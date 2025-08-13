<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Organization;
use App\Models\Contact;
use App\Models\ContactNote;
use App\Models\ContactMeta;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed roles and permissions first
        $this->call(RolePermissionSeeder::class);
        
        // Create test users
        $user1 = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        
        $user2 = User::create([
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        
        // Create organizations
        $org1 = Organization::create([
            'name' => 'Acme Corporation',
            'owner_user_id' => $user1->id,
        ]);
        
        $org2 = Organization::create([
            'name' => 'Tech Solutions Inc',
            'owner_user_id' => $user2->id,
        ]);
        
        // Attach users to organizations with roles
        $org1->users()->attach($user1->id, ['role' => 'Admin', 'created_at' => now(), 'updated_at' => now()]);
        $org1->users()->attach($user2->id, ['role' => 'Member', 'created_at' => now(), 'updated_at' => now()]);
        $org2->users()->attach($user2->id, ['role' => 'Admin', 'created_at' => now(), 'updated_at' => now()]);
        
        // Assign Spatie roles to users
        $user1->assignRole('Admin');
        $user2->assignRole('Admin'); // User2 is admin of org2, member of org1
        
        // Create contacts for Organization 1
        $contact1 = Contact::create([
            'organization_id' => $org1->id,
            'first_name' => 'Alice',
            'last_name' => 'Johnson',
            'email' => 'alice@example.com',
            'phone' => '+1-555-0101',
            'address' => '123 Main St, Anytown, USA',
            'created_by' => $user1->id,
            'updated_by' => $user1->id,
        ]);
        
        $contact2 = Contact::create([
            'organization_id' => $org1->id,
            'first_name' => 'Bob',
            'last_name' => 'Wilson',
            'email' => 'bob@example.com',
            'phone' => '+1-555-0102',
            'address' => '456 Oak Ave, Somewhere, USA',
            'created_by' => $user1->id,
            'updated_by' => $user1->id,
        ]);
        
        $contact3 = Contact::create([
            'organization_id' => $org1->id,
            'first_name' => 'Carol',
            'last_name' => 'Davis',
            'email' => 'carol@example.com',
            'phone' => '+1-555-0103',
            'created_by' => $user2->id,
            'updated_by' => $user2->id,
        ]);
        
        // Create contacts for Organization 2
        $contact4 = Contact::create([
            'organization_id' => $org2->id,
            'first_name' => 'David',
            'last_name' => 'Brown',
            'email' => 'david@example.com',
            'phone' => '+1-555-0201',
            'address' => '789 Pine St, Elsewhere, USA',
            'created_by' => $user2->id,
            'updated_by' => $user2->id,
        ]);
        
        $contact5 = Contact::create([
            'organization_id' => $org2->id,
            'first_name' => 'Emma',
            'last_name' => 'Taylor',
            'email' => 'emma@example.com',
            'phone' => '+1-555-0202',
            'created_by' => $user2->id,
            'updated_by' => $user2->id,
        ]);
        
        // Create contact notes
        ContactNote::create([
            'contact_id' => $contact1->id,
            'user_id' => $user1->id,
            'body' => 'Initial contact made. Very interested in our services.',
        ]);
        
        ContactNote::create([
            'contact_id' => $contact1->id,
            'user_id' => $user2->id,
            'body' => 'Follow-up call scheduled for next week.',
        ]);
        
        ContactNote::create([
            'contact_id' => $contact2->id,
            'user_id' => $user1->id,
            'body' => 'Sent proposal via email. Awaiting response.',
        ]);
        
        ContactNote::create([
            'contact_id' => $contact4->id,
            'user_id' => $user2->id,
            'body' => 'Meeting went well. Discussing contract terms.',
        ]);
        
        // Create contact meta fields
        ContactMeta::create([
            'contact_id' => $contact1->id,
            'key' => 'Company',
            'value' => 'ABC Industries',
        ]);
        
        ContactMeta::create([
            'contact_id' => $contact1->id,
            'key' => 'Position',
            'value' => 'Marketing Director',
        ]);
        
        ContactMeta::create([
            'contact_id' => $contact1->id,
            'key' => 'Lead Source',
            'value' => 'Website Contact Form',
        ]);
        
        ContactMeta::create([
            'contact_id' => $contact2->id,
            'key' => 'Company',
            'value' => 'XYZ Corp',
        ]);
        
        ContactMeta::create([
            'contact_id' => $contact2->id,
            'key' => 'Budget',
            'value' => '$50,000 - $100,000',
        ]);
        
        ContactMeta::create([
            'contact_id' => $contact4->id,
            'key' => 'Company',
            'value' => 'Tech Innovations LLC',
        ]);
        
        ContactMeta::create([
            'contact_id' => $contact4->id,
            'key' => 'Project Type',
            'value' => 'Mobile App Development',
        ]);
        
        ContactMeta::create([
            'contact_id' => $contact5->id,
            'key' => 'Referral',
            'value' => 'David Brown',
        ]);
        
        $this->command->info('Sample data created successfully!');
        $this->command->info('Users created:');
        $this->command->info('- john@example.com (password: password)');
        $this->command->info('- jane@example.com (password: password)');
        $this->command->info('Organizations: Acme Corporation, Tech Solutions Inc');
        $this->command->info('Contacts: 5 contacts with notes and custom fields');
    }
}
