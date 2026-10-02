<?php

namespace Syntax\Core\View;

class Component
{
    protected static array $customTemplatePaths = [];

    /**
     * Add a template directory path to search for overrides.
     */
    public static function addTemplatePath(string $path): void
    {
        if (is_dir($path)) {
            array_unshift(static::$customTemplatePaths, rtrim($path, '/\\'));
        }
    }

    /**
     * Render a component template with given props.
     */
    public static function render(string $name, array $props = []): string
    {
        $templateFile = static::resolveTemplateFile($name);

        if (!$templateFile) {
            return "<!-- Component '{$name}' not found -->";
        }

        // Isolate scope and extract props
        $renderClosure = function (string $__file, array $__props): string {
            extract($__props, EXTR_SKIP);
            ob_start();
            include $__file;
            return ob_get_clean() ?: '';
        };

        return $renderClosure($templateFile, $props);
    }

    protected static function resolveTemplateFile(string $name): ?string
    {
        $fileName = str_replace('.', '/', $name) . '.php';

        // 1. Search in registered custom paths (e.g. application overrides)
        foreach (static::$customTemplatePaths as $dir) {
            $candidate = $dir . DIRECTORY_SEPARATOR . $fileName;
            if (file_exists($candidate)) {
                return $candidate;
            }
        }

        // 2. Search in Core built-in templates
        $builtIn = dirname(__DIR__, 2) . '/templates/' . $fileName;
        if (file_exists($builtIn)) {
            return $builtIn;
        }

        return null;
    }
}
