<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\ContactMeta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ContactController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index($organization, Request $request)
    {
        // Check if user can view contacts
        if (!auth()->user()->canViewContacts()) {
            abort(403, 'You do not have permission to view contacts.');
        }
        
        $query = Contact::with(['creator', 'updater']);
        
        // Search functionality
        if ($request->filled('search')) {
            $query->search($request->search);
        }
        
        // Sort by name by default
        $query->orderBy('first_name')->orderBy('last_name');
        
        $contacts = $query->paginate(15);
        
        return view('contacts.index', compact('contacts'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create($organization)
    {
        // Check if user can create contacts
        if (!auth()->user()->canCreateContacts()) {
            abort(403, 'You do not have permission to create contacts.');
        }
        
        return view('contacts.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store($organization, Request $request)
    {
        // Check if user can create contacts
        if (!auth()->user()->canCreateContacts()) {
            abort(403, 'You do not have permission to create contacts.');
        }
        
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'avatar' => 'nullable|image|max:2048',
            'meta' => 'nullable|array|max:5',
            'meta.*.key' => 'required_with:meta|string|max:255',
            'meta.*.value' => 'required_with:meta|string|max:1000',
        ]);
        
        // Check for duplicate email (case-insensitive) within current organization
        if (!empty($validated['email'])) {
            $existingContact = Contact::where('organization_id', session('current_organization_id'))
                ->whereRaw('LOWER(email) = ?', [strtolower($validated['email'])])
                ->first();
                
            if ($existingContact) {
                // Log the duplicate attempt
                Log::info('duplicate_contact_blocked', [
                    'org_id' => session('current_organization_id'),
                    'email' => $validated['email'],
                    'user_id' => auth()->id(),
                    'existing_contact_id' => $existingContact->id
                ]);
                
                // Return 422 with specific JSON format
                if ($request->expectsJson()) {
                    return response()->json([
                        'code' => 'DUPLICATE_EMAIL',
                        'existing_contact_id' => $existingContact->id
                    ], 422);
                }
                
                // For web requests, redirect to existing contact with message
                return redirect()->route('contacts.show', [
                    'organization' => auth()->user()->getCurrentOrganization()->slug,
                    'contact' => $existingContact
                ])->with('error', 'Duplicate detected. No new contact was created.');
            }
        }
        
        $contact = null;
        DB::transaction(function () use ($validated, $request, &$contact) {
            // Handle avatar upload
            if ($request->hasFile('avatar')) {
                $validated['avatar_path'] = $request->file('avatar')
                    ->store('avatars', 'public');
            }
            
            $contact = Contact::create($validated);
            
            // Store meta fields
            if (!empty($validated['meta'])) {
                foreach ($validated['meta'] as $meta) {
                    if (!empty($meta['key']) && !empty($meta['value'])) {
                        ContactMeta::create([
                            'contact_id' => $contact->id,
                            'key' => $meta['key'],
                            'value' => $meta['value'],
                        ]);
                    }
                }
            }
        });
        
        // Return JSON response for API requests
        if ($request->expectsJson()) {
            return response()->json($contact->load('meta'), 201);
        }
        
        return redirect()->route('contacts.index', ['organization' => auth()->user()->getCurrentOrganization()->slug])
            ->with('success', 'Contact created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show($organization, Contact $contact)
    {
        // Check if user can view contacts
        if (!auth()->user()->canViewContacts()) {
            abort(403, 'You do not have permission to view contacts.');
        }
        
        $contact->load(['notes.user', 'meta', 'creator', 'updater']);
        
        return view('contacts.show', compact('contact'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($organization, Contact $contact)
    {
        // Check if user can edit contacts
        if (!auth()->user()->canEditContacts()) {
            abort(403, 'You do not have permission to edit contacts.');
        }
        
        $contact->load('meta');
        
        return view('contacts.edit', compact('contact'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update($organization, Request $request, Contact $contact)
    {
        // Check if user can edit contacts
        if (!auth()->user()->canEditContacts()) {
            abort(403, 'You do not have permission to edit contacts.');
        }
        
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('contacts', 'email')
                    ->where('organization_id', session('current_organization_id'))
                    ->ignore($contact->id)
            ],
            'phone' => 'nullable|string|max:255',
            'avatar' => 'nullable|image|max:2048',
            'meta' => 'nullable|array|max:5',
            'meta.*.key' => 'required_with:meta|string|max:255',
            'meta.*.value' => 'required_with:meta|string|max:1000',
        ]);
        
        DB::transaction(function () use ($validated, $request, $contact) {
            // Handle avatar upload
            if ($request->hasFile('avatar')) {
                $validated['avatar_path'] = $request->file('avatar')
                    ->store('avatars', 'public');
            }
            
            $contact->update($validated);
            
            // Update meta fields
            $contact->meta()->delete(); // Remove existing meta
            
            if (!empty($validated['meta'])) {
                foreach ($validated['meta'] as $meta) {
                    if (!empty($meta['key']) && !empty($meta['value'])) {
                        ContactMeta::create([
                            'contact_id' => $contact->id,
                            'key' => $meta['key'],
                            'value' => $meta['value'],
                        ]);
                    }
                }
            }
        });
        
        return redirect()->route('contacts.show', ['organization' => auth()->user()->getCurrentOrganization()->slug, 'contact' => $contact])
            ->with('success', 'Contact updated successfully.');
    }

    /**
     * Duplicate an existing contact.
     */
    public function duplicate($organization, Contact $contact)
    {
        // Check if user can create contacts (needed for duplication)
        if (!auth()->user()->canCreateContacts()) {
            abort(403, 'You do not have permission to duplicate contacts.');
        }
        
        $contact->load('meta');
        
        return view('contacts.create', [
            'contact' => $contact,
            'isDuplicate' => true
        ]);
    }
    
    /**
     * Remove the specified resource from storage.
     */
    public function destroy($organization, Contact $contact)
    {
        // Check if user can delete contacts
        if (!auth()->user()->canDeleteContacts()) {
            abort(403, 'You do not have permission to delete contacts.');
        }
        
        $contact->delete();
        
        return redirect()->route('contacts.index', ['organization' => auth()->user()->getCurrentOrganization()->slug])
            ->with('success', 'Contact deleted successfully.');
    }
}
