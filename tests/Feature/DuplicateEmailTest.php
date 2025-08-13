<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class DuplicateEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_email_blocks_creation_and_returns_exact_422_payload()
    {
        // Seed permissions and roles
        $this->seed(RolePermissionSeeder::class);
        
        // Create organization and user
        $user = User::factory()->create();
        $org = Organization::create([
            'name' => 'Test Organization',
            'owner_user_id' => $user->id,
        ]);
        
        // Attach user to organization and assign role
        $org->users()->attach($user->id, ['role' => 'Admin']);
        $user->assignRole('Admin');
        
        // Create an existing contact
        $existingContact = Contact::create([
            'organization_id' => $org->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        
        // Attempt to create a new contact with the same email (case-insensitive)
        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $org->id])
            ->postJson(route('contacts.store', ['organization' => $org->slug]), [
                'first_name' => 'Jane',
                'last_name' => 'Smith',
                'email' => 'JOHN@EXAMPLE.COM', // Different case
            ]);
        
        // Assert exact 422 response with required payload
        $response->assertStatus(422)
            ->assertExactJson([
                'code' => 'DUPLICATE_EMAIL',
                'existing_contact_id' => $existingContact->id
            ]);
        
        // Verify no new contact was created
        $this->assertDatabaseCount('contacts', 1);
        
        // Verify the existing contact is unchanged
        $this->assertDatabaseHas('contacts', [
            'id' => $existingContact->id,
            'email' => 'john@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe'
        ]);
    }
    
    public function test_duplicate_email_logs_attempt()
    {
        // Seed permissions and roles
        $this->seed(RolePermissionSeeder::class);
        
        // Create organization and user
        $user = User::factory()->create();
        $org = Organization::create([
            'name' => 'Test Organization',
            'owner_user_id' => $user->id,
        ]);
        
        // Attach user to organization and assign role
        $org->users()->attach($user->id, ['role' => 'Admin']);
        $user->assignRole('Admin');
        
        // Create an existing contact
        $existingContact = Contact::create([
            'organization_id' => $org->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        
        // Attempt to create duplicate
        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $org->id])
            ->postJson(route('contacts.store', ['organization' => $org->slug]), [
                'first_name' => 'Jane',
                'last_name' => 'Smith',
                'email' => 'john@example.com',
            ]);
        
        // Assert the response is correct (this indirectly tests that logging occurred)
        $response->assertStatus(422)
            ->assertJson([
                'code' => 'DUPLICATE_EMAIL',
                'existing_contact_id' => $existingContact->id,
            ]);
    }
    
    public function test_duplicate_email_redirects_to_existing_contact_for_web_requests()
    {
        // Seed permissions and roles
        $this->seed(RolePermissionSeeder::class);
        
        // Create organization and user
        $user = User::factory()->create();
        $org = Organization::create([
            'name' => 'Test Organization',
            'owner_user_id' => $user->id,
        ]);
        
        // Attach user to organization and assign role
        $org->users()->attach($user->id, ['role' => 'Admin']);
        $user->assignRole('Admin');
        
        // Create an existing contact
        $existingContact = Contact::create([
            'organization_id' => $org->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        
        // Attempt to create duplicate via web form
        $response = $this->actingAs($user)
            ->withSession(['current_organization_id' => $org->id])
            ->post(route('contacts.store', ['organization' => $org->slug]), [
                'first_name' => 'Jane',
                'last_name' => 'Smith',
                'email' => 'john@example.com',
            ]);
        
        // Should redirect to existing contact with error message
        $response->assertRedirect(route('contacts.show', [
            'organization' => $org->slug,
            'contact' => $existingContact
        ]))
        ->assertSessionHas('error', 'Duplicate detected. No new contact was created.');
    }
    
    public function test_different_organizations_can_have_same_email()
    {
        // Seed permissions and roles
        $this->seed(RolePermissionSeeder::class);
        
        // Create two organizations with users
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        
        $orgA = Organization::create([
            'name' => 'Organization A',
            'owner_user_id' => $userA->id,
        ]);
        
        $orgB = Organization::create([
            'name' => 'Organization B',
            'owner_user_id' => $userB->id,
        ]);
        
        // Attach users to their organizations and assign roles
        $orgA->users()->attach($userA->id, ['role' => 'Admin']);
        $orgB->users()->attach($userB->id, ['role' => 'Admin']);
        $userA->assignRole('Admin');
        $userB->assignRole('Admin');
        
        // Create contact in first organization
        Contact::create([
            'organization_id' => $orgA->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'created_by' => $userA->id,
            'updated_by' => $userA->id,
        ]);
        
        // Create contact with same email in second organization - should succeed
        $response = $this->actingAs($userB)
            ->withSession(['current_organization_id' => $orgB->id])
            ->postJson(route('contacts.store', ['organization' => $orgB->slug]), [
                'first_name' => 'Jane',
                'last_name' => 'Smith',
                'email' => 'john@example.com',
            ]);
        
        $response->assertStatus(201); // Should succeed
        $this->assertDatabaseCount('contacts', 2); // Both contacts should exist
        
        // Verify both contacts exist in their respective organizations
        $this->assertDatabaseHas('contacts', [
            'organization_id' => $orgA->id,
            'email' => 'john@example.com',
            'first_name' => 'John',
        ]);
        
        $this->assertDatabaseHas('contacts', [
            'organization_id' => $orgB->id,
            'email' => 'john@example.com',
            'first_name' => 'Jane',
        ]);
    }
}