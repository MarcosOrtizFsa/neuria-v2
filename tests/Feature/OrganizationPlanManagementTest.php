<?php

namespace Tests\Feature;

use App\Models\AutomationBudget;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationPlanManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_can_create_an_organization_monthly_plan(): void
    {
        $root = $this->root();
        $organization = Organization::factory()->create();

        $response = $this->actingAs($root)->put(route('admin.organizaciones.plan.update', $organization), [
            'currency' => 'ARS',
            'monthly_limit' => '100000.50',
            'active_task_limit' => 12,
            'daily_run_limit' => 25,
            'concurrency_limit' => 3,
        ]);

        $response->assertRedirect(route('admin.organizaciones.show', $organization))
            ->assertSessionHas('success', 'Plan mensual actualizado.');
        $this->assertDatabaseHas('automation_budgets', [
            'organization_id' => $organization->id,
            'currency' => 'ARS',
            'monthly_limit_minor' => 10000050,
            'reserved_minor' => 0,
            'settled_minor' => 0,
            'active_task_limit' => 12,
            'daily_run_limit' => 25,
            'concurrency_limit' => 3,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $root->id,
            'action' => 'CONFIGURAR_PLAN',
            'module' => 'ORGANIZACIONES',
            'auditable_id' => $organization->id,
        ]);
    }

    public function test_updating_current_plan_preserves_settled_and_reserved_amounts(): void
    {
        $root = $this->root();
        $organization = Organization::factory()->create();
        AutomationBudget::factory()->for($organization)->create([
            'period_start' => now()->startOfMonth(),
            'monthly_limit_minor' => 10000000,
            'reserved_minor' => 150000,
            'settled_minor' => 250000,
        ]);

        $this->actingAs($root)->put(route('admin.organizaciones.plan.update', $organization), [
            'currency' => 'ARS',
            'monthly_limit' => '150000.00',
            'active_task_limit' => 15,
            'daily_run_limit' => 30,
            'concurrency_limit' => 4,
        ])->assertRedirect();

        $budget = $organization->automationBudget()->firstOrFail();
        $this->assertSame(15000000, $budget->monthly_limit_minor);
        $this->assertSame(150000, $budget->reserved_minor);
        $this->assertSame(250000, $budget->settled_minor);
    }

    public function test_new_period_resets_previous_month_accumulators(): void
    {
        $root = $this->root();
        $organization = Organization::factory()->create();
        AutomationBudget::factory()->for($organization)->create([
            'period_start' => now()->subMonth()->startOfMonth(),
            'reserved_minor' => 150000,
            'settled_minor' => 250000,
        ]);

        $this->actingAs($root)->put(route('admin.organizaciones.plan.update', $organization), [
            'currency' => 'USD',
            'monthly_limit' => '5000.00',
            'active_task_limit' => 10,
            'daily_run_limit' => 20,
            'concurrency_limit' => 2,
        ])->assertRedirect();

        $budget = $organization->automationBudget()->firstOrFail();
        $this->assertSame('USD', $budget->currency);
        $this->assertSame(0, $budget->reserved_minor);
        $this->assertSame(0, $budget->settled_minor);
        $this->assertTrue($budget->period_start->isSameMonth(now()));
    }

    public function test_plan_cannot_be_lower_than_current_committed_amount(): void
    {
        $root = $this->root();
        $organization = Organization::factory()->create();
        AutomationBudget::factory()->for($organization)->create([
            'period_start' => now()->startOfMonth(),
            'reserved_minor' => 150000,
            'settled_minor' => 250000,
        ]);

        $response = $this->actingAs($root)
            ->from(route('admin.organizaciones.plan.edit', $organization))
            ->put(route('admin.organizaciones.plan.update', $organization), [
                'currency' => 'ARS',
                'monthly_limit' => '3999.99',
                'active_task_limit' => 10,
                'daily_run_limit' => 20,
                'concurrency_limit' => 2,
            ]);

        $response->assertRedirect(route('admin.organizaciones.plan.edit', $organization))
            ->assertSessionHasErrors('monthly_limit');
        $this->assertSame(10000000, $organization->automationBudget()->firstOrFail()->monthly_limit_minor);
    }

    public function test_client_cannot_configure_its_organization_plan(): void
    {
        $organization = Organization::factory()->create();
        $client = User::factory()
            ->for(Role::factory()->create(['code' => 'CLIENTE']))
            ->for($organization)
            ->create();

        $this->actingAs($client)->get(route('admin.organizaciones.plan.edit', $organization))->assertForbidden();
        $this->actingAs($client)->put(route('admin.organizaciones.plan.update', $organization), [
            'currency' => 'ARS',
            'monthly_limit' => '100000.00',
            'active_task_limit' => 10,
            'daily_run_limit' => 20,
            'concurrency_limit' => 2,
        ])->assertForbidden();
        $this->assertDatabaseCount('automation_budgets', 0);
    }

    public function test_plan_form_escapes_organization_name(): void
    {
        $root = $this->root();
        $organization = Organization::factory()->create(['name' => '<script>alert("xss")</script>']);

        $response = $this->actingAs($root)->get(route('admin.organizaciones.plan.edit', $organization));

        $response->assertOk()
            ->assertSee('&lt;script&gt;', false)
            ->assertDontSee('<script>alert("xss")</script>', false);
    }

    private function root(): User
    {
        return User::factory()->for(Role::factory()->create(['code' => 'ROOT']))->create();
    }
}
