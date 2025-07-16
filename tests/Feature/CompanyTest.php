<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class CompanyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Create a user and authenticate
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    /** @test */
    public function it_shows_user_companies_on_index()
    {
        // Ensure no 'Admin' role exists to allow company creation without restrictions
        \App\Models\Role::whereRaw('LOWER(name) = ?', ['admin'])->delete();

        // Create an authenticated user
        $this->user = \App\Models\User::factory()->create();
        $this->actingAs($this->user);

        // Submit a request to store a company (simulating real controller logic)
        $response = $this->post(route('companies.store'), [
            'name'     => 'Test Name',
            'industry' => 'Software',
            'address'  => '123 Makati Ave',
            'phone'    => '09171234567',
        ]);

        // Assert redirect to dashboard after creation
        $response->assertRedirect(route('dashboard'));

        // Now hit the index route
        $response = $this->get(route('companies.index'));

        $response->assertOk();
        $response->assertViewIs('companies.index');
        $response->assertSee('Test Name');
        $response->assertSee('Companies'); // Plural of Model
        $response->assertSee('Search');
        $response->assertSee('Add');
        $response->assertSee('List');
    }

    /** @test */
    public function it_filters_companies_by_name_in_search()
    {
        // Create a user and authenticate
        $this->user = \App\Models\User::factory()->create();
        $this->actingAs($this->user);

        // Create multiple companies for the user
        $this->post(route('companies.store'), [
            'name'     => 'Alpha Software',
            'industry' => 'Tech',
            'address'  => '123 Makati Ave',
            'phone'    => '09170000001',
        ]);

        $this->post(route('companies.store'), [
            'name'     => 'Beta Solutions',
            'industry' => 'Consulting',
            'address'  => '456 Ortigas Center',
            'phone'    => '09170000002',
        ]);

        $this->post(route('companies.store'), [
            'name'     => 'Gamma Group',
            'industry' => 'Finance',
            'address'  => '789 BGC Taguig',
            'phone'    => '09170000003',
        ]);

        // Search for "Beta"
        $response = $this->get(route('companies.index', ['name' => 'Beta']));

        $response->assertOk();
        $response->assertViewIs('companies.index');
        $response->assertSee('Beta Solutions');

        // ✅ Extract companies from the view data
        $companies = $response->viewData('companies');

        // ✅ Now you can assert on the actual data
        $this->assertTrue($companies->contains('name', 'Beta Solutions'));
        $this->assertFalse($companies->contains('name', 'Alpha Software'));
        $this->assertFalse($companies->contains('name', 'Gamma Group'));
    }

    /** @test */
    public function it_shows_create_company_link_on_index()
    {
        // Create an authenticated user
        $this->user = \App\Models\User::factory()->create();
        $this->actingAs($this->user);

        // Visit the companies index page
        $response = $this->get(route('companies.index'));

        $response->assertOk();
        $response->assertViewIs('companies.index');

        // Assert that the "create company" link is present
        $response->assertSee(route('companies.create'));

        // Optionally assert the link text too
        $response->assertSee('Want to add a new company?'); // if your Blade shows a link like: <a href="{{ route('companies.create') }}">Create Company</a>
    }

    /** @test */
    public function it_shows_view_edit_and_delete_buttons_on_index()
    {
        // Create a user and authenticate
        $this->user = \App\Models\User::factory()->create();
        $this->actingAs($this->user);

        // Create multiple companies for the user
        $this->post(route('companies.store'), [
            'name'     => 'Alpha Software',
            'industry' => 'Tech',
            'address'  => '123 Makati Ave',
            'phone'    => '09170000001',
        ]);

        // Access the index page
        $response = $this->get(route('companies.index'));

        $response->assertOk();
        $response->assertViewIs('companies.index');

        // Check for the presence of the action buttons/links
        $response->assertSee('View');
        $response->assertSee('Edit');
        $response->assertSee('Delete');

        $company = \App\Models\Company::where('name', 'Alpha Software')->first();

        // Optionally assert that the correct routes are linked (if using named routes)
        $response->assertSee(route('companies.show', $company));
        $response->assertSee('/companies/' . $company->id . '/edit');

        // Check that the delete method spoofing input exists
        $response->assertSee('<input type="hidden" name="_method" value="DELETE">', false);
    }

    /** @test */
    public function it_shows_company_create_form()
    {
        $response = $this->get(route('companies.create'));
        $response->assertOk();
        $response->assertViewIs('companies.create');
        $response->assertSee('Add a New Company');
        $response->assertSee('<button', false); // Checks presence of any <button>
        // Check for the presence of a cancel link
        $response->assertSee('href="javascript:history.back()"', false);
    }

    /** @test */
    public function it_stores_company_and_assigns_admin_if_none_exists()
    {
        // Create an authenticated user
        $this->user = \App\Models\User::factory()->create();
        $this->actingAs($this->user);

        // Submit a request to store a company (simulating real controller logic)
        $response = $this->post(route('companies.store'), [
            'name'     => 'Test Company',
            'industry' => 'Software',
            'address'  => '123 Makati Ave',
            'phone'    => '09171234567',
        ]);

        $response->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('companies', ['name' => 'Test Company']);

        $company = \App\Models\Company::where('name', 'Test Company')->first();

        $this->assertDatabaseHas('roles', [
            'name'       => 'Admin',
            'company_id' => $company->id,
        ]);

        $this->assertDatabaseHas('user_preferences', [
            'user_id'    => $this->user->id,
            'company_id' => $company->id,
        ]);

        $this->assertTrue(
            $this->user->roles()
                ->where('name', 'Admin')
                ->wherePivot('company_id', $company->id)
                ->exists()
        );
    }

    /** @test */
    public function admin_can_create_another_company_if_admin_exists()
    {
        // Create an authenticated user
        $this->user = \App\Models\User::factory()->create();
        $this->actingAs($this->user);

        // Submit a request to store a company (simulating real controller logic)
        $response = $this->post(route('companies.store'), [
            'name'     => 'Test Company',
            'industry' => 'Software',
            'address'  => '123 Makati Ave',
            'phone'    => '09171234567',
        ]);

        $response->assertRedirect(route('dashboard'));

        // Ensure the admin role now exists
        $this->assertDatabaseHas('roles', ['name' => 'Admin']);

        // Step 4: Attempt to create a company again
        $response = $this->post(route('companies.store'), [
            'name'     => 'Second Company',
            'industry' => 'Finance',
            'address'  => '456 BGC St',
            'phone'    => '09998887777',
        ]);

        $response->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('companies', ['name' => 'Second Company']);
    }

    /** @test */
    public function non_admin_cannot_create_company_if_admin_exists()
    {
        // Create an authenticated user
        $this->user = \App\Models\User::factory()->create();
        $this->actingAs($this->user);

        // Submit a request to store a company (simulating real controller logic)
        $response = $this->post(route('companies.store'), [
            'name'     => 'Test Company',
            'industry' => 'Software',
            'address'  => '123 Makati Ave',
            'phone'    => '09171234567',
        ]);

        $response->assertRedirect(route('dashboard'));

        // Ensure the admin role now exists
        $this->assertDatabaseHas('roles', ['name' => 'Admin']);

        // Step 3: Create a second user with NO admin role
        $nonAdminUser = \App\Models\User::factory()->create();
        $this->actingAs($nonAdminUser);

        // Step 4: Attempt to create a company again
        $response = $this->post(route('companies.store'), [
            'name'     => 'Second Company',
            'industry' => 'Finance',
            'address'  => '456 BGC St',
            'phone'    => '09998887777',
        ]);

        // Step 5: Assert redirect and session error message
        $response->assertRedirect(route('companies.index'));
        $response->assertSessionHasErrors(['error' => 'Only admin users can create new companies.']);

        // Ensure the second company was not created
        $this->assertDatabaseMissing('companies', [
            'name' => 'Second Company',
        ]);
    }

    /** @test */
    public function it_displays_company_if_user_has_access()
    {
        // Create an authenticated user
        $this->user = \App\Models\User::factory()->create();
        $this->actingAs($this->user);

        // Submit a request to store a company (simulating real controller logic)
        $response = $this->post(route('companies.store'), [
            'name'     => 'Test Company',
            'industry' => 'Software',
            'address'  => '123 Makati Ave',
            'phone'    => '09171234567',
        ]);

        $company = \App\Models\Company::where('name', 'Test Company')->firstOrFail();

        $response = $this->get(route('companies.show', $company));
        $response->assertOk();
        $response->assertViewIs('companies.show');
        $response->assertViewHas('company');
        $response->assertSee('Company Details');
        $response->assertSee('Edit'); // Text on the Edit button
        $response->assertSee('Delete'); // Text on the Delete button
        $response->assertSee('form', false); // Ensures there's a <form> tag (used by the Delete form)
        $response->assertSee('method="POST"', false); // Confirms the delete form is using POST
        $response->assertSee('type="submit"', false); // Confirms there is a submit button
        $response->assertSee('href="' . route('companies.edit', $company->id) . '"', false);
        $response->assertSee('action="' . route('companies.destroy', $company->id) . '"', false);
        $response->assertSee('href="javascript:history.back()"', false);
    }

    /** @test */
    public function it_shows_edit_form_for_admin_user()
    {
        // Create an authenticated user
        $this->user = \App\Models\User::factory()->create();
        $this->actingAs($this->user);

        // Submit a request to store a company (simulating real controller logic)
        $response = $this->post(route('companies.store'), [
            'name'     => 'Test Company',
            'industry' => 'Software',
            'address'  => '123 Makati Ave',
            'phone'    => '09171234567',
        ]);

        $company = \App\Models\Company::where('name', 'Test Company')->firstOrFail();

        $response = $this->get(route('companies.edit', $company));
        $response->assertOk();
        $response->assertViewIs('companies.edit');
        $response->assertViewHas('company');
        $response->assertSee('Edit Company');
        $response->assertSee('<button', false);       // Checks that a button tag exists
        $response->assertSee('Update');               // Checks that the word "Update" appears (case-sensitive)
        // Check for Cancel button
        $response->assertSee('href="javascript:history.back()"', false);
    }

    /** @test */
    public function it_denies_edit_access_to_non_admin_user()
    {
        // Create an authenticated user
        $this->user = \App\Models\User::factory()->create();
        $this->actingAs($this->user);

        // Submit a request to store a company (simulating real controller logic)
        $response = $this->post(route('companies.store'), [
            'name'     => 'Old',
            'industry' => 'Software',
            'address'  => '123 Makati Ave',
            'phone'    => '09171234567',
        ]);

        $response->assertRedirect(route('dashboard'));

        // Ensure the admin role now exists
        $this->assertDatabaseHas('roles', ['name' => 'Admin']);

        // Step 3: Create a second user with NO admin role
        $nonAdminUser = \App\Models\User::factory()->create();
        $this->actingAs($nonAdminUser);

        $company = \App\Models\Company::where('name', 'Old')->firstOrFail();

        $response = $this->get(route('companies.edit', $company));

        $response->assertForbidden();
    }

    /** @test */
    public function it_updates_company_if_admin()
    {
        // Create an authenticated user
        $this->user = \App\Models\User::factory()->create();
        $this->actingAs($this->user);

        // Submit a request to store a company (simulating real controller logic)
        $response = $this->post(route('companies.store'), [
            'name'     => 'Test Company',
            'industry' => 'Software',
            'address'  => '123 Makati Ave',
            'phone'    => '09171234567',
        ]);

        $company = \App\Models\Company::where('name', 'Test Company')->firstOrFail();

        $response = $this->put(route('companies.update', $company), [
            'name' => 'New Company Name',
        ]);

        $response->assertRedirect(route('companies.index'));
        $this->assertDatabaseHas('companies', ['name' => 'New Company Name']);
    }

    /** @test */
    public function it_does_not_update_company_if_not_admin()
    {
        // Create an authenticated user
        $this->user = \App\Models\User::factory()->create();
        $this->actingAs($this->user);

        // Submit a request to store a company (simulating real controller logic)
        $response = $this->post(route('companies.store'), [
            'name'     => 'Old',
            'industry' => 'Software',
            'address'  => '123 Makati Ave',
            'phone'    => '09171234567',
        ]);

        $response->assertRedirect(route('dashboard'));

        // Ensure the admin role now exists
        $this->assertDatabaseHas('roles', ['name' => 'Admin']);

        // Step 3: Create a second user with NO admin role
        $nonAdminUser = \App\Models\User::factory()->create();
        $this->actingAs($nonAdminUser);

        $company = \App\Models\Company::where('name', 'Old')->firstOrFail();

        $response = $this->put(route('companies.update', $company), [
            'name' => 'New',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('companies', ['name' => 'Old']);
    }

    /** @test */
    public function it_deletes_company_if_admin()
    {
        // Create an authenticated user
        $this->user = \App\Models\User::factory()->create();
        $this->actingAs($this->user);

        // Submit a request to store a company (simulating real controller logic)
        $response = $this->post(route('companies.store'), [
            'name'     => 'Test Company',
            'industry' => 'Software',
            'address'  => '123 Makati Ave',
            'phone'    => '09171234567',
        ]);

        $company = \App\Models\Company::where('name', 'Test Company')->firstOrFail();

        $response = $this->delete(route('companies.destroy', $company));
        $response->assertRedirect(route('companies.index'));
        $this->assertDatabaseMissing('companies', [
            'id' => $company->id,
        ]);
    }

    /** @test */
    public function it_prevents_company_deletion_by_non_admin()
    {
        // Create an authenticated user
        $this->user = \App\Models\User::factory()->create();
        $this->actingAs($this->user);

        // Submit a request to store a company (simulating real controller logic)
        $response = $this->post(route('companies.store'), [
            'name'     => 'Old',
            'industry' => 'Software',
            'address'  => '123 Makati Ave',
            'phone'    => '09171234567',
        ]);

        $response->assertRedirect(route('dashboard'));

        // Ensure the admin role now exists
        $this->assertDatabaseHas('roles', ['name' => 'Admin']);

        // Step 3: Create a second user with NO admin role
        $nonAdminUser = \App\Models\User::factory()->create();
        $this->actingAs($nonAdminUser);

        $company = \App\Models\Company::where('name', 'Old')->firstOrFail();

        $response = $this->delete(route('companies.destroy', $company));
        $response->assertForbidden();
        $this->assertDatabaseHas('companies', ['id' => $company->id]);
    }

    /** @test */
    public function it_displays_validation_errors_on_invalid_update_input()
    {
        // Create an authenticated user
        $this->user = \App\Models\User::factory()->create();
        $this->actingAs($this->user);

        // Submit a request to store a company (simulating real controller logic)
        $response = $this->post(route('companies.store'), [
            'name'     => 'Test Company',
            'industry' => 'Software',
            'address'  => '123 Makati Ave',
            'phone'    => '09171234567',
        ]);

        $company = \App\Models\Company::where('name', 'Test Company')->firstOrFail();

        // Send invalid update request (missing name)
        $response = $this->put(route('companies.update', $company), [
            'name' => '',
        ]);

        // Assert validation error is stored in session
        $response->assertSessionHasErrors(['name']);

        // Now simulate the redirected GET to the edit page (errors will be shown there)
        $response = $this->get(route('companies.edit', $company));

        // Check if the error message is visible
        $response->assertSee('The name field is required.');
    }
}
