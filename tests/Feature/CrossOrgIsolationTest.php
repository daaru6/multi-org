<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrossOrgIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_org_a_cannot_read_org_b_contact()
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
        
        // Create a contact in Organization B
        $contactB = Contact::create([
            'organization_id' => $orgB->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@orgb.com',
            'created_by' => $userB->id,
            'updated_by' => $userB->id,
        ]);
        
        // User A tries to access contact from Organization B
        $this->actingAs($userA)
            ->withSession(['current_organization_id' => $orgA->id])
            ->get(route('contacts.show', [
                'organization' => $orgA->slug,
                'contact' => $contactB->id
            ]))
            ->assertStatus(404); // Should return 404 due to model binding with organization scope
    }
    
    public function test_org_a_cannot_access_org_b_contact_via_api()
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
        
        // Create a contact in Organization B
        $contactB = Contact::create([
            'organization_id' => $orgB->id,
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane@orgb.com',
            'created_by' => $userB->id,
            'updated_by' => $userB->id,
        ]);
        
        // User A tries to access contact from Organization B via API
        $response = $this->actingAs($userA)
            ->withSession(['current_organization_id' => $orgA->id])
            ->getJson("/api/{$orgA->slug}/contacts/{$contactB->id}");
            
        // Should return 404 or 403
        $this->assertTrue(in_array($response->status(), [403, 404]));
    }
}