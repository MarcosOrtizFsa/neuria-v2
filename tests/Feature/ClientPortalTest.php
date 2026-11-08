<?php

namespace Tests\Feature;

use App\Models\ApiUsageLog;
use App\Models\AutomationBudget;
use App\Models\AutomationLedgerEntry;
use App\Models\AutomationQuote;
use App\Models\AutomationRun;
use App\Models\AutomationTask;
use App\Models\AutomationTaskVersion;
use App\Models\MediaApiCredential;
use App\Models\MediaOutlet;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Tests\TestCase;

class ClientPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_sees_only_its_organizations_monthly_services_and_consumption(): void
    {
        $month = now('America/Argentina/Buenos_Aires')->startOfMonth();
        [$client, $organization, $media] = $this->clientEnvironment('Cliente propio');
        [, , $otherMedia] = $this->clientEnvironment('Otro cliente');

        $this->credential($client, $media);
        $this->apiUsage($media, 3, 200);
        $this->apiUsage($otherMedia, 99, 500);
        $task = AutomationTask::factory()->for($organization)->for($client, 'creator')->create(['status' => 'ACTIVA', 'next_run_at' => now()->addDay()]);
        $version = AutomationTaskVersion::factory()->for($task, 'task')->create(['version' => 1]);
        $quote = AutomationQuote::factory()->for($task, 'task')->for($version, 'version')->for($organization)->create();
        $run = AutomationRun::factory()->for($task, 'task')->for($organization)->for($version, 'version')->for($quote, 'quote')->create(['status' => 'COMPLETADA']);
        AutomationLedgerEntry::factory()->for($organization)->for($run, 'run')->for($quote, 'quote')->create(['type' => 'LIQUIDACION', 'amount_minor' => 125000, 'currency' => 'ARS']);
        AutomationBudget::factory()->for($organization)->create(['period_start' => $month, 'monthly_limit_minor' => 1000000, 'settled_minor' => 125000]);

        $response = $this->actingAs($client)->get(route('panel', ['month' => $month->format('Y-m')]));

        $response->assertOk()
            ->assertSee('Centro de servicios agénticos')
            ->assertSee('Cliente propio')
            ->assertSee('Distribución por API')
            ->assertSee('ARS 1.250,00')
            ->assertSee('3 registros entregados este mes')
            ->assertDontSee('Portal público')
            ->assertDontSee('Configuración')
            ->assertDontSee('99 registros entregados este mes');
    }

    public function test_client_month_filter_is_validated(): void
    {
        [$client] = $this->clientEnvironment('Cliente');

        $this->actingAs($client)->get(route('panel', ['month' => 'noviembre']))
            ->assertRedirect()
            ->assertSessionHasErrors('month');
    }

    public function test_client_without_an_organization_receives_a_safe_empty_state(): void
    {
        $role = Role::factory()->create(['code' => 'CLIENTE']);
        $client = User::factory()->for($role)->create(['organization_id' => null, 'is_active' => true]);

        $this->actingAs($client)->get(route('panel'))
            ->assertOk()
            ->assertSee('todavía no tiene una organización asignada');
    }

    public function test_application_renders_pagination_with_bootstrap_five(): void
    {
        $paginator = new LengthAwarePaginator(range(1, 25), 50, 25, 1, ['path' => '/admin/api-consumos']);
        $html = $paginator->links()->toHtml();

        $this->assertStringContainsString('pagination', $html);
        $this->assertStringContainsString('page-item', $html);
        $this->assertStringContainsString('page-link', $html);
    }

    /** @return array{User, Organization, MediaOutlet} */
    private function clientEnvironment(string $organizationName): array
    {
        $role = Role::firstOrCreate(['code' => 'CLIENTE'], ['name' => 'Cliente']);
        $organization = Organization::factory()->create(['name' => $organizationName]);
        $client = User::factory()->for($role)->for($organization)->create(['is_active' => true]);
        $media = MediaOutlet::factory()->for($organization)->create(['status' => 'ACTIVO']);

        return [$client, $organization, $media];
    }

    private function credential(User $client, MediaOutlet $media): void
    {
        MediaApiCredential::create([
            'media_outlet_id' => $media->id,
            'created_by' => $client->id,
            'name' => 'Integración principal',
            'public_id' => (string) Str::uuid(),
            'token_hash' => hash('sha256', Str::random(64)),
            'token_prefix' => 'nria_test',
            'scopes' => ['contents:read'],
        ]);
    }

    private function apiUsage(MediaOutlet $media, int $records, int $status): void
    {
        ApiUsageLog::create([
            'request_id' => (string) Str::uuid(),
            'media_outlet_id' => $media->id,
            'endpoint' => 'api.v1.contents.index',
            'method' => 'GET',
            'status_code' => $status,
            'records_returned' => $records,
            'duration_ms' => 25,
            'result' => $status < 400 ? 'SUCCESS' : 'ERROR',
        ]);

    }
}
