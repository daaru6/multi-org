<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Contacts') }}
            </h2>
            <a href="{{ route('contacts.create', ['organization' => auth()->user()->getCurrentOrganization()->slug]) }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                {{ __('Add Contact') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    
                    <!-- Search Form -->
                    <div class="mb-6">
                        <form method="GET" action="{{ route('contacts.index', ['organization' => auth()->user()->getCurrentOrganization()->slug]) }}" class="flex gap-4">
                            <input type="text" name="search" value="{{ request('search') }}" 
                                   placeholder="Search contacts by name or email..." 
                                   class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            <button type="submit" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                                {{ __('Search') }}
                            </button>
                            @if(request('search'))
                                <a href="{{ route('contacts.index', ['organization' => auth()->user()->getCurrentOrganization()->slug]) }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                                    {{ __('Clear') }}
                                </a>
                            @endif
                        </form>
                    </div>

                    <!-- Contacts Table -->
                    @if($contacts->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avatar</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($contacts as $contact)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @if($contact->avatar_path)
                                                <img class="h-10 w-10 rounded-full" src="{{ Storage::url($contact->avatar_path) }}" alt="{{ $contact->full_name }}">
                                                @else
                                                    <div class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                                                        <span class="text-sm font-medium text-gray-700">{{ substr($contact->first_name, 0, 1) }}{{ substr($contact->last_name, 0, 1) }}</span>
                                                    </div>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900">{{ $contact->full_name }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">{{ $contact->email }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">{{ $contact->phone ?? '-' }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">{{ $contact->created_at->format('M d, Y') }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <a href="{{ route('contacts.show', ['organization' => auth()->user()->getCurrentOrganization()->slug, 'contact' => $contact]) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">View</a>
                                                <a href="{{ route('contacts.edit', ['organization' => auth()->user()->getCurrentOrganization()->slug, 'contact' => $contact]) }}" class="text-yellow-600 hover:text-yellow-900 mr-3">Edit</a>
                                                <a href="{{ route('contacts.duplicate', ['organization' => auth()->user()->getCurrentOrganization()->slug, 'contact' => $contact]) }}" class="text-green-600 hover:text-green-900 mr-3">Duplicate</a>
                                                <form method="POST" action="{{ route('contacts.destroy', ['organization' => auth()->user()->getCurrentOrganization()->slug, 'contact' => $contact]) }}" class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-900" 
                                                            onclick="return confirm('Are you sure you want to delete this contact?')">
                                                        Delete
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="mt-6">
                            {{ $contacts->links() }}
                        </div>
                    @else
                        <div class="text-center py-8">
                            <p class="text-gray-500 text-lg">No contacts found.</p>
                            @if(!request('search'))
                                <a href="{{ route('contacts.create', ['organization' => auth()->user()->getCurrentOrganization()->slug]) }}" class="mt-4 inline-block bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                    {{ __('Create your first contact') }}
                                </a>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>