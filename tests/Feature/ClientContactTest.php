<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\ClientContact;
use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientContactTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;
    protected Client $client;

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
            'client_contact.browse',
            'client_contact.create',
            'client_contact.read',
            'client_contact.update',
            'client_contact.delete',
        ])->map(fn ($name) => Permission::create([
            'name'       => $name,
            'company_id' => $this->company->id,
        ]));

        $role->permissions()->attach($permissions->pluck('id'), ['company_id' => $this->company->id]);

        $this->client = Client::factory()->create([
            'company_id' => $this->company->id,
        ]);
    }

    /** @test */
    public function it_displays_the_contact_index()
    {
        ClientContact::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'client_id'  => $this->client->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('client_contacts.index'))
            ->assertOk()
            ->assertViewIs('client_contacts.index');
    }

    /** @test */
    public function it_displays_the_create_contact_form()
    {
        $this->actingAs($this->user)
            ->get(route('client_contacts.create'))
            ->assertOk()
            ->assertViewIs('client_contacts.create');
    }

    /** @test */
    public function it_stores_a_new_contact()
    {
        $this->actingAs($this->user);

        $data = [
            'client_id'    => $this->client->id,
            'name'         => 'Jane Smith',
            'email'        => 'jane@example.com',
            'phone'        => '09175551234',
            'position'     => 'Manager',
            'is_primary'   => true,
            'linkedin_url' => 'https://linkedin.com/in/janesmith',
        ];

        $response = $this->post(route('client_contacts.store'), $data);

        $response->assertRedirect(route('client_contacts.index'));
        $this->assertDatabaseHas('client_contacts', [
            'name'       => 'Jane Smith',
            'email'      => 'jane@example.com',
            'company_id' => $this->company->id,
        ]);
    }

    /** @test */
    public function it_displays_a_specific_contact()
    {
        $contact = ClientContact::factory()->create([
            'company_id' => $this->company->id,
            'client_id'  => $this->client->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('client_contacts.show', $contact))
            ->assertOk()
            ->assertViewIs('client_contacts.show');
    }

    /** @test */
    public function it_displays_the_edit_form()
    {
        $contact = ClientContact::factory()->create([
            'company_id' => $this->company->id,
            'client_id'  => $this->client->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('client_contacts.edit', $contact))
            ->assertOk()
            ->assertViewIs('client_contacts.edit');
    }

    /** @test */
    public function it_updates_a_contact()
    {
        $contact = ClientContact::factory()->create([
            'company_id' => $this->company->id,
            'client_id'  => $this->client->id,
            'name'       => 'Old Name',
        ]);

        $this->actingAs($this->user);

        $response = $this->put(route('client_contacts.update', $contact), [
            'name'         => 'New Name',
            'email'        => 'new@example.com',
            'phone'        => '09998887777',
            'position'     => 'Director',
            'is_primary'   => false,
            'linkedin_url' => 'https://linkedin.com/in/newname',
        ]);

        $response->assertRedirect(route('client_contacts.index'));

        $this->assertDatabaseHas('client_contacts', [
            'id'    => $contact->id,
            'name'  => 'New Name',
            'email' => 'new@example.com',
        ]);
    }

    /** @test */
    public function it_deletes_a_contact()
    {
        $contact = ClientContact::factory()->create([
            'company_id' => $this->company->id,
            'client_id'  => $this->client->id,
        ]);

        $this->actingAs($this->user);

        $response = $this->delete(route('client_contacts.destroy', $contact));

        $response->assertRedirect(route('client_contacts.index'));
        $this->assertDatabaseMissing('client_contacts', [
            'id' => $contact->id,
        ]);
    }
}
