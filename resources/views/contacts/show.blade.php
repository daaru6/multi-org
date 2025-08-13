<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $contact->full_name }}
            </h2>
            <div class="flex space-x-2">
                <a href="{{ route('contacts.edit', ['organization' => auth()->user()->getCurrentOrganization()->slug, 'contact' => $contact]) }}" class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded">
                    {{ __('Edit') }}
                </a>
                <a href="{{ route('contacts.index', ['organization' => auth()->user()->getCurrentOrganization()->slug]) }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                    {{ __('Back to Contacts') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <!-- Contact Information -->
                <div class="lg:col-span-2">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                        <div class="p-6 text-gray-900">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Contact Information</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Name</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ $contact->full_name }}</p>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Email</label>
                                    <p class="mt-1 text-sm text-gray-900">
                                        <a href="mailto:{{ $contact->email }}" class="text-blue-600 hover:text-blue-900">{{ $contact->email }}</a>
                                    </p>
                                </div>
                                
                                @if($contact->phone)
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Phone</label>
                                    <p class="mt-1 text-sm text-gray-900">
                                        <a href="tel:{{ $contact->phone }}" class="text-blue-600 hover:text-blue-900">{{ $contact->phone }}</a>
                                    </p>
                                </div>
                                @endif
                                
                                @if($contact->address)
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700">Address</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ $contact->address }}</p>
                                </div>
                                @endif
                            </div>
                            
                            <!-- Custom Fields -->
                            @if($contact->meta->count() > 0)
                                <div class="mt-6">
                                    <h4 class="text-md font-medium text-gray-900 mb-3">Custom Fields</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        @foreach($contact->meta as $meta)
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">{{ $meta->key }}</label>
                                                <p class="mt-1 text-sm text-gray-900">{{ $meta->value }}</p>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                            
                            <!-- Contact Metadata -->
                            <div class="mt-6 pt-6 border-t border-gray-200">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-500">
                                    <div>
                                        <span class="font-medium">Created:</span> {{ $contact->created_at->format('M d, Y \a\t g:i A') }}
                                        @if($contact->creator)
                                            <span class="block">by {{ $contact->creator->name }}</span>
                                        @endif
                                    </div>
                                    <div>
                                        <span class="font-medium">Updated:</span> {{ $contact->updated_at->format('M d, Y \a\t g:i A') }}
                                        @if($contact->updater)
                                            <span class="block">by {{ $contact->updater->name }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Notes Section -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6 text-gray-900">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Notes</h3>
                            
                            <!-- Add Note Form -->
                            <form method="POST" action="{{ route('contacts.notes.store', ['organization' => auth()->user()->getCurrentOrganization()->slug, 'contact' => $contact]) }}" class="mb-6">
                                @csrf
                                <div class="mb-4">
                                    <textarea name="body" rows="3" placeholder="Add a note..." required
                                              class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">{{ old('body') }}</textarea>
                                    @error('body')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                    {{ __('Add Note') }}
                                </button>
                            </form>
                            
                            <!-- Notes List -->
                            @if($contact->notes->count() > 0)
                                <div class="space-y-4">
                                    @foreach($contact->notes->sortByDesc('created_at') as $note)
                                        <div class="border border-gray-200 rounded-lg p-4">
                                            <div class="flex justify-between items-start">
                                                <div class="flex-1">
                                                    <p class="text-gray-900">{{ $note->body }}</p>
                                                    <div class="mt-2 text-sm text-gray-500">
                                                        <span class="font-medium">{{ $note->user->name }}</span>
                                                        <span class="mx-1">•</span>
                                                        <span>{{ $note->created_at->format('M d, Y \a\t g:i A') }}</span>
                                                    </div>
                                                </div>
                                                @if(auth()->id() === $note->user_id)
                                                    <form method="POST" action="{{ route('contacts.notes.destroy', ['organization' => auth()->user()->getCurrentOrganization()->slug, 'contact' => $contact, 'note' => $note]) }}" class="ml-4">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-red-600 hover:text-red-900 text-sm"
                                                                onclick="return confirm('Are you sure you want to delete this note?')">
                                                            Delete
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-gray-500 text-center py-4">No notes yet. Add the first note above.</p>
                            @endif
                        </div>
                    </div>
                </div>
                
                <!-- Sidebar -->
                <div class="lg:col-span-1">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6 text-gray-900">
                            <!-- Avatar -->
                            <div class="text-center mb-6">
                                @if($contact->avatar_path)
                                <img class="h-32 w-32 rounded-full mx-auto" src="{{ Storage::url($contact->avatar_path) }}" alt="{{ $contact->full_name }}">
                            @else
                                    <div class="h-32 w-32 rounded-full bg-gray-300 flex items-center justify-center mx-auto">
                                        <span class="text-3xl font-medium text-gray-700">{{ substr($contact->first_name, 0, 1) }}{{ substr($contact->last_name, 0, 1) }}</span>
                                    </div>
                                @endif
                                <h3 class="mt-4 text-lg font-medium text-gray-900">{{ $contact->full_name }}</h3>
                                <p class="text-sm text-gray-500">{{ $contact->email }}</p>
                            </div>
                            
                            <!-- Quick Actions -->
                            <div class="space-y-3">
                                <a href="mailto:{{ $contact->email }}" class="w-full bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded text-center block">
                                    {{ __('Send Email') }}
                                </a>
                                @if($contact->phone)
                                    <a href="tel:{{ $contact->phone }}" class="w-full bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded text-center block">
                                        {{ __('Call') }}
                                    </a>
                                @endif
                                <a href="{{ route('contacts.edit', ['organization' => auth()->user()->getCurrentOrganization()->slug, 'contact' => $contact]) }}" class="w-full bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded text-center block">
                                    {{ __('Edit Contact') }}
                                </a>
                            </div>
                            
                            <!-- Statistics -->
                            <div class="mt-6 pt-6 border-t border-gray-200">
                                <h4 class="text-sm font-medium text-gray-900 mb-3">Statistics</h4>
                                <div class="space-y-2 text-sm text-gray-600">
                                    <div class="flex justify-between">
                                        <span>Notes:</span>
                                        <span class="font-medium">{{ $contact->notes->count() }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span>Custom Fields:</span>
                                        <span class="font-medium">{{ $contact->meta->count() }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>