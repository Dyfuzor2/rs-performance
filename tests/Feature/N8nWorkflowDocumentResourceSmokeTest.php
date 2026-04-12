<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\N8nWorkflowCategory;
use App\Models\N8nWorkflowDocument;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class N8nWorkflowDocumentResourceSmokeTest extends TestCase
{
    public function test_root_can_open_n8n_workflow_hub_index(): void
    {
        /** @var User $user */
        $user = User::query()->where('role', 'root')->firstOrFail();

        N8nWorkflowDocument::query()->create([
            'name' => 'Smoke trigger row',
            'slug' => 'smoke-trigger-row-' . uniqid('', true),
            'n8n_workflow_id' => 'smokeWfId1',
            'category' => N8nWorkflowCategory::Ops,
            'is_visible' => true,
            'sort_order' => 0,
        ]);

        $this->actingAs($user)->get('/admin/n8n-workflow-documents')
            ->assertOk()
            ->assertSee('WOW Ops Hub', false)
            ->assertSee('Synchronizuj z n8n', false)
            ->assertSee('Test API n8n', false)
            ->assertSee('API n8n', false)
            ->assertSee('Wyzwól', false);
    }

    public function test_non_root_cannot_open_n8n_workflow_hub(): void
    {
        /** @var User $user */
        $user = User::query()->firstOrCreate(
            ['email' => 'admin-n8n-hub-smoke@rsperformance.test'],
            [
                'name' => 'Admin smoke',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ]
        );

        $this->actingAs($user)->get('/admin/n8n-workflow-documents')
            ->assertForbidden();
    }
}
