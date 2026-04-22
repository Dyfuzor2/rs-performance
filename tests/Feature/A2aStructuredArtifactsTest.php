<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;

beforeEach(function (): void {
    Cache::flush();
});

it('includes structured JSON artifact for diagnostics intent after task completes', function (): void {
    $response = $this->postJson('/', [
        'jsonrpc' => '2.0',
        'id' => '1',
        'method' => 'message/send',
        'params' => [
            'message' => [
                'role' => 'ROLE_USER',
                'parts' => [['kind' => 'text', 'text' => 'Jaki macie sprzet do diagnostyki?']],
            ],
        ],
    ]);

    $response->assertOk();
    $taskId = $response->json('result.id');
    expect($taskId)->not->toBeEmpty();

    sleep(3);

    $task = $this->getJson("/tasks/{$taskId}");
    $task->assertOk();
    $task->assertJsonPath('status.state', 'TASK_STATE_COMPLETED');

    $artifacts = $task->json('artifacts');
    expect($artifacts)->toBeArray()->not->toBeEmpty();
    $part = $artifacts[0]['parts'][0] ?? [];
    expect($part['kind'] ?? null)->toBe('data');
    expect($part['mediaType'] ?? null)->toBe('application/json');
    $data = is_array($part['data'] ?? null) ? $part['data'] : [];
    expect($data['rs_skill'] ?? null)->toBe('vehicle-diagnostics');
    expect($data['discovery']['agent_card'] ?? '')->toContain('.well-known/agent-card');
});

it('exposes ListTasks via JSON-RPC after SendMessage (A2A Overture list-tasks)', function (): void {
    $this->postJson('/', [
        'jsonrpc' => '2.0',
        'id' => 'lt-1',
        'method' => 'message/send',
        'params' => [
            'message' => [
                'role' => 'ROLE_USER',
                'parts' => [['kind' => 'text', 'text' => 'ListTasks cert ping']],
            ],
        ],
    ])->assertOk();

    $list = $this->postJson('/', [
        'jsonrpc' => '2.0',
        'id' => 'lt-2',
        'method' => 'ListTasks',
        'params' => new stdClass,
    ]);

    $list->assertOk();
    $list->assertJsonStructure([
        'jsonrpc',
        'id',
        'result' => [
            'tasks',
            'totalSize',
            'pageSize',
            'nextPageToken',
        ],
    ]);
    $tasks = $list->json('result.tasks');
    expect($tasks)->toBeArray();
    expect(count($tasks))->toBeGreaterThan(0);
});

it('exposes tasks/list as alias for ListTasks in JSON-RPC', function (): void {
    $r = $this->postJson('/', [
        'jsonrpc' => '2.0',
        'id' => 'lt-3',
        'method' => 'tasks/list',
        'params' => [],
    ]);
    $r->assertOk();
    expect($r->json('result.tasks'))->toBeArray();
});

it('includes structured JSON artifact for general capabilities', function (): void {
    $response = $this->postJson('/', [
        'jsonrpc' => '2.0',
        'id' => '2',
        'method' => 'message/send',
        'params' => [
            'message' => [
                'role' => 'ROLE_USER',
                'parts' => [['kind' => 'text', 'text' => 'Hello, what can you do?']],
            ],
        ],
    ]);

    $response->assertOk();
    $taskId = $response->json('result.id');
    expect($taskId)->not->toBeEmpty();

    sleep(3);

    $task = $this->getJson("/tasks/{$taskId}");
    $task->assertOk();
    $data = $task->json('artifacts.0.parts.0.data');
    expect($data)->toBeArray();
    expect($data['intent'] ?? null)->toBe('general_capabilities');
    expect($data['card_skill_ids'] ?? null)->toContain('dtc-lookup');
    expect($data['support_plane']['rs_skill'] ?? null)->toBe('gateway-semantic-routing');
});
