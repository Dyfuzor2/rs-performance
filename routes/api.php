<?php

use App\Http\Controllers\Api\BlogPipelineController;
use App\Http\Controllers\Api\BlogPostWebhookController;
use App\Http\Controllers\Api\BlogTelegramWebhookController;
use App\Http\Controllers\Api\ContentFeedController;
use App\Http\Controllers\Api\DtcEnrichmentController;
use App\Http\Controllers\Api\KnowledgeSearchController;
use App\Http\Controllers\Api\N8nVertexProxyController;
use App\Http\Controllers\Api\RepairReportMcpController;
use App\Http\Controllers\Api\RepairReportWebhookController;
use App\Http\Controllers\Api\BingInboundLinksController;
use App\Http\Controllers\Api\SearchOpsController;
use App\Http\Controllers\Api\WebVitalsController;
use App\Http\Controllers\McpController;
use Illuminate\Support\Facades\Route;

Route::post('/blog/draft', [BlogPostWebhookController::class, 'store']);
Route::get('/blog/draft/{id}', [BlogPostWebhookController::class, 'show']);
Route::post('/blog/pipeline/run', [BlogPipelineController::class, 'run']);
Route::post('/blog/pipeline/persist', [BlogPipelineController::class, 'persist']);
Route::post('/blog/pipeline/dispatch', [BlogPipelineController::class, 'dispatch']);
Route::post('/blog/telegram/webhook', [BlogTelegramWebhookController::class, 'handle']);
Route::post('/repair-reports', [RepairReportWebhookController::class, 'store']);
Route::get('/repair-reports/{id}', [RepairReportWebhookController::class, 'show']);
Route::get('/mcp/repair-reports/overview', [RepairReportMcpController::class, 'overview']);
Route::get('/mcp/repair-reports/search', [RepairReportMcpController::class, 'search']);
Route::get('/mcp/repair-reports/{identifier}', [RepairReportMcpController::class, 'show']);

Route::get('/content-feed', ContentFeedController::class)
    ->middleware('throttle:60,1');

Route::match(['get', 'post'], '/knowledge/search', KnowledgeSearchController::class)
    ->middleware('throttle:30,1');

Route::post('/messages', [McpController::class, 'handle']);

Route::post('/web-vitals', [WebVitalsController::class, 'store'])
    ->middleware('throttle:60,1');

Route::get('/dtc/batch-for-enrichment', [DtcEnrichmentController::class, 'batch']);
Route::post('/dtc/store-enrichment', [DtcEnrichmentController::class, 'store']);
Route::get('/dtc/enrichment-stats', [DtcEnrichmentController::class, 'stats']);

Route::post('/n8n/vertex/chat', [N8nVertexProxyController::class, 'chat']);
Route::post('/n8n/vertex/generate-content', [N8nVertexProxyController::class, 'generateContent']);
Route::post('/search-ops/fetch-signals', [SearchOpsController::class, 'fetchSignals']);
Route::post('/search-ops/harvest', [SearchOpsController::class, 'harvest']);
Route::get('/search-ops/opportunities', [SearchOpsController::class, 'opportunities']);
Route::post('/search-ops/promote', [SearchOpsController::class, 'promote']);
Route::post('/ops/gsc-status/sync', [SearchOpsController::class, 'syncGscStatus'])
    ->middleware('throttle:30,1');
Route::post('/ops/bing-inbound-links/sync', [BingInboundLinksController::class, 'sync'])
    ->middleware('throttle:30,1');
