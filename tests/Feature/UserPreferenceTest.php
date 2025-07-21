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
    protected Company $company1;
    protected Company $company2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user     = User::factory()->create();
        $this->company1 = Company::factory()->create();
        $this->company2 = Company::factory()->create();

        // Attach both companies to the user
        $this->user->companies()->attach([$this->company1->id, $this->company2->id]);

        // Assign initial preference to company1
        UserPreference::factory()->create([
            'user_id'     => $this->user->id,
            'company_id'  => $this->company1->id,
            'preferences' => ['theme' => 'dark'],
        ]);
    }

    /** @test */
    public function it_displays_edit_preferences_form()
    {
        $this->actingAs($this->user);

        $response = $this->get(route('preferences.edit'));

        $response->assertOk();
        $response->assertViewIs('preferences.edit');
        $response->assertViewHasAll(['preference', 'companies']);
    }

    /** @test */
    public function it_updates_user_preferences_to_different_company()
    {
        $this->actingAs($this->user);

        $response = $this->put(route('preferences.update'), [
            'company_id'  => $this->company2->id, // switch from company1 to company2
            'preferences' => ['theme' => 'light'],
        ]);

        $response->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('user_preferences', [
            'user_id'    => $this->user->id,
            'company_id' => $this->company2->id,
        ]);
    }
}
