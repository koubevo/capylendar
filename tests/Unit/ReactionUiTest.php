<?php

use Symfony\Component\Process\Process;

/**
 * @param  list<string>  $arguments
 */
function runReactionUiTest(string $script, array $arguments = []): mixed
{
    $harness = <<<'JS'
const fs = require('node:fs');
const vm = require('node:vm');
const ts = require('typescript');
const vue = require('vue');
const { parse, compileScript } = require('@vue/compiler-sfc');
const { renderToString } = require('vue/server-renderer');

const runtime = {
    document: { cookie: '' },
    fetch: () => Promise.reject(new Error('fetch was not configured')),
    pageProps: { auth: { user: null }, reactionOptions: [] },
    renderPickerContent: false,
};
const moduleCache = new Map();

function resolvePath(path) {
    if (path.startsWith('@/')) {
        path = `resources/js/${path.slice(2)}`;
    }

    if (fs.existsSync(path)) {
        return path;
    }

    for (const extension of ['.ts', '.js', '.vue']) {
        if (fs.existsSync(path + extension)) {
            return path + extension;
        }
    }

    return path;
}

function routeValue(value) {
    return typeof value === 'object' ? value.id : value;
}

function reactionRoute(itemType, method, args) {
    const itemName = itemType === 'event' ? 'event' : 'todo';

    return {
        url: `/${itemName}/${routeValue(args[itemName])}/reactions/${args.reaction}`,
        method,
    };
}

const buttonStub = {
    inheritAttrs: false,
    setup(_, { attrs, slots }) {
        return () => vue.h('button', { ...attrs }, slots.default?.());
    },
};
const avatarStub = {
    inheritAttrs: false,
    setup(_, { attrs }) {
        return () => vue.h('img', { ...attrs });
    },
};
const popoverStub = {
    inheritAttrs: false,
    props: { open: Boolean },
    setup(props, { slots }) {
        return () =>
            vue.h('div', { 'data-reaction-picker': '' }, [
                slots.default?.(),
                (props.open || runtime.renderPickerContent)
                    ? slots.content?.()
                    : null,
            ]);
    },
};

function mockedRequire(path) {
    if (path === 'vue') {
        return vue;
    }

    if (path === 'vue/server-renderer') {
        return require('vue/server-renderer');
    }

    if (path === '@inertiajs/vue3') {
        return { usePage: () => ({ props: runtime.pageProps }) };
    }

    if (path === '@/actions/App/Http/Controllers/EventReactionController') {
        return {
            store: args => reactionRoute('event', 'put', args),
            destroy: args => reactionRoute('event', 'delete', args),
        };
    }

    if (path === '@/actions/App/Http/Controllers/TodoReactionController') {
        return {
            store: args => reactionRoute('todo', 'put', args),
            destroy: args => reactionRoute('todo', 'delete', args),
        };
    }

    if (path === '@nuxt/ui/components/Button.vue') {
        return { default: buttonStub };
    }

    if (path === '@nuxt/ui/components/Avatar.vue') {
        return { default: avatarStub };
    }

    if (path === '@nuxt/ui/components/Popover.vue') {
        return { default: popoverStub };
    }

    if (path.startsWith('@/')) {
        return loadModule(resolvePath(path));
    }

    return require(path);
}

function loadModule(path) {
    const resolvedPath = resolvePath(path);

    if (moduleCache.has(resolvedPath)) {
        return moduleCache.get(resolvedPath);
    }

    let source = fs.readFileSync(resolvedPath, 'utf8');
    if (resolvedPath.endsWith('.vue')) {
        source = compileScript(parse(source).descriptor, {
            id: 'reaction-ui-test',
            inlineTemplate: true,
        }).content;
    }

    const exports = {};
    moduleCache.set(resolvedPath, exports);
    vm.runInNewContext(
        ts.transpileModule(source, {
            compilerOptions: {
                module: ts.ModuleKind.CommonJS,
                target: ts.ScriptTarget.ES2022,
            },
        }).outputText,
        {
            exports,
            require: mockedRequire,
            Blob,
            File,
            DOMException,
            Promise,
            console,
            document: runtime.document,
            fetch: (...args) => runtime.fetch(...args),
            setTimeout,
            clearTimeout,
        },
    );

    return exports;
}

(async () => {
JS;
    $process = new Process([
        'node',
        '-e',
        $harness.$script."\n})().then(result => process.stdout.write(JSON.stringify(result))).catch(error => { console.error(error); process.exitCode = 1; });",
        ...$arguments,
    ], base_path());
    $process->mustRun();

    return json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);
}

it('optimistically toggles reactions with isolated request state and exact fetch headers', function () {
    $result = runReactionUiTest(<<<'JS'
const { useItemReactions } = loadModule('resources/js/composables/useItemReactions.ts');
const own = { id: 1, name: 'Já', capybara: 'pink' };
const partner = { id: 2, name: 'Partner', capybara: 'blue' };
const originalGroups = [
    { key: 'approve', users: [partner, own] },
    { key: 'love', users: [own] },
];
const props = vue.ref(originalGroups);
const calls = [];
const pending = [];
runtime.document.cookie = `session=ok; XSRF-TOKEN=${encodeURIComponent('csrf +/?=')}`;
runtime.fetch = (url, options) =>
    new Promise((resolve, reject) => {
        const request = { url, options, resolve, reject };
        calls.push(request);
        pending.push(request);
    });

const api = useItemReactions({
    itemType: 'event',
    itemId: 7,
    reactions: props,
    currentUser: vue.ref(own),
});
const snapshot = value => JSON.parse(JSON.stringify(value));
const group = key => api.reactions.value.find(item => item.key === key);
const add = api.toggle('seen');
const duplicate = api.toggle('seen');
const addWasOptimistic = group('seen')?.users.some(user => user.id === own.id);
const propsAfterAdd = snapshot(props.value);
pending[0].resolve({
    ok: true,
    json: async () => ({
        reactions: [
            { key: 'approve', users: [partner, own] },
            { key: 'seen', users: [partner, own] },
            { key: 'love', users: [own] },
        ],
    }),
});
const addResult = await add;

const remove = api.toggle('approve');
const removeWasOptimistic =
    group('approve')?.users.map(user => user.id).join(',') === '2';
pending[1].resolve({
    ok: true,
    json: async () => ({
        reactions: [
            { key: 'approve', users: [partner] },
            { key: 'seen', users: [partner, own] },
            { key: 'love', users: [own] },
        ],
    }),
});
const removeResult = await remove;

const removeLast = api.toggle('love');
const lastRemoveWasOptimistic = !group('love');
pending[2].resolve({ ok: true, json: async () => ({ reactions: [] }) });
const lastRemoveResult = await removeLast;

const nullApi = useItemReactions({
    itemType: 'todo',
    itemId: 8,
    reactions: [],
    currentUser: vue.ref(null),
});
const nullResult = await nullApi.toggle('question');

return {
    addWasOptimistic,
    removeWasOptimistic,
    lastRemoveWasOptimistic,
    requestChecks: calls.map(request => ({
        url: request.url,
        method: request.options.method,
        credentials: request.options.credentials,
        headers: request.options.headers,
    })),
    duplicateResult: await duplicate,
    addResult,
    removeResult,
    lastRemoveResult,
    nullResult,
    requestCountAfterNull: calls.length,
    originalPropsUntouched:
        JSON.stringify(propsAfterAdd) === JSON.stringify(originalGroups),
    lastGroupHidden: !api.reactions.value.some(group => group.key === 'love'),
};
JS);

    expect($result)->toMatchArray([
        'addWasOptimistic' => true,
        'removeWasOptimistic' => true,
        'lastRemoveWasOptimistic' => true,
        'duplicateResult' => false,
        'addResult' => true,
        'removeResult' => true,
        'lastRemoveResult' => true,
        'nullResult' => false,
        'requestCountAfterNull' => 3,
        'originalPropsUntouched' => true,
        'lastGroupHidden' => true,
    ])
        ->and($result['requestChecks'])->toBe([
            [
                'url' => '/event/7/reactions/seen',
                'method' => 'PUT',
                'credentials' => 'same-origin',
                'headers' => [
                    'Accept' => 'application/json',
                    'X-Requested-With' => 'XMLHttpRequest',
                    'X-XSRF-TOKEN' => 'csrf +/?=',
                ],
            ],
            [
                'url' => '/event/7/reactions/approve',
                'method' => 'DELETE',
                'credentials' => 'same-origin',
                'headers' => [
                    'Accept' => 'application/json',
                    'X-Requested-With' => 'XMLHttpRequest',
                    'X-XSRF-TOKEN' => 'csrf +/?=',
                ],
            ],
            [
                'url' => '/event/7/reactions/love',
                'method' => 'DELETE',
                'credentials' => 'same-origin',
                'headers' => [
                    'Accept' => 'application/json',
                    'X-Requested-With' => 'XMLHttpRequest',
                    'X-XSRF-TOKEN' => 'csrf +/?=',
                ],
            ],
        ]);
});

it('merges independent responses and rolls back only failed reaction keys', function () {
    $result = runReactionUiTest(<<<'JS'
const { useItemReactions } = loadModule('resources/js/composables/useItemReactions.ts');
const own = { id: 1, name: 'Já', capybara: 'pink' };
const partner = { id: 2, name: 'Partner', capybara: 'blue' };
const calls = new Map();
runtime.fetch = (url, options) =>
    new Promise((resolve, reject) => {
        calls.set(url, { url, options, resolve, reject });
    });
const props = vue.ref([
    { key: 'approve', users: [own] },
    { key: 'celebrate', users: [partner] },
]);
const api = useItemReactions({
    itemType: 'event',
    itemId: 9,
    reactions: props,
    currentUser: vue.ref(own),
});
const group = key => api.reactions.value.find(item => item.key === key);
const groupsByKey = () => Object.fromEntries(
    api.reactions.value
        .slice()
        .sort((left, right) => left.key.localeCompare(right.key))
        .map(item => [item.key, item.users.map(user => user.id)]),
);

const failedApprove = api.toggle('approve');
const successfulSeen = api.toggle('seen');
calls.get('/event/9/reactions/seen').resolve({
    ok: true,
    json: async () => ({ reactions: [{ key: 'seen', users: [partner, own] }] }),
});
const seenResult = await successfulSeen;
const groupsAfterSeen = groupsByKey();
calls.get('/event/9/reactions/approve').reject(new Error('offline'));
const approveResult = await failedApprove;

const failedNonOk = api.toggle('celebrate');
calls.get('/event/9/reactions/celebrate').resolve({
    ok: false,
    json: async () => ({ reactions: [] }),
});
const nonOkResult = await failedNonOk;

const malformed = api.toggle('question');
const successfulLove = api.toggle('love');
calls.get('/event/9/reactions/question').resolve({
    ok: true,
    json: async () => ({ reactions: [{ key: 'question', users: [{ id: 1 }] }] }),
});
calls.get('/event/9/reactions/love').resolve({
    ok: true,
    json: async () => ({ reactions: [{ key: 'love', users: [own] }] }),
});
const malformedResult = await malformed;
const loveResult = await successfulLove;

return {
    groupsAfterSeen,
    groupsAfterFailures: groupsByKey(),
    seenResult,
    approveResult,
    nonOkResult,
    malformedResult,
    loveResult,
    errors: Object.keys(api.errors.value).sort(),
    approveUsers: group('approve')?.users.map(user => user.id),
    celebrateUsers: group('celebrate')?.users.map(user => user.id),
    seenUsers: group('seen')?.users.map(user => user.id),
    loveUsers: group('love')?.users.map(user => user.id),
    questionPresent: Boolean(group('question')),
};
JS);

    expect($result['groupsAfterSeen'])->toBe([
        'celebrate' => [2],
        'seen' => [2, 1],
    ])
        ->and($result['groupsAfterFailures'])->toBe([
            'approve' => [1],
            'celebrate' => [2],
            'love' => [1],
            'seen' => [2, 1],
        ])
        ->and($result['seenResult'])->toBeTrue()
        ->and($result['approveResult'])->toBeFalse()
        ->and($result['nonOkResult'])->toBeFalse()
        ->and($result['malformedResult'])->toBeFalse()
        ->and($result['loveResult'])->toBeTrue()
        ->and($result['errors'])->toBe(['approve', 'celebrate', 'question'])
        ->and($result['approveUsers'])->toBe([1])
        ->and($result['celebrateUsers'])->toBe([2])
        ->and($result['seenUsers'])->toBe([2, 1])
        ->and($result['loveUsers'])->toBe([1])
        ->and($result['questionPresent'])->toBeFalse();
});

it('preserves both successful keys when responses resolve out of order', function () {
    $result = runReactionUiTest(<<<'JS'
const { useItemReactions } = loadModule('resources/js/composables/useItemReactions.ts');
const own = { id: 1, name: 'Já', capybara: 'pink' };
const partner = { id: 2, name: 'Partner', capybara: 'blue' };
const calls = new Map();
runtime.fetch = (url, options) =>
    new Promise((resolve, reject) => {
        calls.set(url, { url, options, resolve, reject });
    });
const api = useItemReactions({
    itemType: 'event',
    itemId: 12,
    reactions: [],
    currentUser: vue.ref(own),
});
const groupsByKey = () => Object.fromEntries(
    api.reactions.value
        .slice()
        .sort((left, right) => left.key.localeCompare(right.key))
        .map(item => [item.key, item.users.map(user => user.id)]),
);
const approve = api.toggle('approve');
const seen = api.toggle('seen');
calls.get('/event/12/reactions/seen').resolve({
    ok: true,
    json: async () => ({ reactions: [{ key: 'seen', users: [partner, own] }] }),
});
const seenResult = await seen;
const afterFirstResponse = groupsByKey();
calls.get('/event/12/reactions/approve').resolve({
    ok: true,
    json: async () => ({ reactions: [{ key: 'approve', users: [partner, own] }] }),
});
const approveResult = await approve;

return { afterFirstResponse, finalGroups: groupsByKey(), seenResult, approveResult };
JS);

    expect($result)->toBe([
        'afterFirstResponse' => [
            'approve' => [1],
            'seen' => [2, 1],
        ],
        'finalGroups' => [
            'approve' => [2, 1],
            'seen' => [2, 1],
        ],
        'seenResult' => true,
        'approveResult' => true,
    ]);
});

it('preserves pending keys while replacing other keys from refreshed props', function () {
    $result = runReactionUiTest(<<<'JS'
const { useItemReactions } = loadModule('resources/js/composables/useItemReactions.ts');
const own = { id: 1, name: 'Já', capybara: 'pink' };
const partner = { id: 2, name: 'Partner', capybara: 'blue' };
let request;
runtime.fetch = (url, options) =>
    new Promise((resolve, reject) => {
        request = { url, options, resolve, reject };
    });
const incoming = [
    { key: 'approve', users: [partner] },
    { key: 'love', users: [partner] },
];
const props = vue.ref(incoming);
const api = useItemReactions({
    itemType: 'todo',
    itemId: 11,
    reactions: props,
    currentUser: vue.ref(own),
});
const groupsByKey = () => Object.fromEntries(
    api.reactions.value
        .slice()
        .sort((left, right) => left.key.localeCompare(right.key))
        .map(item => [item.key, item.users.map(user => user.id)]),
);
const pendingAdd = api.toggle('seen');
props.value = [
    { key: 'approve', users: [own] },
    { key: 'seen', users: [partner] },
    { key: 'celebrate', users: [partner] },
];
await vue.nextTick();
const afterRefresh = groupsByKey();
request.resolve({
    ok: true,
    json: async () => ({ reactions: [{ key: 'seen', users: [partner, own] }] }),
});
const requestResult = await pendingAdd;

return {
    afterRefresh,
    finalGroups: groupsByKey(),
    incomingUntouched: JSON.stringify(incoming) === JSON.stringify([
        { key: 'approve', users: [partner] },
        { key: 'love', users: [partner] },
    ]),
    requestResult,
    request: { url: request.url, method: request.options.method },
};
JS);

    expect($result['afterRefresh'])->toBe([
        'approve' => [1],
        'celebrate' => [2],
        'seen' => [1],
    ])
        ->and($result['finalGroups'])->toBe([
            'approve' => [1],
            'celebrate' => [2],
            'seen' => [2, 1],
        ])
        ->and($result['incomingUntouched'])->toBeTrue()
        ->and($result['requestResult'])->toBeTrue()
        ->and($result['request'])->toBe([
            'url' => '/todo/11/reactions/seen',
            'method' => 'PUT',
        ]);
});

it('groups reaction emoji by user and keeps the picker and guest state accessible', function () {
    $result = runReactionUiTest(<<<'JS'
const options = [
    { key: 'love', emoji: '❤️', label: 'Líbí se mi' },
    { key: 'celebrate', emoji: '🤩', label: 'Těším se' },
    { key: 'seen', emoji: '👀', label: 'Viděl/a jsem' },
    { key: 'approve', emoji: '🙏', label: 'Díky' },
    { key: 'question', emoji: '🤔', label: 'Otázka / nejistota' },
];
const own = { id: 1, name: 'Já', capybara: 'pink' };
const partner = { id: 2, name: 'Partner', capybara: 'blue' };
const reactionUserSummary = loadModule('resources/js/components/reactions/ReactionUserSummary.vue').default;
const itemReactions = loadModule('resources/js/components/reactions/ItemReactions.vue').default;

async function render(component, props) {
    return renderToString(vue.createSSRApp(component, props));
}

function emojiAppearInOrder(html, emoji) {
    const positions = emoji.map(value => html.indexOf(value));
    return positions.every((position, index) =>
        position >= 0 && (index === 0 || position > positions[index - 1]),
    );
}

const summaryHtml = await render(reactionUserSummary, {
    user: partner,
    options: [options[0], options[3]],
});
runtime.pageProps = { auth: { user: own }, reactionOptions: options };
const listHtml = await render(itemReactions, {
    itemType: 'event',
    itemId: 7,
    reactions: [
        { key: 'approve', users: [partner] },
        { key: 'seen', users: [own] },
        { key: 'love', users: [partner] },
    ],
});
const detailHtml = await render(itemReactions, {
    itemType: 'event',
    itemId: 7,
    reactions: [
        { key: 'approve', users: [partner] },
        { key: 'seen', users: [own] },
        { key: 'love', users: [partner] },
    ],
});
runtime.renderPickerContent = true;
const pickerHtml = await render(itemReactions, {
    itemType: 'event',
    itemId: 7,
    reactions: [{ key: 'approve', users: [partner] }],
});
const pickerButtonsHtml = (pickerHtml.match(/<button\b[^>]*>[\s\S]*?<\/button>/g) ?? []).join('');
runtime.renderPickerContent = false;
runtime.pageProps = { auth: { user: null }, reactionOptions: options };
const guestHtml = await render(itemReactions, {
    itemType: 'event',
    itemId: 7,
    reactions: [{ key: 'approve', users: [partner] }],
});

return {
    summaryHasAvatar: summaryHtml.includes('/images/capys/blue.jpg'),
    summaryShowsSelectedEmoji:
        summaryHtml.includes('🙏') && summaryHtml.includes('❤️'),
    summaryHidesVisibleName: !summaryHtml.includes('>Partner<'),
    listGroupsByUser:
        (listHtml.match(/data-reaction-user/g) ?? []).length === 2,
    listShowsSelectedEmoji:
        listHtml.includes('👀') &&
        emojiAppearInOrder(listHtml, ['❤️', '🙏']),
    listHidesVisibleLabels: options.every(
        option => !listHtml.includes(`>${option.label}<`),
    ),
    listHasPicker: listHtml.includes('Přidat reakci'),
    detailGroupsByUser:
        (detailHtml.match(/data-reaction-user/g) ?? []).length === 2,
    detailKeepsEmojiOrder: emojiAppearInOrder(detailHtml, ['❤️', '🙏']),
    pickerShowsAll: options.every(
        option => pickerHtml.includes(option.label) && pickerHtml.includes(option.emoji),
    ),
    pickerKeepsEmojiOrder: emojiAppearInOrder(pickerButtonsHtml, ['❤️', '🤩', '👀', '🙏', '🤔']),
    pickerHidesLabels: options.every(
        option => !pickerHtml.includes(`>${option.label}<`),
    ),
    guestShowsUsed: guestHtml.includes('Díky'),
    guestHasNoPicker: !guestHtml.includes('Přidat reakci'),
    guestIsReadOnly: !/<button/.test(guestHtml),
};
JS);

    expect($result)->toBe([
        'summaryHasAvatar' => true,
        'summaryShowsSelectedEmoji' => true,
        'summaryHidesVisibleName' => true,
        'listGroupsByUser' => true,
        'listShowsSelectedEmoji' => true,
        'listHidesVisibleLabels' => true,
        'listHasPicker' => true,
        'detailGroupsByUser' => true,
        'detailKeepsEmojiOrder' => true,
        'pickerShowsAll' => true,
        'pickerKeepsEmojiOrder' => true,
        'pickerHidesLabels' => true,
        'guestShowsUsed' => true,
        'guestHasNoPicker' => true,
        'guestIsReadOnly' => true,
    ]);
});

it('shares injected state across consumers and isolates item type and id keys', function () {
    $result = runReactionUiTest(<<<'JS'
const { provideItemReactions, useItemReactions } = loadModule('resources/js/composables/useItemReactions.ts');
const own = { id: 1, name: 'Já', capybara: 'pink' };
const partner = { id: 2, name: 'Partner', capybara: 'blue' };
const initial = [
    { key: 'approve', users: [partner] },
    { key: 'love', users: [partner] },
];
const newer = [
    { key: 'approve', users: [own] },
    { key: 'seen', users: [partner] },
    { key: 'celebrate', users: [partner] },
];
const requests = [];
runtime.fetch = (url, options) =>
    new Promise((resolve, reject) => {
        requests.push({ url, options, resolve, reject });
    });

const consumers = [];
let firstToggle;
const child = {
    props: { name: String, itemType: String, itemId: Number, reactions: Array },
    setup(props) {
        const api = useItemReactions({
            itemType: props.itemType,
            itemId: props.itemId,
            reactions: props.reactions,
            currentUser: vue.ref(own),
        });
        consumers.push({ name: props.name, api });
        if (props.name === 'first') {
            firstToggle = api.toggle('seen');
        }

        return () => vue.h('span', props.name);
    },
};
const parent = {
    setup() {
        provideItemReactions();

        return () => vue.h('div', [
            vue.h(child, {
                name: 'first', itemType: 'event', itemId: 1, reactions: initial,
            }),
            vue.h(child, {
                name: 'same-key', itemType: 'event', itemId: 1, reactions: initial,
            }),
            vue.h(child, {
                name: 'new-props-remount', itemType: 'event', itemId: 1, reactions: newer,
            }),
            vue.h(child, {
                name: 'todo-same-id', itemType: 'todo', itemId: 1, reactions: newer,
            }),
            vue.h(child, {
                name: 'event-other-id', itemType: 'event', itemId: 2, reactions: newer,
            }),
        ]);
    },
};
await renderToString(vue.createSSRApp(parent));
const byName = name => consumers.find(consumer => consumer.name === name).api;
const first = byName('first');
const sameKey = byName('same-key');
const remount = byName('new-props-remount');
const todo = byName('todo-same-id');
const otherId = byName('event-other-id');
const snapshot = api => JSON.parse(JSON.stringify(api.reactions.value));
const group = (api, key) => api.reactions.value.find(item => item.key === key);
const duplicateResult = await sameKey.toggle('seen');
const sharedOptimisticState =
    Boolean(group(sameKey, 'seen')?.users.some(user => user.id === own.id)) &&
    sameKey.isPending('seen') &&
    remount.isPending('seen');
const remountMergedNewProps =
    group(remount, 'approve')?.users[0].id === own.id &&
    group(remount, 'celebrate')?.users[0].id === partner.id &&
    group(remount, 'seen')?.users[0].id === own.id &&
    !group(remount, 'love');
requests[0].resolve({
    ok: true,
    json: async () => ({ reactions: [{ key: 'seen', users: [partner, own] }] }),
});
const firstResult = await firstToggle;

return {
    requestCount: requests.length,
    duplicateResult,
    sharedOptimisticState,
    remountMergedNewProps,
    firstResult,
    finalSharedState: JSON.stringify(snapshot(first)) === JSON.stringify(snapshot(sameKey)) &&
        JSON.stringify(snapshot(first)) === JSON.stringify(snapshot(remount)),
    todoIsolated: !todo.isPending('seen') &&
        JSON.stringify(group(todo, 'seen')?.users.map(user => user.id)) === '[2]',
    otherIdIsolated: !otherId.isPending('seen') &&
        JSON.stringify(group(otherId, 'seen')?.users.map(user => user.id)) === '[2]',
};
JS);

    expect($result)->toBe([
        'requestCount' => 1,
        'duplicateResult' => false,
        'sharedOptimisticState' => true,
        'remountMergedNewProps' => true,
        'firstResult' => true,
        'finalSharedState' => true,
        'todoIsolated' => true,
        'otherIdIsolated' => true,
    ]);
});
