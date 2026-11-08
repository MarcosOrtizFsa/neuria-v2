<?php

namespace Tests\Feature;

use App\Models\AiUsage;
use App\Models\ApiUsageLog;
use App\Models\AutomationBudget;
use App\Models\MediaApiCredential;
use App\Models\MediaOutlet;
use App\Models\NewsRequest;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ClientRequestConsumptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_sees_one_grouped_row_with_aggregated_tokens_and_api_calls(): void
    {
        [$client, $organization, $media, $credential] = $this->clientContext();
        AutomationBudget::factory()->for($organization)->create([
            'monthly_limit_minor' => 1000000,
            'reserved_minor' => 100000,
            'settled_minor' => 250000,
            'period_start' => now()->startOfMonth(),
        ]);
        $newsRequest = $this->newsRequest($client, $media, 'Producción agrupada para ISFD');
        $this->usage($newsRequest, 'INVESTIGACION', 100, 50);
        $this->usage($newsRequest, 'REDACCION', 120, 80);
        $this->apiLog($newsRequest, $media, $credential, 'api.v1.requests.store');
        $this->apiLog($newsRequest, $media, $credential, 'api.v1.requests.show');
        $this->apiLog($newsRequest, $media, $credential, 'api.v1.contents.show');

        $response = $this->actingAs($client)->get(route('admin.solicitudes-consumos.index'));

        $response->assertOk()
            ->assertSee($newsRequest->reference_code)
            ->assertSee('Producción agrupada para ISFD')
            ->assertSee('350')
            ->assertSee('ARS 10.000,00')
            ->assertSee('3');
        $this->assertSame(1, substr_count($response->getContent(), 'Producción agrupada para ISFD'));
    }

    public function test_client_cannot_see_or_open_another_organizations_request(): void
    {
        [$client] = $this->clientContext();
        [$otherClient, , $otherMedia] = $this->clientContext();
        $otherRequest = $this->newsRequest($otherClient, $otherMedia, 'Solicitud privada de otra organización');
        $this->usage($otherRequest, 'INVESTIGACION', 900, 99);

        $this->actingAs($client)->get(route('admin.solicitudes-consumos.index'))
            ->assertOk()
            ->assertDontSee('Solicitud privada de otra organización')
            ->assertDontSee('999');

        $this->actingAs($client)->get(route('admin.solicitudes-consumos.show', $otherRequest->reference_code))
            ->assertNotFound();
    }

    public function test_client_can_review_ai_usage_and_api_activity_for_own_request(): void
    {
        [$client, , $media, $credential] = $this->clientContext();
        $newsRequest = $this->newsRequest($client, $media, 'Detalle técnico visible');
        $this->usage($newsRequest, 'REDACCION', 200, 125);
        $this->apiLog($newsRequest, $media, $credential, 'api.v1.requests.show');

        $this->actingAs($client)->get(route('admin.solicitudes-consumos.show', $newsRequest->reference_code))
            ->assertOk()
            ->assertSee($newsRequest->reference_code)
            ->assertSee('Detalle técnico visible')
            ->assertSee('325')
            ->assertSee('REDACCION')
            ->assertSee('api.v1.requests.show');
    }

    public function test_invalid_month_filter_is_rejected(): void
    {
        [$client] = $this->clientContext();

        $this->actingAs($client)->get(route('admin.solicitudes-consumos.index', ['month' => '2026-99']))
            ->assertSessionHasErrors('month');
    }

    /**
     * @return array{User, Organization, MediaOutlet, MediaApiCredential}
     */
    private function clientContext(): array
    {
        $role = Role::firstOrCreate(['code' => 'CLIENTE'], ['name' => 'CLIENTE']);
        $organization = Organization::factory()->create(['is_active' => true]);
        $client = User::factory()->for($role)->for($organization)->create(['is_active' => true]);
        $media = MediaOutlet::factory()->for($organization)->create(['status' => 'ACTIVO']);
        $credential = MediaApiCredential::create([
            'media_outlet_id' => $media->id,
            'created_by' => $client->id,
            'name' => 'Credencial de prueba',
            'public_id' => 'nria_'.Str::lower(Str::random(16)),
            'token_hash' => hash('sha256', Str::random(64)),
            'token_prefix' => 'nria_test',
            'scopes' => ['content:read', 'requests:write'],
        ]);

        return [$client, $organization, $media, $credential];
    }

    private function newsRequest(User $client, MediaOutlet $media, string $title): NewsRequest
    {
        return NewsRequest::factory()->create([
            'requested_by' => $client->id,
            'api_media_outlet_id' => $media->id,
            'title' => $title,
            'description' => 'Solicitud enviada desde el proyecto cliente.',
            'status' => 'COMPLETADA',
            'progress' => 100,
        ]);
    }

    private function usage(NewsRequest $newsRequest, string $operation, int $input, int $output): void
    {
        AiUsage::create([
            'news_request_id' => $newsRequest->id,
            'openai_response_id' => 'resp_'.Str::uuid(),
            'model' => 'gpt-test',
            'operation' => $operation,
            'input_tokens' => $input,
            'output_tokens' => $output,
            'total_tokens' => $input + $output,
        ]);
    }

    private function apiLog(NewsRequest $newsRequest, MediaOutlet $media, MediaApiCredential $credential, string $endpoint): void
    {
        ApiUsageLog::create([
            'request_id' => (string) Str::uuid(),
            'media_outlet_id' => $media->id,
            'media_api_credential_id' => $credential->id,
            'news_request_id' => $newsRequest->id,
            'endpoint' => $endpoint,
            'method' => 'GET',
            'status_code' => 200,
            'records_returned' => 1,
            'duration_ms' => 15,
            'result' => 'SUCCESS',
            'ip_hash' => hash('sha256', '127.0.0.1'),
        ]);
    }
}
