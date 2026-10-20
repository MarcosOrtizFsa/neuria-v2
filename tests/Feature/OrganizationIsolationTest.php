<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_policy_allows_only_its_organization(): void
    {
        $own = Organization::factory()->create();
        $other = Organization::factory()->create();
        $client = User::factory()->for(Role::factory()->create(['code' => 'CLIENTE']))->for($own)->create();
        $this->assertTrue($client->can('view', $own));
        $this->assertFalse($client->can('view', $other));
    }

    public function test_client_cannot_access_global_organization_administration(): void
    {
        $client = User::factory()->for(Role::factory()->create(['code' => 'CLIENTE']))->for(Organization::factory())->create();
        $this->actingAs($client)->get('/admin/organizaciones')->assertForbidden();
    }
}
