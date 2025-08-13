<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class OrganizationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $organizations = auth()->user()->organizations()->paginate(10);
        
        return view('organizations.index', compact('organizations'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('organizations.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9-]+$/',
                Rule::unique('organizations', 'slug')
            ],
        ]);

        // Generate slug if not provided
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
            
            // Ensure uniqueness
            $originalSlug = $validated['slug'];
            $counter = 1;
            while (Organization::where('slug', $validated['slug'])->exists()) {
                $validated['slug'] = $originalSlug . '-' . $counter;
                $counter++;
            }
        }

        $organization = Organization::create([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'owner_user_id' => auth()->id(),
        ]);

        // Attach user as admin
        $organization->users()->attach(auth()->id(), ['role' => 'Admin']);
        
        // Assign Admin role using Spatie
        auth()->user()->assignRole('Admin');

        // Set as current organization
        session(['current_organization_id' => $organization->id]);

        return redirect()->route('dashboard')
            ->with('success', 'Organization created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Organization $organization)
    {
        // Check if user has access to this organization
        if (!auth()->user()->organizations()->where('organizations.id', $organization->id)->exists()) {
            abort(403, 'You do not have access to this organization.');
        }

        $organization->load(['users', 'contacts']);
        
        return view('organizations.show', compact('organization'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Organization $organization)
    {
        // Check if user is admin of this organization
        if (!auth()->user()->hasRole('Admin') || $organization->owner_user_id !== auth()->id()) {
            abort(403, 'You do not have permission to edit this organization.');
        }

        return view('organizations.edit', compact('organization'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Organization $organization)
    {
        // Check if user is admin of this organization
        if (!auth()->user()->hasRole('Admin') || $organization->owner_user_id !== auth()->id()) {
            abort(403, 'You do not have permission to edit this organization.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9-]+$/',
                Rule::unique('organizations', 'slug')->ignore($organization->id)
            ],
        ]);

        $organization->update($validated);

        return redirect()->route('organizations.show', $organization)
            ->with('success', 'Organization updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Organization $organization)
    {
        // Check if user is owner of this organization
        if ($organization->owner_user_id !== auth()->id()) {
            abort(403, 'You do not have permission to delete this organization.');
        }

        // Don't allow deletion if it's the user's only organization
        if (auth()->user()->organizations()->count() <= 1) {
            return redirect()->route('organizations.index')
                ->with('error', 'You cannot delete your only organization.');
        }

        $organization->delete();

        // Switch to another organization
        $nextOrg = auth()->user()->organizations()->first();
        if ($nextOrg) {
            session(['current_organization_id' => $nextOrg->id]);
        } else {
            session()->forget('current_organization_id');
        }

        return redirect()->route('dashboard')
            ->with('success', 'Organization deleted successfully.');
    }

    /**
     * Switch to a different organization
     */
    public function switch($slug)
    {
        $organization = auth()->user()->organizations()
            ->where('slug', $slug)
            ->first();

        if (!$organization) {
            abort(403, 'You do not have access to this organization.');
        }

        session(['current_organization_id' => $organization->id]);

        return redirect()->route('dashboard')
            ->with('success', 'Switched to ' . $organization->name);
    }
}