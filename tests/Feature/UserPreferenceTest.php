<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPreferenceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()
            ->hasAttached(Company::factory(), [], 'companies') // Attach at least one company
            ->create();
    }

    public function test_user_can_view_preferences_page(): void
    {
        $response = $this->actingAs($this->user)->get('/preferences');

        $response->assertOk()->assertViewIs('preferences.index');
    }

    public function test_user_can_view_edit_preferences_form(): void
    {
        $response = $this->actingAs($this->user)->get('/preferences/edit');

        $response->assertOk()->assertViewIs('preferences.edit');
    }

    public function test_user_can_update_preferences(): void
    {
        $company = $this->user->companies->first();

        $response = $this->actingAs($this->user)->patch('/preferences', [
            'company_id'  => $company->id,
            'preferences' => ['theme' => 'dark'],
        ]);

        $response->assertRedirect(route('dashboard'))
                 ->assertSessionHas('success', 'Preferences updated successfully.');

        $this->assertDatabaseHas('user_preferences', [
            'user_id'    => $this->user->id,
            'company_id' => $company->id,
        ]);
    }

    public function test_preferences_update_requires_valid_company(): void
    {
        $invalidCompanyId = 999;

        $response = $this->actingAs($this->user)->patch('/preferences', [
            'company_id' => $invalidCompanyId,
        ]);

        $response->assertSessionHasErrors(['company_id']);
    }

    public function test_user_can_switch_active_company(): void
    {
        $company = $this->user->companies->first();

        $response = $this->actingAs($this->user)->post('/preferences/switch-company', [
            'company_id' => $company->id,
        ]);

        $response->assertSessionHas('success', 'Switched active company.');

        $this->assertDatabaseHas('user_preferences', [
            'user_id'    => $this->user->id,
            'company_id' => $company->id,
        ]);
    }

    public function test_user_cannot_switch_to_unauthorized_company(): void
    {
        $unauthorizedCompany = Company::factory()->create();

        $response = $this->actingAs($this->user)->post('/preferences/switch-company', [
            'company_id' => $unauthorizedCompany->id,
        ]);

        $response->assertForbidden();
    }
}
