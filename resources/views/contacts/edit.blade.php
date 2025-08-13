<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Edit Contact: :name', ['name' => $contact->full_name]) }}
            </h2>
            <div class="flex space-x-2">
                <a href="{{ route('contacts.show', ['organization' => auth()->user()->getCurrentOrganization()->slug, 'contact' => $contact]) }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    {{ __('View Contact') }}
                </a>
                <a href="{{ route('contacts.index', ['organization' => auth()->user()->getCurrentOrganization()->slug]) }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                    {{ __('Back to Contacts') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    
                    <form method="POST" action="{{ route('contacts.update', ['organization' => auth()->user()->getCurrentOrganization()->slug, 'contact' => $contact]) }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        
                        <!-- Current Avatar -->
                        @if($contact->avatar_path)
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Current Avatar</label>
                                <img class="h-20 w-20 rounded-full" src="{{ Storage::url($contact->avatar_path) }}" alt="{{ $contact->full_name }}">
                            </div>
                        @endif
                        
                        <!-- Basic Information -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <label for="first_name" class="block text-sm font-medium text-gray-700">First Name *</label>
                                <input type="text" name="first_name" id="first_name" value="{{ old('first_name', $contact->first_name) }}" required
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                @error('first_name')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <div>
                                <label for="last_name" class="block text-sm font-medium text-gray-700">Last Name *</label>
                                <input type="text" name="last_name" id="last_name" value="{{ old('last_name', $contact->last_name) }}" required
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                @error('last_name')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700">Email *</label>
                                <input type="email" name="email" id="email" value="{{ old('email', $contact->email) }}" required
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                @error('email')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <div>
                                <label for="phone" class="block text-sm font-medium text-gray-700">Phone</label>
                                <input type="text" name="phone" id="phone" value="{{ old('phone', $contact->phone) }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                @error('phone')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        
                        <!-- Avatar Upload -->
                        <div class="mb-6">
                            <label for="avatar" class="block text-sm font-medium text-gray-700">Avatar {{ $contact->avatar_path ? '(Upload new to replace)' : '' }}</label>
                            <input type="file" name="avatar" id="avatar" accept="image/*"
                                   class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                            @error('avatar')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <!-- Address -->
                        <div class="mb-6">
                            <label for="address" class="block text-sm font-medium text-gray-700">Address</label>
                            <textarea name="address" id="address" rows="3"
                                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">{{ old('address', $contact->address) }}</textarea>
                            @error('address')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <!-- Custom Fields -->
                        <div class="mb-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Custom Fields (Max 5)</h3>
                            <div id="custom-fields">
                                @if($contact->meta->count() > 0)
                                    @foreach($contact->meta as $index => $meta)
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4 custom-field-row">
                                            <div>
                                                <input type="text" name="meta_keys[]" value="{{ old('meta_keys.'.$index, $meta->key) }}" placeholder="Field Name"
                                                       class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                            </div>
                                            <div class="flex gap-2">
                                                <input type="text" name="meta_values[]" value="{{ old('meta_values.'.$index, $meta->value) }}" placeholder="Field Value"
                                                       class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                                <button type="button" onclick="removeCustomField(this)" class="text-red-600 hover:text-red-900">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4 custom-field-row">
                                        <div>
                                            <input type="text" name="meta_keys[]" placeholder="Field Name"
                                                   class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                        </div>
                                        <div class="flex gap-2">
                                            <input type="text" name="meta_values[]" placeholder="Field Value"
                                                   class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                            <button type="button" onclick="removeCustomField(this)" class="text-red-600 hover:text-red-900">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                            @if($contact->meta->count() < 5)
                                <button type="button" onclick="addCustomField()" class="mt-2 text-blue-600 hover:text-blue-900">
                                    + Add Custom Field
                                </button>
                            @endif
                        </div>
                        
                        <!-- Submit Buttons -->
                        <div class="flex justify-end space-x-4">
                            <a href="{{ route('contacts.show', ['organization' => auth()->user()->getCurrentOrganization()->slug, 'contact' => $contact]) }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                                {{ __('Cancel') }}
                            </a>
                            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                {{ __('Update Contact') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function addCustomField() {
            const container = document.getElementById('custom-fields');
            const fieldCount = container.querySelectorAll('.custom-field-row').length;
            
            if (fieldCount >= 5) {
                alert('Maximum 5 custom fields allowed.');
                return;
            }
            
            const newField = document.createElement('div');
            newField.className = 'grid grid-cols-1 md:grid-cols-2 gap-4 mb-4 custom-field-row';
            newField.innerHTML = `
                <div>
                    <input type="text" name="meta_keys[]" placeholder="Field Name"
                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                </div>
                <div class="flex gap-2">
                    <input type="text" name="meta_values[]" placeholder="Field Value"
                           class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                    <button type="button" onclick="removeCustomField(this)" class="text-red-600 hover:text-red-900">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                    </button>
                </div>
            `;
            container.appendChild(newField);
        }
        
        function removeCustomField(button) {
            const container = document.getElementById('custom-fields');
            const fieldCount = container.querySelectorAll('.custom-field-row').length;
            
            if (fieldCount > 1) {
                button.closest('.custom-field-row').remove();
            }
        }
    </script>
</x-app-layout>