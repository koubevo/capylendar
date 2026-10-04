<?php

use Symfony\Component\Process\Process;

/**
 * @param  list<string>  $arguments
 */
function runRelationshipImageTest(string $script, array $arguments = []): mixed
{
    $harness = <<<'JS'
const fs = require('node:fs');
const vm = require('node:vm');
const ts = require('typescript');
const vue = require('vue');
const { parse, compileScript } = require('@vue/compiler-sfc');
function loadModule(path, globals = {}, inlineTemplate = false) {
    let source = fs.readFileSync(path, 'utf8');
    if (path.endsWith('.vue')) {
        source = compileScript(parse(source).descriptor, { id: 'relationship-export', inlineTemplate }).content;
    }
    const exports = {};
    vm.runInNewContext(ts.transpileModule(source, {
        compilerOptions: { module: ts.ModuleKind.CommonJS, target: ts.ScriptTarget.ES2022 },
    }).outputText, { exports, require, Blob, File, DOMException, ...globals });
    return exports;
}
const utilityPath = 'resources/js/lib/relationshipImage.ts';
(async () => {
JS;
    $process = new Process(['node', '-e', $harness.$script."\n})().then(result => process.stdout.write(JSON.stringify(result))).catch(error => { console.error(error); process.exitCode = 1; });", ...$arguments], base_path());
    $process->mustRun();

    return json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);
}

it('exports current Czech card text and the illustration at readable resolution without changing the card', function (string $lineHeight, bool $unbrokenWord) {
    $result = runRelationshipImageTest(<<<'JS'
const options = JSON.parse(process.argv[1]);
const description = options.unbrokenWord ? 'ž'.repeat(200) : 'dlouhý popis '.repeat(20);
const texts = ['Spolu 1234 dní', '3 roky 4 měsíce 5 dní', 'Nejbližší milník: ' + description, 'Neděle 25.10.26 (za 21 dní)'];
const paragraphs = texts.map(textContent => ({ textContent }));
const painted = [];
let measurements = 0;
const context = {
    measureText: text => { measurements++; return { width: Array.from(text).length * 8 }; },
    scale() {}, fillRect() {}, beginPath() {}, roundRect() {}, stroke() {},
    fillText(text, x, y) { painted.push({ text, x, y, color: this.fillStyle }); },
    drawImage(image, x, y, width, height) { this.image = { x, y, width, height }; },
};
const canvas = { getContext: () => context, toBlob: (callback, type) => callback(new Blob(['PNG'], { type })) };
const image = { decode: async () => {}, naturalWidth: 400, naturalHeight: 200 };
const card = { querySelector: () => image, querySelectorAll: () => paragraphs };
const api = loadModule(utilityPath, {
    document: { fonts: { ready: Promise.resolve() }, createElement: () => canvas },
    getComputedStyle: () => ({ fontWeight: '400', fontSize: '16px', fontFamily: 'sans-serif', lineHeight: options.lineHeight, color: '#eee', backgroundColor: '#111', getPropertyValue: () => '#333' }),
});
const blob = await api.createRelationshipImage(card);
const efficientMeasurements = measurements <= texts.join(' ').split(/\s+/).length * 2 + (options.unbrokenWord ? description.length : 0);
return { type: blob.type, width: canvas.width, height: canvas.height, texts: painted.map(line => line.text).join(' '), preservedText: painted.map(line => line.text).join('').replace(/\s/g, '') === texts.join('').replace(/\s/g, ''), efficientMeasurements, originals: paragraphs.map(p => p.textContent), inBounds: painted.every(line => line.x + context.measureText(line.text).width <= context.image.x && line.y * 2 < canvas.height), background: context.image, colors: painted.map(line => line.color) };
JS, [json_encode(['lineHeight' => $lineHeight, 'unbrokenWord' => $unbrokenWord], JSON_THROW_ON_ERROR)]);

    expect($result['type'])->toBe('image/png')
        ->and($result['width'])->toBe(1280)
        ->and($result['height'])->toBeGreaterThan(380)
        ->and($result['texts'])->toContain('Spolu 1234 dní', '3 roky 4 měsíce 5 dní', 'Nejbližší milník:', 'Neděle 25.10.26 (za 21 dní)')
        ->not->toContain('Sdílet', 'Stáhnout', 'Uložit nastavení')
        ->and($result['originals'][0])->toBe('Spolu 1234 dní')
        ->and($result['inBounds'])->toBeTrue()
        ->and($result['preservedText'])->toBeTrue()
        ->and($result['efficientMeasurements'])->toBeTrue()
        ->and($result['background']['width'])->toBe(128)
        ->and($result['background']['height'])->toBe(64)
        ->and(array_unique($result['colors']))->toBe(['#eee']);
})->with([
    'explicit line height' => ['24px', false],
    'normal line height' => ['normal', false],
    'oversized Czech word' => ['24px', true],
]);

it('rejects failed image decoding and PNG creation instead of exporting an incomplete image', function () {
    $result = runRelationshipImageTest(<<<'JS'
const results = [];
for (const failure of ['missing', 'decode', 'context', 'blob']) {
    const image = { decode: async () => { if (failure === 'decode') throw new Error('decode'); }, naturalWidth: 128, naturalHeight: 128 };
    const context = { scale() {}, fillRect() {}, beginPath() {}, roundRect() {}, stroke() {}, drawImage() {} };
    const api = loadModule(utilityPath, {
        document: { fonts: { ready: Promise.resolve() }, createElement: () => ({ getContext: () => failure === 'context' ? null : context, toBlob: callback => callback(null) }) },
        getComputedStyle: () => ({ backgroundColor: '#fff', getPropertyValue: () => '#ddd' }),
    });
    try {
        await api.createRelationshipImage({ querySelector: () => failure === 'missing' ? null : image, querySelectorAll: () => [] });
        results.push(false);
    } catch { results.push(true); }
}
return results;
JS);

    expect($result)->toBe([true, true, true, true]);
});

it('offers clipboard and sharing only for supported PNG files in a secure context', function () {
    $result = runRelationshipImageTest(<<<'JS'
const file = new File(['PNG'], 'relationship.png', { type: 'image/png' });
const cases = [];
for (const secure of [true, false]) {
    for (const supported of [true, false]) {
        const api = loadModule(utilityPath, {
            window: { isSecureContext: secure },
            navigator: { clipboard: { write() {} }, share() {}, canShare: data => data.files[0] === file && supported },
            ClipboardItem: { supports: type => type === 'image/png' && supported },
        });
        cases.push([api.canCopyRelationshipImage(), api.canShareRelationshipImage(file)]);
    }
}
const unsupported = loadModule(utilityPath, { window: { isSecureContext: true }, navigator: {} });
cases.push([unsupported.canCopyRelationshipImage(), unsupported.canShareRelationshipImage(file)]);
const server = loadModule(utilityPath);
cases.push([server.canCopyRelationshipImage(), server.canShareRelationshipImage(file)]);
return cases;
JS);

    expect($result)->toBe([[true, true], [false, false], [false, false], [false, false], [false, false], [false, false]]);
});

it('prepares without sharing and sends the PNG only after an explicit copy share or download action', function () {
    $result = runRelationshipImageTest(<<<'JS'
const calls = [];
let failure = '';
class ClipboardItem { constructor(data) { this.data = data; } }
class FileReader { readAsDataURL() { this.result = 'data:image/png;base64,UE5H'; this.onload(); } }
const component = loadModule('resources/js/components/settings/RelationshipImageExport.vue', {
    FileReader, ClipboardItem,
    navigator: {
        clipboard: { write: async items => { if (failure === 'copy') throw new Error(); calls.push(['copy', items[0].data['image/png'].type]); } },
        share: async data => { if (failure) throw new DOMException('', failure); calls.push(['share', data.files[0].type]); },
    },
    document: { body: { append() {} }, createElement: () => ({ click() { if (failure === 'download') throw new Error(); calls.push(['download', this.download, this.href]); }, remove() {} }) },
    require: path => path === 'vue' ? vue : {
        createRelationshipImage: async () => { if (failure === 'prepare') throw new Error(); return new Blob(['PNG'], { type: 'image/png' }); },
        canCopyRelationshipImage: () => true, canShareRelationshipImage: () => true,
    },
}).default.setup({ card: {} }, { expose() {} });
await component.prepare();
const automaticCalls = calls.length;
await component.exportImage('copy');
await component.exportImage('share');
await component.exportImage('download');
failure = 'AbortError';
await component.exportImage('share');
const cancelledError = component.error.value;
failure = 'NotAllowedError';
await component.exportImage('share');
const shareFailed = !!component.error.value && !!component.file.value;
failure = 'copy';
await component.exportImage('copy');
const copyFailed = !!component.error.value && !!component.file.value;
failure = 'download';
await component.exportImage('download');
const downloadFailed = !!component.error.value && !!component.file.value;
failure = 'prepare';
await component.prepare();
const prepareFailed = !!component.error.value && component.file.value === null && !component.preparing.value;
failure = '';
await component.prepare();
return { automaticCalls, calls, cancelledError, shareFailed, copyFailed, downloadFailed, prepareFailed, retried: !!component.file.value, processing: component.processing.value };
JS);

    expect($result['automaticCalls'])->toBe(0)
        ->and($result['calls'])->toBe([
            ['copy', 'image/png'],
            ['share', 'image/png'],
            ['download', 'capylendar-spolu.png', 'data:image/png;base64,UE5H'],
        ])
        ->and($result['cancelledError'])->toBe('')
        ->and($result['shareFailed'])->toBeTrue()
        ->and($result['copyFailed'])->toBeTrue()
        ->and($result['downloadFailed'])->toBeTrue()
        ->and($result['prepareFailed'])->toBeTrue()
        ->and($result['retried'])->toBeTrue()
        ->and($result['processing'])->toBeFalse();
});

it('disables the export button until the relationship card is available', function () {
    $result = runRelationshipImageTest(<<<'JS'
const { renderToString } = require('vue/server-renderer');
const component = loadModule('resources/js/components/settings/RelationshipImageExport.vue', {
    require: path => path === 'vue' ? vue : {},
}, true).default;
const disabled = [];
for (const card of [null, {}]) {
    const app = vue.createSSRApp(component, { card });
    app.component('UButton', {
        props: ['disabled'],
        setup: (props, { slots }) => () => vue.h('button', { disabled: props.disabled }, slots.default?.()),
    });
    app.component('UModal', { render: () => null });
    const html = await renderToString(app);
    disabled.push(/<button[^>]* disabled(?:\s|>)/.test(html));
}
return disabled;
JS);

    expect($result)->toBe([true, false]);
});
