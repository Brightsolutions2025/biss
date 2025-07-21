<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;

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
            'client.browse',
            'client.create',
            'client.read',
            'client.update',
            'client.delete',
        ])->map(function ($name) {
            return \App\Models\Permission::create([
                'name'       => $name,
                'company_id' => $this->company->id,
            ]);
        });

        $role->permissions()->attach($permissions->pluck('id'), ['company_id' => $this->company->id]);
    }

    /** @test */
    public function it_displays_client_index()
    {
        Client::factory()->count(3)->create(['company_id' => $this->company->id]);

        $this->actingAs($this->user)
            ->get(route('clients.index'))
            ->assertOk()
            ->assertViewIs('clients.index');
    }

    /** @test */
    public function it_displays_create_form()
    {
        $this->actingAs($this->user)
            ->get(route('clients.create'))
            ->assertOk()
            ->assertViewIs('clients.create');
    }

    /** @test */
    public function it_stores_a_client()
    {
        $this->actingAs($this->user);

        $data = [
            'name'           => 'Acme Corp',
            'contact_person' => 'John Doe',
            'email'          => 'john@acme.test',
            'contact_number' => '09171234567',
        ];

        $response = $this->post(route('clients.store'), $data);

        $response->assertRedirect(route('clients.index'));

        $this->assertDatabaseHas('clients', [
            'name'       => 'Acme Corp',
            'email'      => 'john@acme.test',
            'company_id' => $this->company->id,
        ]);
    }

    /** @test */
    public function it_displays_a_specific_client()
    {
        $client = Client::factory()->create(['company_id' => $this->company->id]);

        $this->actingAs($this->user)
            ->get(route('clients.show', $client))
            ->assertOk()
            ->assertViewIs('clients.show');
    }

    /** @test */
    public function it_displays_edit_form()
    {
        $client = Client::factory()->create(['company_id' => $this->company->id]);

        $this->actingAs($this->user)
            ->get(route('clients.edit', $client))
            ->assertOk()
            ->assertViewIs('clients.edit');
    }

    /** @test */
    public function it_updates_a_client()
    {
        $client = Client::factory()->create([
            'name'       => 'Old Client',
            'company_id' => $this->company->id,
        ]);

        $this->actingAs($this->user);

        $response = $this->put(route('clients.update', $client), [
            'name'           => 'Updated Client',
            'contact_person' => 'Jane Doe',
            'email'          => 'jane@client.test',
            'contact_number' => '09981234567',
        ]);

        $response->assertRedirect(route('clients.index'));

        $this->assertDatabaseHas('clients', [
            'id'    => $client->id,
            'name'  => 'Updated Client',
            'email' => 'jane@client.test',
        ]);
    }

    /** @test */
    public function it_deletes_a_client()
    {
        $client = Client::factory()->create(['company_id' => $this->company->id]);

        $this->actingAs($this->user);

        $response = $this->delete(route('clients.destroy', $client));

        $response->assertRedirect(route('clients.index'));

        $this->assertDatabaseMissing('clients', [
            'id' => $client->id,
        ]);
    }
}
