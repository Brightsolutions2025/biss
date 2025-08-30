<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;
    protected TicketType $ticketType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->user    = User::factory()->create();
        $this->user->companies()->attach($this->company->id);

        UserPreference::factory()->create([
            'user_id'    => $this->user->id,
            'company_id' => $this->company->id,
        ]);

        $role = Role::factory()->create(['name' => 'admin']);
        $this->user->roles()->attach($role->id, ['company_id' => $this->company->id]);

        $permissions = collect([
            'ticket.browse',
            'ticket.create',
            'ticket.read',
            'ticket.update',
            'ticket.delete',
        ])->map(fn ($name) => Permission::create([
            'name'       => $name,
            'company_id' => $this->company->id,
        ]));

        $role->permissions()->attach($permissions->pluck('id'), ['company_id' => $this->company->id]);

        $this->ticketType = TicketType::factory()->create([
            'company_id' => $this->company->id,
            'is_active'  => true,
        ]);
    }

    /** @test */
    public function it_displays_ticket_index()
    {
        Ticket::factory()->count(3)->create([
            'company_id'    => $this->company->id,
            'ticket_type_id'=> $this->ticketType->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('tickets.index'))
            ->assertOk()
            ->assertViewIs('tickets.index');
    }

    /** @test */
    public function it_displays_ticket_create_form()
    {
        $this->actingAs($this->user)
            ->get(route('tickets.create'))
            ->assertOk()
            ->assertViewIs('tickets.create');
    }

    /** @test */
    public function it_stores_a_new_ticket()
    {
        $this->actingAs($this->user);

        $data = [
            'ticket_type_id' => $this->ticketType->id,
            'subject'        => 'System Crash',
            'description'    => 'The system crashed at login.',
            'priority'       => 'high',
        ];

        $response = $this->post(route('tickets.store'), $data);

        $response->assertRedirect(route('tickets.index'));
        $this->assertDatabaseHas('tickets', [
            'subject'        => 'System Crash',
            'company_id'     => $this->company->id,
            'ticket_type_id' => $this->ticketType->id,
        ]);
    }

    /** @test */
    public function it_displays_a_ticket()
    {
        $ticket = Ticket::factory()->create([
            'company_id'     => $this->company->id,
            'ticket_type_id' => $this->ticketType->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertViewIs('tickets.show');
    }

    /** @test */
    public function it_displays_ticket_edit_form()
    {
        $ticket = Ticket::factory()->create([
            'company_id'     => $this->company->id,
            'ticket_type_id' => $this->ticketType->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('tickets.edit', $ticket))
            ->assertOk()
            ->assertViewIs('tickets.edit');
    }

    /** @test */
    public function it_updates_a_ticket()
    {
        $ticket = Ticket::factory()->create([
            'company_id'     => $this->company->id,
            'ticket_type_id' => $this->ticketType->id,
            'subject'        => 'Old Subject',
        ]);

        $this->actingAs($this->user);

        $response = $this->put(route('tickets.update', $ticket), [
            'ticket_type_id' => $this->ticketType->id,
            'subject'        => 'New Subject',
            'description'    => 'Updated description',
            'priority'       => 'medium',
        ]);

        $response->assertRedirect(route('tickets.index'));

        $this->assertDatabaseHas('tickets', [
            'id'      => $ticket->id,
            'subject' => 'New Subject',
        ]);
    }

    /** @test */
    public function it_deletes_a_ticket()
    {
        $ticket = Ticket::factory()->create([
            'company_id'     => $this->company->id,
            'ticket_type_id' => $this->ticketType->id,
        ]);

        $this->actingAs($this->user);

        $response = $this->delete(route('tickets.destroy', $ticket));

        $response->assertRedirect(route('tickets.index'));
        $this->assertDatabaseMissing('tickets', [
            'id' => $ticket->id,
        ]);
    }
}
