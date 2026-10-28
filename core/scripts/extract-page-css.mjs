#!/usr/bin/env node
/**
 * Move a Blade view's @push('style') CSS into a stylesheet for its React page,
 * scoped under one wrapper class so it cannot leak onto other pages (Inertia
 * keeps page CSS loaded after you navigate away).
 *
 *   node scripts/extract-page-css.mjs <view.blade.php> <Page.css> <scope-class>
 *   e.g. node scripts/extract-page-css.mjs resources/views/templates/basic/user/awards.blade.php \
 *            resources/js/Pages/User/Awards.css page-awards
 *
 * The page component must render <div className="page-awards"> around its
 * content. The CSS is written with native nesting (.page-awards { ... }); the
 * Vite build flattens it to ".page-awards .x" for older browsers.
 * @keyframes / @font-face / @import stay at top level (they cannot be nested).
 */
import fs from 'node:fs';

const [view, out, scope] = process.argv.slice(2);
if (!view || !out || !scope) {
    console.error('usage: extract-page-css.mjs <view.blade.php> <Page.css> <scope-class>');
    process.exit(1);
}

const blade = fs.readFileSync(view, 'utf8');
const blocks = [...blade.matchAll(/<style[^>]*>([\s\S]*?)<\/style>/g)].map((m) => m[1]);
if (!blocks.length) {
    console.error(`no <style> block in ${view}`);
    process.exit(1);
}
const css = blocks.join('\n');

if (/\{\{|@(php|if|foreach|lang)\b/.test(css)) {
    console.error('the CSS contains Blade syntax ({{ }} / @php / @if): convert those parts by hand');
    process.exit(1);
}

// Split into top-level statements by tracking brace depth.
const statements = [];
let depth = 0, start = 0;
for (let i = 0; i < css.length; i++) {
    if (css[i] === '{') depth++;
    else if (css[i] === '}' && --depth === 0) {
        statements.push(css.slice(start, i + 1).trim());
        start = i + 1;
    }
}

const hoisted = [], scoped = [], warnings = [];
for (const s of statements) {
    if (/^@(keyframes|-webkit-keyframes|font-face|import)\b/.test(s)) hoisted.push(s);
    else {
        if (/(^|[\s,}])(html|body|:root)\b/.test(s.split('{')[0])) warnings.push(s.split('{')[0].trim());
        scoped.push(s.replace(/^/gm, '    '));
    }
}

fs.writeFileSync(
    out,
    `/* Moved from ${view.replace(/^.*resources\//, 'resources/')} (@push('style')),\n` +
        `   scoped under .${scope} so it only applies to this page. */\n\n` +
        (hoisted.length ? hoisted.join('\n\n') + '\n\n' : '') +
        `.${scope} {\n${scoped.join('\n\n')}\n}\n`,
);

console.log(`${out}: ${scoped.length} rules scoped under .${scope}, ${hoisted.length} kept at top level`);
if (warnings.length) console.log(`check by hand (html/body/:root cannot be scoped): ${warnings.join(' | ')}`);
