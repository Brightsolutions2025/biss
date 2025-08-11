<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\User;
use App\Models\Company;
use App\Models\Role;
use App\Models\UserPreference;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketTest extends TestCase
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
            'ticket.browse',
            'ticket.create',
            'ticket.read',
            'ticket.update',
            'ticket.delete',
        ])->map(function ($name) {
            return Permission::create([
                'name'       => $name,
                'company_id' => $this->company->id,
            ]);
        });

        $role->permissions()->attach($permissions->pluck('id'), ['company_id' => $this->company->id]);
    }

    /** @test */
    public function it_displays_ticket_index()
    {
        Ticket::factory()->count(3)->create();

        $this->actingAs($this->user)
            ->get(route('tickets.index'))
            ->assertOk()
            ->assertViewIs('tickets.index');
    }

    /** @test */
    public function it_displays_create_form()
    {
        $this->actingAs($this->user)
            ->get(route('tickets.create'))
            ->assertOk()
            ->assertViewIs('tickets.create');
    }

    /** @test */
    public function it_stores_a_ticket()
    {
        $ticketType = TicketType::factory()->create(['company_id' => $this->company->id]);

        $data = [
            'ticket_type_id' => $ticketType->id,
            'subject'        => 'Test Ticket',
            'description'    => 'Test ticket description',
            'status'         => 'open',
            'priority'       => 'high',
        ];

        $this->actingAs($this->user);

        $response = $this->post(route('tickets.store'), $data);

        $response->assertRedirect(route('tickets.index'));

        $this->assertDatabaseHas('tickets', [
            'subject' => 'Test Ticket',
            'company_id' => $this->company->id,
        ]);
    }

    /** @test */
    public function it_displays_a_specific_ticket()
    {
        $ticket = Ticket::factory()->create();

        $this->actingAs($this->user)
            ->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertViewIs('tickets.show');
    }

    /** @test */
    public function it_displays_edit_form()
    {
        $ticket = Ticket::factory()->create();

        $this->actingAs($this->user)
            ->get(route('tickets.edit', $ticket))
            ->assertOk()
            ->assertViewIs('tickets.edit');
    }

    /** @test */
    public function it_updates_a_ticket()
    {
        $ticket = Ticket::factory()->create(['subject' => 'Old Subject']);

        $this->actingAs($this->user);

        $response = $this->put(route('tickets.update', $ticket), [
            'subject'     => 'Updated Subject',
            'description' => 'Updated description',
            'status'      => 'in_progress',
            'priority'    => 'low',
        ]);

        $response->assertRedirect(route('tickets.index'));

        $this->assertDatabaseHas('tickets', [
            'id'      => $ticket->id,
            'subject' => 'Updated Subject',
        ]);
    }

    /** @test */
    public function it_deletes_a_ticket()
    {
        $ticket = Ticket::factory()->create();

        $this->actingAs($this->user);

        $response = $this->delete(route('tickets.destroy', $ticket));

        $response->assertRedirect(route('tickets.index'));

        $this->assertDatabaseMissing('tickets', [
            'id' => $ticket->id,
        ]);
    }
    
    /** @test */
    public function it_has_relationships()
    {
        $ticket = Ticket::factory()->create();

        $this->assertInstanceOf(Company::class, $ticket->company);
        $this->assertInstanceOf(TicketType::class, $ticket->ticketType);
        $this->assertInstanceOf(User::class, $ticket->creator);
        $this->assertTrue($ticket->assignedTo === null || $ticket->assignedTo instanceof User);
    }
}
