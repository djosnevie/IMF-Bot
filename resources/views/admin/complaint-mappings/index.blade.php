@extends('layouts.admin')

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-6">Mapping Types de Plaintes ↔ Agents</h1>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white shadow-md rounded my-6 overflow-x-auto">
        <table class="min-w-full leading-normal">
            <thead>
                <tr>
                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        Type de Plainte
                    </th>
                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        Agents Affectés
                    </th>
                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        Actions
                    </th>
                </tr>
            </thead>
            <tbody>
                @foreach($complaintTypes as $type)
                <tr>
                    <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                        <p class="text-gray-900 whitespace-no-wrap font-bold">{{ $type->name }}</p>
                        <p class="text-gray-500 whitespace-no-wrap text-xs">Code: {{ $type->code }}</p>
                    </td>
                    <form action="{{ route('admin.complaint-mappings.sync', $type) }}" method="POST">
                        @csrf
                        <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm w-1/2">
                            <!-- Alpine.js Multi-select component -->
                            <div x-data="multiSelect({
                                options: [
                                    @foreach($agents as $agent)
                                        { value: '{{ $agent->id }}', label: '{{ addslashes($agent->name) }}' },
                                    @endforeach
                                ],
                                selected: {{ json_encode($type->users->pluck('id')->toArray()) }}
                            })" class="relative">
                                
                                <template x-for="val in selected" :key="val">
                                    <input type="hidden" name="user_ids[]" :value="val">
                                </template>

                                <div @click="open = !open" class="w-full border rounded p-2 flex flex-wrap gap-2 min-h-[42px] cursor-text bg-gray-50">
                                    <template x-if="selected.length === 0">
                                        <span class="text-gray-400 mt-1">Sélectionner des agents...</span>
                                    </template>
                                    <template x-for="val in selected" :key="val">
                                        <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2 py-1 rounded flex items-center">
                                            <span x-text="options.find(o => o.value == val)?.label"></span>
                                            <button type="button" @click.stop="toggle(val)" class="ml-1 text-blue-600 hover:text-blue-900 font-bold">&times;</button>
                                        </span>
                                    </template>
                                </div>

                                <div x-show="open" @click.away="open = false" class="absolute z-10 w-full bg-white border border-gray-300 mt-1 rounded shadow-lg max-h-60 overflow-y-auto" style="display: none;">
                                    <template x-for="option in options" :key="option.value">
                                        <div @click="toggle(option.value)" 
                                             class="cursor-pointer px-4 py-2 hover:bg-blue-50 flex items-center justify-between"
                                             :class="{'bg-blue-100 font-semibold': selected.includes(option.value)}">
                                            <span x-text="option.label"></span>
                                            <span x-show="selected.includes(option.value)" class="text-blue-600">✓</span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                Enregistrer
                            </button>
                        </td>
                    </form>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('multiSelect', (config) => ({
            open: false,
            options: config.options,
            selected: config.selected.map(String),
            
            toggle(value) {
                value = String(value);
                if (this.selected.includes(value)) {
                    this.selected = this.selected.filter(val => val !== value);
                } else {
                    this.selected.push(value);
                }
            }
        }))
    })
</script>
@endsection
