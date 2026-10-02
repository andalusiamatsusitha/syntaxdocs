<?php
/**
 * Syntax Core — Automated Test Suite
 * Run with: php tests/run_tests.php
 */

require_once __DIR__ . '/../packages/core-php/autoload.php';

use Syntax\Core\Http\Request;
use Syntax\Core\Http\Response;
use Syntax\Core\Http\Router;
use Syntax\Core\Http\Middleware\Pipeline;
use Syntax\Core\Http\Middleware\MiddlewareInterface;
use Syntax\Core\Auth\PasswordHasher;
use Syntax\Core\View\Component;
use Syntax\Core\Database\QueryBuilder;
use Syntax\Core\Database\ConnectionManager;
use Syntax\Core\Support\Env;

$totalTests = 0;
$passedTests = 0;
$failedTests = 0;

function it(string $description, callable $testCase) {
    global $totalTests, $passedTests, $failedTests;
    $totalTests++;

    try {
        $testCase();
        echo "  [✓ PASS] {$description}\n";
        $passedTests++;
    } catch (\Throwable $e) {
        echo "  [✗ FAIL] {$description}\n";
        echo "           Reason: " . $e->getMessage() . "\n";
        $failedTests++;
    }
}

function assertEquals($expected, $actual, string $message = '') {
    if ($expected !== $actual) {
        $expStr = var_export($expected, true);
        $actStr = var_export($actual, true);
        throw new \AssertionError("Expected {$expStr} but got {$actStr}. {$message}");
    }
}

function assertTrue($condition, string $message = '') {
    if (!$condition) {
        throw new \AssertionError("Failed asserting that condition is true. {$message}");
    }
}

echo "\n==============================================\n";
echo "    SYNTAX CORE AUTOMATED TEST RUNNER        \n";
echo "==============================================\n\n";

// Group 1: HTTP Request & Response
echo "1. Testing HTTP Layer:\n";

it('Request captures URI and method correctly', function () {
    $req = new Request('POST', '/api/users?sort=desc', ['sort' => 'desc'], ['name' => 'John']);
    assertEquals('POST', $req->getMethod());
    assertEquals('/api/users?sort=desc', $req->getUri());
    assertEquals('desc', $req->query('sort'));
    assertEquals('John', $req->input('name'));
    assertTrue($req->isMethod('post'));
});

it('Response outputs correct JSON and status code', function () {
    $resp = Response::json(['key' => 'value'], 201);
    assertEquals(201, $resp->getStatus());
    assertEquals('{"key":"value"}', $resp->getContent());
    assertEquals('application/json; charset=UTF-8', $resp->getHeaders()['Content-Type']);
});

// Group 2: Onion Middleware Pipeline
echo "\n2. Testing Middleware Pipeline:\n";

class AppendMiddlewareA implements MiddlewareInterface {
    public function handle(Request $req, callable $next): Response {
        $req->setAttribute('order', array_merge($req->getAttribute('order', []), ['A_in']));
        $res = $next($req);
        $res->setHeader('X-Order', 'A_out');
        return $res;
    }
}

class AppendMiddlewareB implements MiddlewareInterface {
    public function handle(Request $req, callable $next): Response {
        $req->setAttribute('order', array_merge($req->getAttribute('order', []), ['B_in']));
        return $next($req);
    }
}

it('Pipeline executes middlewares in correct onion order', function () {
    $req = new Request('GET', '/test');
    $pipeline = new Pipeline();

    $response = $pipeline
        ->send($req)
        ->through([AppendMiddlewareA::class, AppendMiddlewareB::class])
        ->then(function (Request $r) {
            $order = $r->getAttribute('order', []);
            assertEquals(['A_in', 'B_in'], $order);
            return Response::html('ok');
        });

    assertEquals('ok', $response->getContent());
    assertEquals('A_out', $response->getHeaders()['X-Order']);
});

// Group 3: Router & Dynamic Route Matching
echo "\n3. Testing Router:\n";

it('Router matches dynamic route parameters', function () {
    $router = new Router();
    $router->get('/articles/{id}', function (Request $req, $id) {
        return Response::json(['id' => $id]);
    });

    $req = new Request('GET', '/articles/42');
    $resp = $router->dispatch($req);
    assertEquals('{"id":"42"}', $resp->getContent());
});

it('Router handles route groups and prefixes', function () {
    $router = new Router();
    $router->group(['prefix' => '/admin'], function (Router $r) {
        $r->get('/dashboard', function () {
            return Response::html('admin-dashboard');
        });
    });

    $req = new Request('GET', '/admin/dashboard');
    $resp = $router->dispatch($req);
    assertEquals('admin-dashboard', $resp->getContent());
});

// Group 4: Auth & Password Hasher
echo "\n4. Testing Security & Auth:\n";

it('PasswordHasher hashes and verifies passwords correctly', function () {
    $password = 'Secret123!';
    $hash = PasswordHasher::hash($password);
    assertTrue(PasswordHasher::verify($password, $hash), 'Password verify should return true');
    assertTrue(!PasswordHasher::verify('wrong-password', $hash), 'Wrong password verify should return false');
});

// Group 5: View & Component Engine
echo "\n5. Testing View Component Engine:\n";

it('Component renders navbar correctly', function () {
    $html = Component::render('navbar', [
        'brand' => 'Syntax Test',
        'items' => [['label' => 'Home', 'url' => '/home', 'active' => true]]
    ]);
    assertTrue(str_contains($html, 'Syntax Test'), 'HTML should contain brand');
    assertTrue(str_contains($html, 'is-active'), 'HTML should contain active class');
});

it('Component renders modal dialog with escaped content', function () {
    $html = Component::render('modal', [
        'id' => 'testModal',
        'title' => 'Modal <Test>',
        'body' => 'Isi modal',
        'confirm_label' => 'Hapus'
    ]);
    assertTrue(str_contains($html, 'id="testModal"'), 'HTML should contain id');
    assertTrue(str_contains($html, 'Modal &lt;Test&gt;'), 'HTML should escape special characters in title');
});

it('Component renders alert banner', function () {
    $html = Component::render('alert', [
        'message' => 'Operasi sukses',
        'type' => 'success'
    ]);
    assertTrue(str_contains($html, 'c-alert--success'), 'HTML should have alert variant class');
    assertTrue(str_contains($html, 'Operasi sukses'), 'HTML should have message');
});

echo "\n==============================================\n";
echo "SUMMARY: Total: {$totalTests} | Passed: {$passedTests} | Failed: {$failedTests}\n";
echo "==============================================\n\n";

exit($failedTests === 0 ? 0 : 1);
