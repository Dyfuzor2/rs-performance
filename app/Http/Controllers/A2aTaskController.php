<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\RsUri;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class A2aTaskController
{
    private const TASK_TTL_HOURS = 6;
    private const TASK_INDEX_CACHE_KEY = 'a2a:tasks:index';
    private const TASK_PENDING_SECONDS = 2;
    private const ELECTRIFIED_LANE_KEYWORDS = [
        'elektryczne' => [
            'ev',
            'elektrycz',
            'bateria trakcyjn',
            'bms',
            'ladowan',
            'ładowan',
            '400v',
            '800v',
            'rekuper',
            'inwerter',
            'thermal',
            'hv',
        ],
        'hybrydy' => [
            'hybryd',
            'plug-in',
            'plug in',
            'plugin hybrid',
            'phev',
            'hev',
            'naped miesz',
            'power split',
            'e-cvt',
        ],
    ];
    private const ELECTRIFIED_TOPIC_KEYWORDS = [
        'elektryczne' => [
            'bateria-trakcyjna-i-bms' => [
                'bateria trakcyjn',
                'bms',
                'soh',
                'soc',
                'cell balancing',
                'balans cel',
                'degradac',
                'modul bater',
            ],
            'ladowanie-ac-dc' => [
                'ladowan',
                'ładowan',
                'ac',
                'dc',
                'precondition',
                'evse',
                'charge curve',
                'on-board charger',
                'on board charger',
            ],
            'architektura-400v-vs-800v' => [
                '400v',
                '800v',
                'dc-dc',
                'dc dc',
                'high-voltage',
                'high voltage',
                'platforma 800',
                'platforma 400',
                'inwerter',
            ],
        ],
        'hybrydy' => [
            'hev-vs-phev-bez-skrotow-myslowych' => [
                'hev',
                'phev',
                'plug-in',
                'plug in',
                'plugin hybrid',
                'self charging hybrid',
            ],
            'bateria-hybrydowa-i-zarzadzanie-energia' => [
                'bateria hybryd',
                'zarzadzanie energia',
                'state of charge',
                'power split',
                'battery buffer',
                'bufor energ',
            ],
            'diagnostyka-napedu-mieszanego' => [
                'naped miesz',
                'diagnostyka hybryd',
                'rekuper',
                'e-cvt',
                'hybrid control',
                'mixed drive',
                'power split',
            ],
        ],
    ];
    private const TERMINAL_STATES = [
        'TASK_STATE_COMPLETED',
        'TASK_STATE_CANCELED',
        'TASK_STATE_FAILED',
        'TASK_STATE_REJECTED',
    ];

    public function handle(Request $request): JsonResponse|StreamedResponse
    {
        $body = $request->json()->all();

        if (isset($body['message']) && ! isset($body['jsonrpc'], $body['method'])) {
            return $this->handleRestSend($request);
        }

        if (($body['jsonrpc'] ?? '') !== '2.0' || empty($body['method'])) {
            return $this->jsonRpcError($body['id'] ?? null, -32600, 'Invalid JSON-RPC: require jsonrpc "2.0" and a non-empty method field.');
        }

        $id = $body['id'] ?? null;
        $method = (string) $body['method'];
        $params = is_array($body['params'] ?? null) ? $body['params'] : [];

        return match ($method) {
            'message/send', 'SendMessage' => $this->handleSendMessage($id, $params),
            'message/stream', 'SendStreamingMessage' => $this->handleJsonRpcStream($id, $params),
            'tasks/get', 'GetTask' => $this->handleGetTask($id, $params),
            'tasks/list', 'ListTasks' => $this->handleListTasks($id, $params),
            'tasks/cancel', 'CancelTask' => $this->handleCancelTask($id, $params),
            'tasks/subscribe', 'tasks/resubscribe', 'SubscribeToTask' => $this->handleJsonRpcSubscribe($id, $params),
            default => $this->jsonRpcError($id, -32601, "Unknown method \"{$method}\". Supported: message/send, message/stream, tasks/get, tasks/list, tasks/cancel, tasks/subscribe; legacy aliases: SendMessage, SendStreamingMessage, GetTask, ListTasks, CancelTask, SubscribeToTask."),
        };
    }

    public function handleRestSend(Request $request): JsonResponse
    {
        $payload = $request->json()->all();

        if ($this->hasUnsupportedPushConfig($payload['configuration'] ?? null)) {
            return $this->httpError('PushNotificationNotSupportedError', 'Push notifications are not supported by this agent.', 400);
        }

        $message = is_array($payload['message'] ?? null) ? $payload['message'] : [];
        $task = $this->buildTask(
            $message,
            is_array($payload['metadata'] ?? null) ? $payload['metadata'] : [],
            is_array($payload['configuration'] ?? null) ? $payload['configuration'] : []
        );

        if ($task === null) {
            return $this->httpError('InvalidRequestError', 'No text content in message', 400);
        }

        return response()->json(['task' => $this->taskSnapshot($task)]);
    }

    public function handleRestStream(Request $request): JsonResponse|StreamedResponse
    {
        $payload = $request->json()->all();

        if ($this->hasUnsupportedPushConfig($payload['configuration'] ?? null)) {
            return $this->httpError('PushNotificationNotSupportedError', 'Push notifications are not supported by this agent.', 400);
        }

        $message = is_array($payload['message'] ?? null) ? $payload['message'] : [];
        $task = $this->buildTask(
            $message,
            is_array($payload['metadata'] ?? null) ? $payload['metadata'] : [],
            is_array($payload['configuration'] ?? null) ? $payload['configuration'] : []
        );

        if ($task === null) {
            return $this->httpError('InvalidRequestError', 'No text content in message', 400);
        }

        return $this->streamTaskLifecycle((string) $task['id']);
    }

    public function getTask(string $taskId): JsonResponse
    {
        $task = $this->loadTask($taskId);

        if (! is_array($task)) {
            return $this->httpError('TaskNotFoundError', 'Task not found', 404);
        }

        return response()->json($this->taskSnapshot($task, request()->query('historyLength')));
    }

    public function listTasks(): JsonResponse
    {
        return response()->json($this->buildListTasksResult(
            (string) request()->query('status', ''),
            (string) request()->query('contextId', ''),
            request()->query('historyLength'),
            max(1, min(100, (int) request()->query('pageSize', 50)))
        ));
    }

    /**
     * @return array{tasks: list<array<string, mixed>>, totalSize: int, pageSize: int, nextPageToken: string}
     */
    private function buildListTasksResult(
        string $statusFilter,
        string $contextFilter,
        mixed $historyLength,
        int $pageSize,
    ): array {
        $index = array_map('strval', Cache::get(self::TASK_INDEX_CACHE_KEY, []));
        $tasks = [];

        foreach ($index as $taskId) {
            $task = $this->loadTask($taskId);

            if (! is_array($task)) {
                continue;
            }

            if ($statusFilter !== '' && (string) ($task['status']['state'] ?? '') !== $statusFilter) {
                continue;
            }

            if ($contextFilter !== '' && (string) ($task['contextId'] ?? '') !== $contextFilter) {
                continue;
            }

            $tasks[] = $this->taskSnapshot($task, $historyLength);
        }

        usort($tasks, function (array $left, array $right): int {
            return strcmp(
                (string) ($right['status']['timestamp'] ?? ''),
                (string) ($left['status']['timestamp'] ?? '')
            );
        });

        $tasks = array_slice($tasks, 0, $pageSize);

        return [
            'tasks' => $tasks,
            'totalSize' => count($tasks),
            'pageSize' => $pageSize,
            'nextPageToken' => '',
        ];
    }

    private function handleListTasks(mixed $id, array $params): JsonResponse
    {
        $result = $this->buildListTasksResult(
            (string) ($params['status'] ?? ''),
            (string) ($params['contextId'] ?? ''),
            $params['historyLength'] ?? null,
            max(1, min(100, (int) ($params['pageSize'] ?? 50)))
        );

        return response()->json([
            'jsonrpc' => '2.0',
            'id' => $id,
            'result' => $result,
        ]);
    }

    public function cancelTask(string $taskId): JsonResponse
    {
        $task = Cache::get($this->taskCacheKey($taskId));

        if (! is_array($task)) {
            return $this->httpError('TaskNotFoundError', 'Task not found', 404);
        }

        $state = (string) ($task['status']['state'] ?? '');

        if ($state === 'TASK_STATE_CANCELED') {
            return response()->json($this->taskSnapshot($task));
        }

        if (in_array($state, self::TERMINAL_STATES, true)) {
            return $this->httpError('TaskNotCancelableError', 'Task is already in a terminal state', 409);
        }

        $task = $this->markTaskCanceled($task);
        $this->storeTask($task);

        return response()->json($this->taskSnapshot($task));
    }

    public function subscribeTask(string $taskId): JsonResponse|StreamedResponse
    {
        $task = Cache::get($this->taskCacheKey($taskId));

        if (! is_array($task)) {
            return $this->httpError('TaskNotFoundError', 'Task not found', 404);
        }

        if (in_array((string) ($task['status']['state'] ?? ''), self::TERMINAL_STATES, true)) {
            return $this->httpError('UnsupportedOperationError', 'SubscribeToTask requires a non-terminal task.', 409);
        }

        return $this->streamTaskLifecycle($taskId);
    }

    private function handleSendMessage(mixed $id, array $params): JsonResponse
    {
        if ($this->hasUnsupportedPushConfig($params['configuration'] ?? null)) {
            return $this->jsonRpcError($id, -32003, 'Push and webhook delivery are not supported; omit pushConfig or equivalent from configuration.');
        }

        $message = is_array($params['message'] ?? null) ? $params['message'] : [];
        $task = $this->buildTask(
            $message,
            is_array($params['metadata'] ?? null) ? $params['metadata'] : [],
            is_array($params['configuration'] ?? null) ? $params['configuration'] : []
        );

        if ($task === null) {
            return $this->jsonRpcError($id, -32602, 'No user-visible text: include a message part with kind "text" and non-empty text.');
        }

        return response()->json([
            'jsonrpc' => '2.0',
            'id' => $id,
            'result' => $this->taskSnapshot($task),
        ]);
    }

    private function handleJsonRpcStream(mixed $id, array $params): JsonResponse|StreamedResponse
    {
        if ($this->hasUnsupportedPushConfig($params['configuration'] ?? null)) {
            return $this->jsonRpcError($id, -32003, 'Push and webhook delivery are not supported; omit pushConfig or equivalent from configuration.');
        }

        $message = is_array($params['message'] ?? null) ? $params['message'] : [];
        $task = $this->buildTask(
            $message,
            is_array($params['metadata'] ?? null) ? $params['metadata'] : [],
            is_array($params['configuration'] ?? null) ? $params['configuration'] : []
        );

        if ($task === null) {
            return $this->jsonRpcError($id, -32602, 'No user-visible text: include a message part with kind "text" and non-empty text.');
        }

        return $this->streamTaskLifecycle((string) $task['id'], true, $id);
    }

    private function handleGetTask(mixed $id, array $params): JsonResponse
    {
        $taskId = (string) ($params['id'] ?? $params['taskId'] ?? '');
        $task = $taskId !== '' ? $this->loadTask($taskId) : null;

        if (! is_array($task)) {
            return $this->jsonRpcError($id, -32001, 'Task not found or expired; IDs are short-lived (see agent card / operator hints for typical retention).');
        }

        return response()->json([
            'jsonrpc' => '2.0',
            'id' => $id,
            'result' => $this->taskSnapshot($task, $params['historyLength'] ?? null),
        ]);
    }

    private function handleCancelTask(mixed $id, array $params): JsonResponse
    {
        $taskId = (string) ($params['id'] ?? $params['taskId'] ?? '');
        $task = $taskId !== '' ? Cache::get($this->taskCacheKey($taskId)) : null;

        if (! is_array($task)) {
            return $this->jsonRpcError($id, -32001, 'Task not found or expired; IDs are short-lived (see agent card / operator hints for typical retention).');
        }

        $state = (string) ($task['status']['state'] ?? '');

        if ($state === 'TASK_STATE_CANCELED') {
            return response()->json([
                'jsonrpc' => '2.0',
                'id' => $id,
                'result' => $this->taskSnapshot($task),
            ]);
        }

        if (in_array($state, self::TERMINAL_STATES, true)) {
            return $this->jsonRpcError($id, -32002, 'Task is already finished (completed, failed, rejected, or canceled); cancel is not applicable.');
        }

        $task = $this->markTaskCanceled($task);
        $this->storeTask($task);

        return response()->json([
            'jsonrpc' => '2.0',
            'id' => $id,
            'result' => $this->taskSnapshot($task),
        ]);
    }

    private function handleJsonRpcSubscribe(mixed $id, array $params): JsonResponse|StreamedResponse
    {
        $taskId = (string) ($params['id'] ?? $params['taskId'] ?? '');
        $task = $taskId !== '' ? Cache::get($this->taskCacheKey($taskId)) : null;

        if (! is_array($task)) {
            return $this->jsonRpcError($id, -32001, 'Task not found or expired; IDs are short-lived (see agent card / operator hints for typical retention).');
        }

        if (in_array((string) ($task['status']['state'] ?? ''), self::TERMINAL_STATES, true)) {
            return $this->jsonRpcError($id, -32004, 'Subscribe or stream only while the task is non-terminal; this task is already completed, failed, rejected, or canceled—use tasks/get or create a new message.');
        }

        return $this->streamTaskLifecycle($taskId, true, $id);
    }

    private function buildTask(array $message, array $metadata = [], array $configuration = []): ?array
    {
        $userText = $this->extractUserText(is_array($message['parts'] ?? null) ? $message['parts'] : []);

        if ($userText === '') {
            return null;
        }

        $continuationTaskId = (string) ($message['taskId'] ?? $metadata['taskId'] ?? '');
        $existingTask = $continuationTaskId !== '' ? Cache::get($this->taskCacheKey($continuationTaskId)) : null;
        $response = $this->routeToSkill($userText);

        $contextId = is_array($existingTask)
            ? (string) ($existingTask['contextId'] ?? $continuationTaskId)
            : (string) ($message['contextId'] ?? $metadata['contextId'] ?? bin2hex(random_bytes(16)));
        $taskId = is_array($existingTask)
            ? (string) ($existingTask['id'] ?? $continuationTaskId)
            : bin2hex(random_bytes(16));

        $userMessage = [
            'kind' => 'message',
            'messageId' => (string) ($message['messageId'] ?? bin2hex(random_bytes(16))),
            'role' => (string) ($message['role'] ?? 'ROLE_USER'),
            'parts' => $message['parts'] ?? [[
                'kind' => 'text',
                'text' => $userText,
            ]],
        ];

        $history = is_array($existingTask)
            ? (array) ($existingTask['history'] ?? [])
            : (array) Cache::get($this->contextCacheKey($contextId), []);
        $history[] = $userMessage;

        $workingMessage = $this->makeAgentMessage('Przetwarzam zapytanie RS Performance...');

        $task = is_array($existingTask) ? $existingTask : [
            'id' => $taskId,
            'contextId' => $contextId,
            'kind' => 'task',
            'artifacts' => [],
            'history' => [],
            'metadata' => [],
        ];

        $task['id'] = $taskId;
        $task['contextId'] = $contextId;
        $task['kind'] = 'task';
        $task['status'] = [
            'state' => 'TASK_STATE_WORKING',
            'timestamp' => now()->toIso8601String(),
            'message' => $workingMessage,
        ];
        $task['artifacts'] = [];
        $task['history'] = $history;
        $task['metadata'] = array_merge(
            (array) ($task['metadata'] ?? []),
            [
                'provider' => 'rsperformance.online',
                'binding_compatibility' => ['JSONRPC', 'HTTP+JSON'],
            ]
        );
        $task['_pending_response'] = $response;
        $task['_ready_at'] = now()->addSeconds(self::TASK_PENDING_SECONDS)->toIso8601String();

        if (! empty($configuration)) {
            $task['metadata']['last_configuration'] = $configuration;
        }

        $this->storeTask($task);

        return $task;
    }

    private function loadTask(string $taskId): ?array
    {
        $task = Cache::get($this->taskCacheKey($taskId));

        if (! is_array($task)) {
            return null;
        }

        $task = $this->resolveTask($task);
        $this->storeTask($task);

        return $task;
    }

    private function resolveTask(array $task): array
    {
        $state = (string) ($task['status']['state'] ?? '');

        if (in_array($state, self::TERMINAL_STATES, true)) {
            return $task;
        }

        $readyAt = (string) ($task['_ready_at'] ?? '');

        if ($readyAt === '' || strtotime($readyAt) === false || strtotime($readyAt) > time()) {
            return $task;
        }

        $response = is_array($task['_pending_response'] ?? null) ? $task['_pending_response'] : null;

        if ($response === null) {
            return $task;
        }

        $agentMessage = $this->makeAgentMessage((string) ($response['text'] ?? ''));
        $task['status'] = [
            'state' => 'TASK_STATE_COMPLETED',
            'timestamp' => now()->toIso8601String(),
            'message' => $agentMessage,
        ];
        $task['artifacts'] = array_values(array_filter((array) ($response['artifacts'] ?? []), 'is_array'));
        $task['history'] = array_merge((array) ($task['history'] ?? []), [$agentMessage]);

        unset($task['_pending_response'], $task['_ready_at']);

        return $task;
    }

    private function markTaskCanceled(array $task): array
    {
        $agentMessage = $this->makeAgentMessage('Task canceled by client request.');
        $task['status'] = [
            'state' => 'TASK_STATE_CANCELED',
            'timestamp' => now()->toIso8601String(),
            'message' => $agentMessage,
        ];
        $task['history'] = array_merge((array) ($task['history'] ?? []), [$agentMessage]);

        unset($task['_pending_response'], $task['_ready_at']);

        return $task;
    }

    private function storeTask(array $task): void
    {
        Cache::put($this->taskCacheKey((string) $task['id']), $task, now()->addHours(self::TASK_TTL_HOURS));
        Cache::put($this->contextCacheKey((string) $task['contextId']), (array) ($task['history'] ?? []), now()->addHours(self::TASK_TTL_HOURS));

        $index = Cache::get(self::TASK_INDEX_CACHE_KEY, []);
        $index = array_values(array_unique(array_merge([(string) $task['id']], array_map('strval', (array) $index))));
        Cache::put(self::TASK_INDEX_CACHE_KEY, array_slice($index, 0, 100), now()->addHours(self::TASK_TTL_HOURS));
    }

    private function taskSnapshot(array $task, mixed $historyLength = null): array
    {
        $snapshot = $this->sanitizeTask($task);

        return $this->applyHistoryWindow($snapshot, $historyLength);
    }

    private function sanitizeTask(array $task): array
    {
        unset($task['_pending_response'], $task['_ready_at']);

        return $task;
    }

    private function streamTaskLifecycle(string $taskId, bool $jsonRpc = false, mixed $id = null): StreamedResponse
    {
        return response()->stream(function () use ($taskId, $jsonRpc, $id): void {
            $previous = null;

            while (true) {
                $task = $this->loadTask($taskId);

                if (! is_array($task)) {
                    break;
                }

                $snapshot = $this->taskSnapshot($task);

                if ($previous === null) {
                    $this->writeSse($jsonRpc ? ['jsonrpc' => '2.0', 'id' => $id, 'result' => ['task' => $snapshot]] : ['task' => $snapshot]);
                    $previous = $snapshot;
                } else {
                    $previousArtifacts = (array) ($previous['artifacts'] ?? []);
                    $currentArtifacts = (array) ($snapshot['artifacts'] ?? []);

                    if (count($currentArtifacts) > count($previousArtifacts)) {
                        foreach (array_slice($currentArtifacts, count($previousArtifacts)) as $artifact) {
                            $payload = ['artifactUpdate' => [
                                'taskId' => $taskId,
                                'contextId' => $snapshot['contextId'] ?? null,
                                'artifact' => $artifact,
                            ]];
                            $this->writeSse($jsonRpc ? ['jsonrpc' => '2.0', 'id' => $id, 'result' => $payload] : $payload);
                        }
                    }

                    if (($previous['status']['state'] ?? null) !== ($snapshot['status']['state'] ?? null)) {
                        $payload = ['statusUpdate' => [
                            'taskId' => $taskId,
                            'contextId' => $snapshot['contextId'] ?? null,
                            'status' => $snapshot['status'],
                            'final' => in_array((string) ($snapshot['status']['state'] ?? ''), self::TERMINAL_STATES, true),
                        ]];
                        $this->writeSse($jsonRpc ? ['jsonrpc' => '2.0', 'id' => $id, 'result' => $payload] : $payload);
                    }

                    $previous = $snapshot;
                }

                if (in_array((string) ($snapshot['status']['state'] ?? ''), self::TERMINAL_STATES, true)) {
                    break;
                }

                usleep(250000);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
            'A2A-Version' => '1.0',
        ]);
    }

    private function writeSse(array $payload): void
    {
        echo 'data: ' . json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n\n";

        if (function_exists('ob_flush')) {
            @ob_flush();
        }

        flush();
    }

    private function makeAgentMessage(string $text): array
    {
        return [
            'kind' => 'message',
            'messageId' => bin2hex(random_bytes(16)),
            'role' => 'ROLE_AGENT',
            'parts' => [[
                'kind' => 'text',
                'text' => $text,
            ]],
        ];
    }

    private function hasUnsupportedPushConfig(mixed $configuration): bool
    {
        if (! is_array($configuration)) {
            return false;
        }

        return is_array($configuration['taskPushNotificationConfig'] ?? null)
            || is_array($configuration['pushNotificationConfig'] ?? null);
    }

    private function extractUserText(array $parts): string
    {
        $textParts = array_filter($parts, function ($part): bool {
            if (! is_array($part)) {
                return false;
            }

            $kind = (string) ($part['kind'] ?? $part['type'] ?? 'text');

            return $kind === 'text' && filled($part['text'] ?? null);
        });

        return trim(implode(' ', array_map(
            fn(array $part): string => (string) ($part['text'] ?? ''),
            $textParts
        )));
    }

    private function taskCacheKey(string $taskId): string
    {
        return 'a2a:task:' . $taskId;
    }

    private function contextCacheKey(string $contextId): string
    {
        return 'a2a:context:' . $contextId;
    }

    private function routeToSkill(string $userText): array
    {
        if (preg_match('/\b([PCBU]\d{4}[A-F0-9]?)\b/i', $userText, $match)) {
            return $this->dtcLookup(strtoupper($match[1]));
        }

        if (
            preg_match('/\b([0-9A-F]{4})\b/i', $userText, $match)
            && (stripos($userText, 'BMW') !== false || stripos($userText, 'bimmer') !== false)
        ) {
            return $this->dtcLookup(strtoupper($match[1]));
        }

        $lower = mb_strtolower($userText);

        if ($electrifiedMatch = $this->matchElectrifiedKnowledge($lower)) {
            return $this->electrifiedKnowledgeInfo($electrifiedMatch);
        }

        if (
            str_contains($lower, 'diagnostyk')
            || str_contains($lower, 'diagnostic')
            || str_contains($lower, 'sprzet')
            || str_contains($lower, 'sprzęt')
            || str_contains($lower, 'equipment')
        ) {
            return $this->diagnosticsInfo();
        }

        if (
            str_contains($lower, 'rezerwac')
            || str_contains($lower, 'book')
            || str_contains($lower, 'wizyt')
            || str_contains($lower, 'appointment')
            || str_contains($lower, 'kontakt')
            || str_contains($lower, 'contact')
        ) {
            return $this->bookingInfo();
        }

        if (
            str_contains($lower, 'napraw')
            || str_contains($lower, 'repair')
            || str_contains($lower, 'serwis')
            || str_contains($lower, 'service')
        ) {
            return $this->repairInfo();
        }

        return $this->generalInfo();
    }

    /**
     * @return array{lane_key: string, lane: array<string, mixed>, topic_key: string|null, topic: array<string, mixed>|null}|null
     */
    private function matchElectrifiedKnowledge(string $query): ?array
    {
        foreach (self::ELECTRIFIED_TOPIC_KEYWORDS as $laneKey => $topics) {
            foreach ($topics as $topicKey => $keywords) {
                if ($this->containsAnyKeyword($query, $keywords)) {
                    $lane = (array) config('ev_knowledge.lanes.' . $laneKey, []);
                    $topic = (array) config('ev_knowledge.lanes.' . $laneKey . '.topics.' . $topicKey, []);

                    if ($lane !== [] && $topic !== []) {
                        return [
                            'lane_key' => $laneKey,
                            'lane' => $lane,
                            'topic_key' => $topicKey,
                            'topic' => $topic,
                        ];
                    }
                }
            }
        }

        foreach (self::ELECTRIFIED_LANE_KEYWORDS as $laneKey => $keywords) {
            if ($this->containsAnyKeyword($query, $keywords)) {
                $lane = (array) config('ev_knowledge.lanes.' . $laneKey, []);

                if ($lane !== []) {
                    return [
                        'lane_key' => $laneKey,
                        'lane' => $lane,
                        'topic_key' => null,
                        'topic' => null,
                    ];
                }
            }
        }

        return null;
    }

    /**
     * @param  array<int, string>  $keywords
     */
    private function containsAnyKeyword(string $query, array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            $normalizedKeyword = mb_strtolower(trim($keyword));

            if ($normalizedKeyword === '') {
                continue;
            }

            if ($this->keywordMatches($query, $normalizedKeyword)) {
                return true;
            }
        }

        return false;
    }

    private function keywordMatches(string $query, string $keyword): bool
    {
        if (preg_match('/^[a-z0-9-]+$/iu', $keyword) === 1) {
            $pattern = '/(?<![\\p{L}\\p{N}])' . preg_quote($keyword, '/') . '(?![\\p{L}\\p{N}])/iu';

            return preg_match($pattern, $query) === 1;
        }

        return str_contains($query, $keyword);
    }

    /**
     * @param  array{lane_key: string, lane: array<string, mixed>, topic_key: string|null, topic: array<string, mixed>|null}  $match
     * @return array{text: string, artifacts: array<int, array<string, mixed>>}
     */
    private function electrifiedKnowledgeInfo(array $match): array
    {
        $laneKey = $match['lane_key'];
        $lane = $match['lane'];
        $topicKey = $match['topic_key'];
        $topic = $match['topic'];

        $hubUrl = RsUri::evKnowledgeHub();
        $laneUrl = RsUri::evKnowledgeLane($laneKey);
        $laneMarkdown = RsUri::evKnowledgeLaneMarkdown($laneKey);
        $topicUrl = $topicKey !== null ? RsUri::evKnowledgeTopic($laneKey, $topicKey) : null;
        $topicMarkdown = $topicKey !== null ? RsUri::evKnowledgeTopicMarkdown($laneKey, $topicKey) : null;

        $text = $topic !== null
            ? (string) ($topic['quick_answer'] ?? $topic['bluf'] ?? $lane['bluf'] ?? '')
            : (string) ($lane['bluf'] ?? '');

        $lines = [
            trim($text),
            '',
            'Najlepszy kierunek w bazie wiedzy RS Performance:',
            '- Hub EV / hybrid: ' . $hubUrl,
            '- Lane: ' . $laneUrl,
        ];

        if ($topicUrl !== null && $topic !== null) {
            $lines[] = '- Temat: ' . $topicUrl;
        }

        $decisionPoints = (array) ($topic['decision_points'] ?? $lane['what_it_means'] ?? []);
        if ($decisionPoints !== []) {
            $lines[] = '';
            $lines[] = 'Co sprawdzic najpierw:';
            foreach (array_slice(array_values($decisionPoints), 0, 3) as $point) {
                $lines[] = '- ' . (string) $point;
            }
        }

        $workshopChecks = (array) ($topic['workshop_checks'] ?? []);
        if ($workshopChecks !== []) {
            $lines[] = '';
            $lines[] = 'Perspektywa warsztatowa:';
            foreach (array_slice(array_values($workshopChecks), 0, 3) as $check) {
                $lines[] = '- ' . (string) $check;
            }
        }

        $lines[] = '';
        $lines[] = 'Ten lane jest oddzielony od klasycznego DTC hubu. Uzywaj go do pytan o baterie, BMS, ladowanie, 400V/800V, HEV i PHEV.';
        $lines[] = 'Telefon warsztatu: +48 58 552 24 00 | rsperformance.online';

        $artifactPayload = [
            'rs_skill' => 'ev-hybrid-knowledge',
            'intent' => 'electrified-mobility-knowledge',
            'summary' => (string) ($topic['summary'] ?? $lane['summary'] ?? $lane['bluf'] ?? ''),
            'lane' => [
                'key' => $laneKey,
                'title' => (string) ($lane['title'] ?? $laneKey),
                'url' => $laneUrl,
                'markdown_url' => $laneMarkdown,
            ],
            'canonical_paths' => array_filter([
                'hub' => $hubUrl,
                'lane' => $laneUrl,
                'lane_markdown' => $laneMarkdown,
                'topic' => $topicUrl,
                'topic_markdown' => $topicMarkdown,
            ]),
            'decision_points' => array_values(array_map(
                static fn(mixed $point): string => (string) $point,
                array_slice(array_values($decisionPoints), 0, 5)
            )),
            'workshop_checks' => array_values(array_map(
                static fn(mixed $point): string => (string) $point,
                array_slice(array_values($workshopChecks), 0, 5)
            )),
            'support_plane' => [
                'rs_skill' => 'gateway-semantic-routing',
                'semantic_search_url' => RsUri::aiGatewaySemanticSearch(),
                'answer_routing' => RsUri::aiGatewayAnswerRoutingJson(),
                'freshness' => RsUri::aiGatewayFreshnessJson(),
            ],
            'discovery' => array_merge($this->a2aDiscoveryLinks(), [
                'content_index' => RsUri::contentIndexJson(),
                'llms_full' => RsUri::llmsFull(),
            ]),
        ];

        if ($topic !== null && $topicKey !== null) {
            $artifactPayload['topic'] = [
                'key' => $topicKey,
                'title' => (string) ($topic['title'] ?? $topicKey),
                'url' => $topicUrl,
                'markdown_url' => $topicMarkdown,
                'related_terms' => array_values(array_map(
                    static fn(mixed $term): string => (string) $term,
                    (array) ($topic['related_terms'] ?? [])
                )),
            ];
        }

        return [
            'text' => implode("\n", array_filter($lines)),
            'artifacts' => [
                $this->makeStructuredArtifact('RS EV / hybrid knowledge', $artifactPayload),
            ],
        ];
    }

    private function dtcLookup(string $code): array
    {
        $dbPath = storage_path('app/dtc/dtc_complete.db');

        if (! file_exists($dbPath)) {
            return [
                'text' => 'Baza DTC niedostepna. Skontaktuj sie z RS Performance: +48 58 552 24 00',
                'artifacts' => [
                    $this->makeStructuredArtifact('DTC database unavailable', [
                        'rs_skill' => 'dtc-lookup',
                        'found' => false,
                        'error' => 'database_missing',
                        'phone' => '+48 58 552 24 00',
                        'discovery' => $this->a2aDiscoveryLinks(),
                    ]),
                ],
            ];
        }

        $db = new \SQLite3($dbPath);
        $stmt = $db->prepare('SELECT code, manufacturer, description, type, trinity_comment FROM dtc WHERE code = :code LIMIT 5');
        $stmt->bindValue(':code', $code, SQLITE3_TEXT);
        $result = $stmt->execute();

        $entries = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $entries[] = $row;
        }
        $db->close();

        if ($entries === []) {
            return [
                'text' => "Kod {$code} nie zostal znaleziony w bazie RS Performance (21 876 kodow). Sprawdz pisownie lub skontaktuj sie: +48 58 552 24 00.",
                'artifacts' => [
                    $this->makeStructuredArtifact("DTC {$code} not found", [
                        'rs_skill' => 'dtc-lookup',
                        'found' => false,
                        'code' => $code,
                        'canonical_paths' => [
                            'dtc_hub' => RsUri::dtcHub(),
                            'dtc_feed' => RsUri::dtcFeedJson(),
                        ],
                        'phone' => '+48 58 552 24 00',
                        'discovery' => $this->a2aDiscoveryLinks(),
                    ]),
                ],
            ];
        }

        $parts = [];
        foreach ($entries as $entry) {
            $text = "**{$entry['code']}** ({$entry['manufacturer']})\n";
            $text .= "Opis: {$entry['description']}\n";

            if (! empty($entry['trinity_comment'])) {
                $enriched = json_decode((string) $entry['trinity_comment'], true);

                if (is_array($enriched)) {
                    if (! empty($enriched['opis_pl'])) {
                        $text .= "\nDiagnoza: {$enriched['opis_pl']}";
                    }
                    if (! empty($enriched['czy_mozna_jechac'])) {
                        $text .= "\nCzy mozna jezdzic: {$enriched['czy_mozna_jechac']}";
                    }
                    if (! empty($enriched['diy_tip'])) {
                        $text .= "\nDIY: {$enriched['diy_tip']}";
                    }
                    if (! empty($enriched['nie_daj_sie_oszukac'])) {
                        $text .= "\nUwaga: {$enriched['nie_daj_sie_oszukac']}";
                    }
                    if (! empty($enriched['szacunkowy_koszt'])) {
                        $text .= "\nSzacunkowy koszt: {$enriched['szacunkowy_koszt']}";
                    }
                    if (! empty($enriched['heniu_komentarz'])) {
                        $text .= "\n\nHeniu mowi: \"{$enriched['heniu_komentarz']}\"";
                    }
                } else {
                    $text .= "\n" . $entry['trinity_comment'];
                }
            }

            $parts[] = $text;
        }

        $responseText = implode("\n\n---\n\n", $parts);
        $responseText .= "\n\nRS Performance Gdansk | +48 58 552 24 00 | rsperformance.online";

        $artifacts = [];
        if (! empty($entries[0]['trinity_comment'])) {
            $enriched = json_decode((string) $entries[0]['trinity_comment'], true);

            if (is_array($enriched)) {
                $artifacts[] = $this->makeStructuredArtifact("DTC {$code} structured data", array_merge(
                    ['rs_skill' => 'dtc-lookup', 'found' => true, 'code' => $code],
                    $enriched,
                    [
                        'canonical_paths' => [
                            'dtc_html' => RsUri::dtcCode($code),
                            'dtc_json' => RsUri::dtcCodeJson($code),
                            'dtc_hub' => RsUri::dtcHub(),
                        ],
                        'discovery' => $this->a2aDiscoveryLinks(),
                    ]
                ));
            }
        }

        if ($artifacts === []) {
            $row = $entries[0];
            $artifacts[] = $this->makeStructuredArtifact("DTC {$code}", [
                'rs_skill' => 'dtc-lookup',
                'found' => true,
                'code' => $row['code'] ?? $code,
                'manufacturer' => $row['manufacturer'] ?? null,
                'description' => $row['description'] ?? null,
                'type' => $row['type'] ?? null,
                'canonical_paths' => [
                    'dtc_html' => RsUri::dtcCode($code),
                    'dtc_json' => RsUri::dtcCodeJson($code),
                    'dtc_hub' => RsUri::dtcHub(),
                ],
                'discovery' => $this->a2aDiscoveryLinks(),
            ]);
        }

        return ['text' => $responseText, 'artifacts' => $artifacts];
    }

    private function diagnosticsInfo(): array
    {
        $text = "RS Performance - Diagnostyka komputerowa pojazdow\n\n"
            . "Sprzet:\n"
            . "- Bosch KTS 560 (Mistrz) - pelna diagnostyka wielomarkowa\n"
            . "- Ross-Tech VCDS - specjalista VAG (VW, Audi, Skoda, Seat)\n"
            . "- Bosch PDL 4000 - diagnostyka pojazdow ciezarowych\n"
            . "- Bosch CDIF 3 - interfejs diagnostyczny\n\n"
            . "Uslugi: odczyt i kasowanie bledow, kodowanie modulow, programowanie ECU, adaptacje i testy aktorow.\n\n"
            . 'Telefon: +48 58 552 24 00 | rsperformance.online';

        return [
            'text' => $text,
            'artifacts' => [
                $this->makeStructuredArtifact('RS vehicle-diagnostics', [
                    'rs_skill' => 'vehicle-diagnostics',
                    'summary' => 'Diagnostyka komputerowa: Bosch KTS 560, VCDS, PDL 4000, CDIF 3; odczyt i kasowanie bledow, kodowanie modulow, programowanie ECU, adaptacje.',
                    'equipment' => [
                        ['name' => 'Bosch KTS 560', 'role' => 'Diagnostyka wielomarkowa'],
                        ['name' => 'Ross-Tech VCDS', 'role' => 'VAG (VW, Audi, Skoda, Seat)'],
                        ['name' => 'Bosch PDL 4000', 'role' => 'Pojazdy ciezarowe'],
                        ['name' => 'Bosch CDIF 3', 'role' => 'Interfejs diagnostyczny'],
                    ],
                    'canonical_paths' => [
                        'services' => RsUri::serviceHub(),
                        'dtc_hub' => RsUri::dtcHub(),
                        'home' => RsUri::home(),
                    ],
                    'phone' => '+48 58 552 24 00',
                    'discovery' => $this->a2aDiscoveryLinks(),
                ]),
            ],
        ];
    }

    private function bookingInfo(): array
    {
        $text = "Rezerwacja wizyty w RS Performance\n\n"
            . "Proces:\n1. Kontakt telefoniczny lub formularz online\n2. Wywiad wstepny\n3. Ustalenie terminu\n4. Diagnostyka -> Wycena -> Naprawa\n\n"
            . "Telefon: +48 58 552 24 00\n"
            . "Komorka: +48 601 338 001\n"
            . "Email: biuro@rsperformance.online\n"
            . "Online: rsperformance.online/rezerwacja\n"
            . 'Lokalizacja: Gdansk, Trojmiasto';

        return [
            'text' => $text,
            'artifacts' => [
                $this->makeStructuredArtifact('RS booking', [
                    'rs_skill' => 'booking',
                    'summary' => 'Rezerwacja wizyty: kontakt telefoniczny / online, wywiad, termin, diagnostyka i naprawa.',
                    'contact' => [
                        'phone' => '+48 58 552 24 00',
                        'mobile' => '+48 601 338 001',
                        'email' => 'biuro@rsperformance.online',
                    ],
                    'location' => 'Gdansk, Trojmiasto',
                    'canonical_paths' => [
                        'home' => RsUri::home(),
                        'booking_page' => RsUri::path('/rezerwacja'),
                        'services' => RsUri::serviceHub(),
                    ],
                    'discovery' => $this->a2aDiscoveryLinks(),
                ]),
            ],
        ];
    }

    private function repairInfo(): array
    {
        $text = "RS Performance - Uslugi naprawcze\n\n"
            . "- Mechanika silnikowa (benzyna, diesel, hybryda)\n"
            . "- Skrzynie biegow (manualne, automatyczne, DSG)\n"
            . "- Zawieszenie i geometria kol\n"
            . "- Uklad hamulcowy\n"
            . "- Klimatyzacja (serwis, napelnianie, wykrywanie nieszczelnosci)\n"
            . "- Turbosprezarki\n"
            . "- DPF / EGR / AdBlue\n"
            . "- Elektronika pojazdowa\n"
            . "- Floty B2B (50+ pojazdow)\n\n"
            . "Proces: Wywiad -> Diagnostyka -> Wycena -> Naprawa\n"
            . 'Telefon: +48 58 552 24 00 | rsperformance.online';

        return [
            'text' => $text,
            'artifacts' => [
                $this->makeStructuredArtifact('RS repair-services', [
                    'rs_skill' => 'repair-services',
                    'summary' => 'Mechanika, skrzynie, zawieszenie, hamulce, klimatyzacja, turbo, DPF/EGR/AdBlue, elektronika, floty B2B.',
                    'service_lines' => [
                        'mechanika_silnikowa',
                        'skrzynie_biegow',
                        'zawieszenie_geometria',
                        'hamulce',
                        'klimatyzacja',
                        'turbo',
                        'dpf_egr_adblue',
                        'elektronika',
                        'floty_b2b',
                    ],
                    'canonical_paths' => [
                        'services' => RsUri::serviceHub(),
                        'repair_reports' => RsUri::repairReportsHub(),
                        'problems' => RsUri::problemHub(),
                        'home' => RsUri::home(),
                    ],
                    'phone' => '+48 58 552 24 00',
                    'discovery' => $this->a2aDiscoveryLinks(),
                ]),
            ],
        ];
    }

    private function generalInfo(): array
    {
        $text = "RS Performance Gdansk - serwis diagnostyki i napraw samochodow.\n\n"
            . "Moge pomoc z:\n"
            . "- lookupem kodow DTC (np. Co oznacza P0420?)\n"
            . "- informacjami o diagnostyce i sprzecie\n"
            . "- informacjami o uslugach naprawczych\n"
            . "- wiedza o EV, bateriach, BMS, ladowaniu, HEV i PHEV\n"
            . "- rezerwacja wizyty\n\n"
            . "Podaj kod bledu OBD-II (np. P0301, U0100, C1111), a sprawdze go w bazie 21 876 kodow.\n\n"
            . 'Telefon: +48 58 552 24 00 | rsperformance.online';

        return [
            'text' => $text,
            'artifacts' => [
                $this->makeStructuredArtifact('RS A2A capabilities', [
                    'intent' => 'general_capabilities',
                    'summary' => 'Agent RS Performance: DTC, diagnostyka warsztatowa, naprawy, EV i hybrid knowledge, rezerwacja; baza 21 876 kodow.',
                    'card_skill_ids' => [
                        'dtc-lookup',
                        'vehicle-diagnostics',
                        'repair-services',
                        'ev-hybrid-knowledge',
                        'booking',
                        'gateway-semantic-routing',
                    ],
                    'support_plane' => [
                        'rs_skill' => 'gateway-semantic-routing',
                        'semantic_search_url' => RsUri::aiGatewaySemanticSearch(),
                        'answer_routing' => RsUri::aiGatewayAnswerRoutingJson(),
                        'freshness' => RsUri::aiGatewayFreshnessJson(),
                    ],
                    'canonical_paths' => [
                        'home' => RsUri::home(),
                        'dtc_hub' => RsUri::dtcHub(),
                        'services' => RsUri::serviceHub(),
                        'ev_hybrid_hub' => RsUri::evKnowledgeHub(),
                    ],
                    'phone' => '+48 58 552 24 00',
                    'discovery' => $this->a2aDiscoveryLinks(),
                ]),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function makeStructuredArtifact(string $displayName, array $data): array
    {
        return [
            'artifactId' => bin2hex(random_bytes(8)),
            'name' => $displayName,
            'parts' => [[
                'kind' => 'data',
                'mediaType' => 'application/json',
                'data' => $data,
            ]],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function a2aDiscoveryLinks(): array
    {
        return [
            'a2a_json' => RsUri::a2aJson(),
            'agent_card' => RsUri::a2aAgentCard(),
            'ai_resources' => RsUri::aiResourcesJson(),
        ];
    }

    private function jsonRpcError(mixed $id, int $code, string $message): JsonResponse
    {
        return response()->json([
            'jsonrpc' => '2.0',
            'id' => $id,
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ], $code === -32001 ? 404 : 200);
    }

    /**
     * @param  array<string, mixed>  $task
     * @return array<string, mixed>
     */
    private function applyHistoryWindow(array $task, mixed $historyLength): array
    {
        $historyLength = is_numeric($historyLength) ? (int) $historyLength : null;

        if ($historyLength === null) {
            return $task;
        }

        if ($historyLength <= 0) {
            unset($task['history']);

            return $task;
        }

        $task['history'] = array_slice((array) ($task['history'] ?? []), -$historyLength);

        return $task;
    }

    private function httpError(string $code, string $message, int $status): JsonResponse
    {
        return response()->json([
            'code' => $code,
            'message' => $message,
        ], $status);
    }
}
