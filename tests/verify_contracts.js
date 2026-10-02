/**
 * SyntaxDocs Contract & Asset Verification Suite (Node.js)
 * Run with: node tests/verify_contracts.js
 */

const fs = require('fs');
const path = require('path');

let passed = 0;
let failed = 0;

function assert(condition, message) {
  if (condition) {
    console.log(`  [✓ PASS] ${message}`);
    passed++;
  } else {
    console.error(`  [✗ FAIL] ${message}`);
    failed++;
  }
}

console.log('\n==============================================');
console.log('   SYNTAXDOCS CONTRACT & ASSET VERIFIER      ');
console.log('==============================================\n');

// 1. Verify Specification JSONs
console.log('1. Verifying Specifications & Contracts:');

const compSpecPath = path.join(__dirname, '../docs/specs/components-spec.json');
assert(fs.existsSync(compSpecPath), 'docs/specs/components-spec.json exists');
const compSpec = JSON.parse(fs.readFileSync(compSpecPath, 'utf8'));
assert(compSpec.components && compSpec.components.navbar, 'Component spec defines navbar');
assert(compSpec.components.modal, 'Component spec defines modal');
assert(compSpec.components.card, 'Component spec defines card');
assert(compSpec.components.table, 'Component spec defines table');

const apiSpecPath = path.join(__dirname, '../docs/specs/api-contract.json');
assert(fs.existsSync(apiSpecPath), 'docs/specs/api-contract.json exists');
const apiSpec = JSON.parse(fs.readFileSync(apiSpecPath, 'utf8'));
assert(apiSpec.properties.success && apiSpec.properties.code && apiSpec.properties.data, 'API spec defines standard envelope');

// 2. Verify UI CDN Assets
console.log('\n2. Verifying UI CDN Assets:');

const cssBundlePath = path.join(__dirname, '../packages/ui-cdn/public/css/core.min.css');
assert(fs.existsSync(cssBundlePath), 'packages/ui-cdn/public/css/core.min.css exists');
const cssContent = fs.readFileSync(cssBundlePath, 'utf8');
assert(cssContent.includes('--color-primary'), 'CSS bundle contains design tokens');
assert(cssContent.includes('.c-btn'), 'CSS bundle contains .c-btn component');
assert(cssContent.includes('.c-modal'), 'CSS bundle contains .c-modal component');
assert(cssContent.includes('.c-navbar'), 'CSS bundle contains .c-navbar component');

const jsBundlePath = path.join(__dirname, '../packages/ui-cdn/public/js/core.min.js');
assert(fs.existsSync(jsBundlePath), 'packages/ui-cdn/public/js/core.min.js exists');
const jsContent = fs.readFileSync(jsBundlePath, 'utf8');
assert(jsContent.includes('CoreUI'), 'JS bundle defines CoreUI singleton');
assert(jsContent.includes('Modal:'), 'JS bundle defines Modal controller');
assert(jsContent.includes('data-toggle'), 'JS bundle implements data-toggle delegation');

const showcasePath = path.join(__dirname, '../packages/ui-cdn/public/index.html');
assert(fs.existsSync(showcasePath), 'Showcase index.html exists');

// 3. Verify Applications & Core PHP Structure
console.log('\n3. Verifying Application Layouts:');

assert(fs.existsSync(path.join(__dirname, '../packages/core-php/src/Application.php')), 'Core PHP Application.php exists');
assert(fs.existsSync(path.join(__dirname, '../packages/core-php/autoload.php')), 'Core PHP autoload.php exists');
assert(fs.existsSync(path.join(__dirname, '../apps/cms/public/index.php')), 'CMS public/index.php exists');
assert(fs.existsSync(path.join(__dirname, '../apps/cms/routes/web.php')), 'CMS routes/web.php exists');
assert(fs.existsSync(path.join(__dirname, '../apps/school/public/index.php')), 'School public/index.php exists');
assert(fs.existsSync(path.join(__dirname, '../apps/school/public/theme.css')), 'School custom theme.css exists');

console.log('\n==============================================');
console.log(`SUMMARY: Passed: ${passed} | Failed: ${failed}`);
console.log('==============================================\n');

process.exit(failed === 0 ? 0 : 1);
