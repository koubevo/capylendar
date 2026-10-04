<?php

use Symfony\Component\Process\Process;

it('links Plane work items from PR branches without duplicate updates', function (string $branch, ?string $body, ?string $expectedBody) {
    $workflow = file_get_contents(base_path('.github/workflows/plane-link.yml'));
    $script = preg_replace('/^ {12}/m', '', explode('script: |', $workflow, 2)[1]);
    $harness = <<<'JS'
const AsyncFunction = Object.getPrototypeOf(async function () {}).constructor;
const context = {
    payload: { pull_request: { head: { ref: process.argv[2] }, body: JSON.parse(process.argv[3]), number: 93 } },
    repo: { owner: 'koubevo', repo: 'capylendar' },
};
const updates = [];
const github = { rest: { pulls: { update: async (update) => updates.push(update) } } };
new AsyncFunction('context', 'github', 'console', process.argv[1])(context, github, { log() {} })
    .then(() => process.stdout.write(JSON.stringify(updates)))
    .catch((error) => { console.error(error); process.exitCode = 1; });
JS;
    $process = new Process(['node', '-e', $harness, $script, $branch, json_encode($body, JSON_THROW_ON_ERROR)]);
    $process->mustRun();
    $updates = json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);

    if ($expectedBody === null) {
        expect($updates)->toBe([]);

        return;
    }

    expect($updates)->toBe([[
        'owner' => 'koubevo',
        'repo' => 'capylendar',
        'pull_number' => 93,
        'body' => $expectedBody,
    ]]);
})->with([
    'task branch' => ['CAPY-33-remove-youtrack-integration', 'Description', "# [CAPY-33](https://app.plane.so/vk-personal/browse/CAPY-33)\n\nDescription"],
    'case insensitive legacy branch' => ['VK-capy-33-integration', null, "# [CAPY-33](https://app.plane.so/vk-personal/browse/CAPY-33)\n\n"],
    'already linked' => ['CAPY-33-integration', "# [CAPY-33](https://app.plane.so/vk-personal/browse/CAPY-33)\n\nDescription", null],
    'unrelated branch' => ['dependabot/npm-update', 'Description', null],
]);
