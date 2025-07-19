<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Role;
use App\Models\Shift;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShiftTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->user = User::factory()->create();
        $this->user->companies()->attach($this->company->id);

        UserPreference::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
        ]);

        $role = Role::factory()->create(['name' => 'admin']);
        $this->user->roles()->attach($role->id, ['company_id' => $this->company->id]);

        $permissions = collect([
            'shift.browse',
            'shift.create',
            'shift.read',
            'shift.update',
            'shift.delete',
        ])->map(fn ($name) => \App\Models\Permission::create([
            'name' => $name,
            'company_id' => $this->company->id,
        ]));

        $role->permissions()->attach($permissions->pluck('id'), ['company_id' => $this->company->id]);
    }

    /** @test */
    public function it_displays_shift_index()
    {
        Shift::factory()->count(3)->create(['company_id' => $this->company->id]);

        $this->actingAs($this->user)
            ->get(route('shifts.index'))
            ->assertOk()
            ->assertViewIs('shifts.index');
    }

    /** @test */
    public function it_displays_shift_create_form()
    {
        $this->actingAs($this->user)
            ->get(route('shifts.create'))
            ->assertOk()
            ->assertViewIs('shifts.create');
    }

    /** @test */
    public function it_stores_a_shift()
    {
        $this->actingAs($this->user);

        $response = $this->post(route('shifts.store'), [
            'name' => 'Morning Shift',
            'time_in' => '08:00',
            'time_out' => '16:00',
            'is_night_shift' => false,
        ]);

        $response->assertRedirect(route('shifts.index'));
        $this->assertDatabaseHas('shifts', ['name' => 'Morning Shift']);
    }

    /** @test */
    public function it_shows_a_shift()
    {
        $shift = Shift::factory()->create(['company_id' => $this->company->id]);

        $this->actingAs($this->user)
            ->get(route('shifts.show', $shift))
            ->assertOk()
            ->assertViewIs('shifts.show');
    }

    /** @test */
    public function it_displays_shift_edit_form()
    {
        $shift = Shift::factory()->create(['company_id' => $this->company->id]);

        $this->actingAs($this->user)
            ->get(route('shifts.edit', $shift))
            ->assertOk()
            ->assertViewIs('shifts.edit');
    }

    /** @test */
    public function it_updates_a_shift()
    {
        $shift = Shift::factory()->create([
            'name' => 'Old Name',
            'company_id' => $this->company->id,
        ]);

        $this->actingAs($this->user);

        $response = $this->put(route('shifts.update', $shift), [
            'name' => 'Updated Name',
            'time_in' => $shift->time_in,
            'time_out' => $shift->time_out,
            'is_night_shift' => $shift->is_night_shift,
        ]);

        $response->assertRedirect(route('shifts.index'));
        $this->assertDatabaseHas('shifts', [
            'id' => $shift->id,
            'name' => 'Updated Name',
        ]);
    }

    /** @test */
    public function it_deletes_a_shift()
    {
        $shift = Shift::factory()->create(['company_id' => $this->company->id]);

        $this->actingAs($this->user);

        $response = $this->delete(route('shifts.destroy', $shift));

        $response->assertRedirect(route('shifts.index'));
        $this->assertDatabaseMissing('shifts', ['id' => $shift->id]);
    }
}
