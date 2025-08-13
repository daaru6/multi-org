<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Organization;
use App\Models\ContactNote;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the dashboard.
     */
    public function index(): View
    {
        $user = auth()->user();
        $currentOrganization = $user->getCurrentOrganization();
        
        // If no organization is selected, return empty stats
        if (!$currentOrganization) {
            return view('dashboard', [
                'totalContacts' => 0,
                'totalOrganizations' => $user->organizations()->count(),
                'totalNotes' => 0,
                'recentContacts' => collect(),
                'recentNotes' => collect(),
                'recentNotesCount' => 0
            ]);
        }
        
        // Get statistics for the current organization
        $totalContacts = Contact::where('organization_id', $currentOrganization->id)->count();
        $totalOrganizations = $user->organizations()->count();
        $totalNotes = ContactNote::whereHas('contact', function ($query) use ($currentOrganization) {
            $query->where('organization_id', $currentOrganization->id);
        })->count();
        
        // Get recent contacts (last 5) for current organization
        $recentContacts = Contact::where('organization_id', $currentOrganization->id)
            ->with('organization')
            ->latest()
            ->take(5)
            ->get();
        
        // Get recent notes (last 5) for current organization
        $recentNotes = ContactNote::whereHas('contact', function ($query) use ($currentOrganization) {
            $query->where('organization_id', $currentOrganization->id);
        })
        ->with(['contact', 'contact.organization'])
        ->latest()
        ->take(5)
        ->get();
        
        return view('dashboard', compact(
            'totalContacts',
            'totalOrganizations', 
            'totalNotes',
            'recentContacts',
            'recentNotes'
        ))->with('recentNotesCount', $totalNotes);
    }
}