<?php

use Symfony\Component\Process\Process;

function runEventSurpriseUiTest(string $script): mixed
{
    $harness = <<<'JS'
const fs = require('node:fs');
const vm = require('node:vm');
const ts = require('typescript');
const vue = require('vue');
const { parse, compileScript } = require('@vue/compiler-sfc');
const runtime = {
    document: { cookie: 'XSRF-TOKEN=csrf%20token' },
    requests: [],
    reloads: [],
    revoked: [],
    status: 204,
};

function setupComponent(path, props) {
    const source = compileScript(parse(fs.readFileSync(path, 'utf8')).descriptor, {
        id: 'event-surprise-ui-test',
    }).content;
    const exports = {};
    vm.runInNewContext(ts.transpileModule(source, {
        compilerOptions: { module: ts.ModuleKind.CommonJS, target: ts.ScriptTarget.ES2022 },
    }).outputText, {
        exports,
        require: path => {
            if (path === 'vue') return { ...vue, onUnmounted: () => {} };
            if (path === '@inertiajs/vue3') return {
                router: { reload: options => runtime.reloads.push(options) },
            };
            if (path.includes('EventSurpriseController')) return {
                unlock: { url: id => `/event/${id}/surprise/unlock` },
            };
            return {};
        },
        document: runtime.document,
        URL: {
            createObjectURL: file => `blob:${file.name}`,
            revokeObjectURL: url => runtime.revoked.push(url),
        },
        fetch: async (url, options) => {
            runtime.requests.push({ url, options });
            return { status: runtime.status, ok: runtime.status === 204 };
        },
    });
    return exports.default.setup(vue.reactive(props), { expose: () => {}, emit: () => {} });
}

(async () => {
JS;

    $process = new Process([
        'node', '-e',
        $harness.$script."\n})().then(result => process.stdout.write(JSON.stringify(result))).catch(error => { console.error(error); process.exitCode = 1; });",
    ], base_path());
    $process->mustRun();

    return json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);
}

it('allows selecting the same surprise again and clears uploads when changing kind', function () {
    $result = runEventSurpriseUiTest(<<<'JS'
const form = vue.reactive({
    kind: 'birthday', surprise_image: null, surprise_password: '', remove_surprise_image: false,
});
const component = setupComponent('resources/js/components/events/EventForm.vue', {
    form, hasSurpriseImage: true,
    kindOptions: [
        { value: 'birthday', allows_surprise: true },
        { value: 'standard', allows_surprise: false },
    ],
});
const input = { files: [{ name: 'gift.jpg' }], value: 'gift.jpg' };
component.onSurpriseImageSelected({ target: input });
const first = { input: input.value, preview: component.surprisePreview.value };
component.clearSurpriseImage();
const removed = { image: form.surprise_image, remove: form.remove_surprise_image };
component.onSurpriseImageSelected({ target: input });
form.surprise_password = 'meloun';
const replaced = { image: form.surprise_image.name, remove: form.remove_surprise_image };
form.kind = 'standard';
await vue.nextTick();
return { first, removed, replaced, cleared: {
    image: form.surprise_image, password: form.surprise_password, preview: component.surprisePreview.value,
}, revoked: runtime.revoked };
JS);

    expect($result['first'])->toBe(['input' => '', 'preview' => 'blob:gift.jpg']);
    expect($result['removed'])->toBe(['image' => null, 'remove' => true]);
    expect($result['replaced'])->toBe(['image' => 'gift.jpg', 'remove' => false]);
    expect($result['cleared'])->toBe(['image' => null, 'password' => '', 'preview' => null]);
    expect($result['revoked'])->toBe(['blob:gift.jpg', 'blob:gift.jpg']);
});

it('unlocks with a background request and refreshes only after success', function () {
    $result = runEventSurpriseUiTest(<<<'JS'
const component = setupComponent('resources/js/components/events/EventSurpriseCard.vue', {
    eventId: 7, surprise: { is_unlocked: false, image_url: null }, classes: '',
});
const errors = [];
for (const status of [422, 429, 500, 204]) {
    runtime.status = status;
    component.password.value = 'meloun';
    await component.submit();
    errors.push(Boolean(component.error.value));
}
return { errors, requests: runtime.requests, reloads: runtime.reloads,
    password: component.password.value, pending: component.isUnlocking.value };
JS);

    expect($result['errors'])->toBe([true, true, true, false]);
    expect($result['reloads'])->toBe([['only' => ['event']]]);
    expect($result['password'])->toBe('');
    expect($result['pending'])->toBeFalse();
    expect($result['requests'])->toHaveCount(4);
    expect($result['requests'][0])->toBe([
        'url' => '/event/7/surprise/unlock',
        'options' => [
            'method' => 'POST',
            'credentials' => 'same-origin',
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest',
                'X-XSRF-TOKEN' => 'csrf token',
            ],
            'body' => '{"password":"meloun"}',
        ],
    ]);
});
