<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\ContactNote;
use Illuminate\Http\Request;

class ContactNoteController extends Controller
{
    /**
     * Store a new note for a contact.
     */
    public function store($organization, Request $request, Contact $contact)
    {
        $validated = $request->validate([
            'body' => 'required|string|max:2000',
        ]);
        
        ContactNote::create([
            'contact_id' => $contact->id,
            'body' => $validated['body'],
        ]);
        
        return redirect()->route('contacts.show', ['organization' => auth()->user()->getCurrentOrganization()->slug, 'contact' => $contact])
            ->with('success', 'Note added successfully.');
    }
    
    /**
     * Delete a contact note.
     */
    public function destroy($organization, Contact $contact, ContactNote $note)
    {
        // Ensure the note belongs to the contact
        if ($note->contact_id !== $contact->id) {
            abort(404);
        }
        
        $note->delete();
        
        return redirect()->route('contacts.show', ['organization' => auth()->user()->getCurrentOrganization()->slug, 'contact' => $contact])
            ->with('success', 'Note deleted successfully.');
    }
}
