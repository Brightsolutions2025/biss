<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Role;
use App\Models\TicketType;
use App\Models\User;
use App\Models\UserPreference;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketTypeTest extends TestCase
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
            'user_id'    => $this->user->id,
            'company_id' => $this->company->id,
        ]);

        $role = Role::factory()->create(['name' => 'admin']);
        $this->user->roles()->attach($role->id, ['company_id' => $this->company->id]);

        $permissions = collect([
            'ticket_type.browse',
            'ticket_type.create',
            'ticket_type.read',
            'ticket_type.update',
            'ticket_type.delete',
        ])->map(function ($name) {
            return Permission::create([
                'name'       => $name,
                'company_id' => $this->company->id,
            ]);
        });

        $role->permissions()->attach($permissions->pluck('id'), ['company_id' => $this->company->id]);
    }

    /** @test */
    public function it_displays_ticket_type_index()
    {
        TicketType::factory()->count(3)->create(['company_id' => $this->company->id]);

        $this->actingAs($this->user)
            ->get(route('ticket_types.index'))
            ->assertOk()
            ->assertViewIs('ticket_types.index');
    }

    /** @test */
    public function it_displays_create_form()
    {
        $this->actingAs($this->user)
            ->get(route('ticket_types.create'))
            ->assertOk()
            ->assertViewIs('ticket_types.create');
    }

    /** @test */
    public function it_stores_a_ticket_type()
    {
        $this->actingAs($this->user);

        $data = [
            'name'        => 'Technical Support',
            'description' => 'Handles tech issues',
            'sort_order'  => 1,
            'is_active'   => true,
        ];

        $response = $this->post(route('ticket_types.store'), $data);

        $response->assertRedirect(route('ticket_types.index'));

        $this->assertDatabaseHas('ticket_types', [
            'name'       => 'Technical Support',
            'company_id' => $this->company->id,
        ]);
    }

    /** @test */
    public function it_displays_a_specific_ticket_type()
    {
        $ticketType = TicketType::factory()->create(['company_id' => $this->company->id]);

        $this->actingAs($this->user)
            ->get(route('ticket_types.show', $ticketType))
            ->assertOk()
            ->assertViewIs('ticket_types.show');
    }

    /** @test */
    public function it_displays_edit_form()
    {
        $ticketType = TicketType::factory()->create(['company_id' => $this->company->id]);

        $this->actingAs($this->user)
            ->get(route('ticket_types.edit', $ticketType))
            ->assertOk()
            ->assertViewIs('ticket_types.edit');
    }

    /** @test */
    public function it_updates_a_ticket_type()
    {
        $ticketType = TicketType::factory()->create([
            'name'       => 'Old Name',
            'company_id' => $this->company->id,
        ]);

        $this->actingAs($this->user);

        $response = $this->put(route('ticket_types.update', $ticketType), [
            'name'        => 'Updated Name',
            'description' => 'Updated description',
            'sort_order'  => 2,
            'is_active'   => false,
        ]);

        $response->assertRedirect(route('ticket_types.index'));

        $this->assertDatabaseHas('ticket_types', [
            'id'   => $ticketType->id,
            'name' => 'Updated Name',
        ]);
    }

    /** @test */
    public function it_deletes_a_ticket_type()
    {
        $ticketType = TicketType::factory()->create(['company_id' => $this->company->id]);

        $this->actingAs($this->user);

        $response = $this->delete(route('ticket_types.destroy', $ticketType));

        $response->assertRedirect(route('ticket_types.index'));

        $this->assertDatabaseMissing('ticket_types', [
            'id' => $ticketType->id,
        ]);
    }
}
