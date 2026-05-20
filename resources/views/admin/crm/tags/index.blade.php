@extends('layouts.admin')
@section('title', 'CRM — Tags')
@section('page_title', 'Gestion des Tags')

@section('content')
@if(session('success'))
    <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl mb-5 text-sm flex items-center gap-2">
        <i class="fas fa-check-circle"></i> {{ session('success') }}
    </div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Liste des tags --}}
    <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-bold text-gray-800 flex items-center gap-2 text-sm">
                <i class="fas fa-tags text-blue-500"></i> Tags existants
                <span class="text-gray-400 font-normal">({{ $tags->total() }})</span>
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500 font-semibold">
                    <tr>
                        <th class="px-5 py-3">Tag</th>
                        <th class="px-5 py-3">Source</th>
                        <th class="px-5 py-3 text-center">Occurrences</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($tags as $tag)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-5 py-3">
                            <span class="px-2.5 py-1 rounded-full text-xs font-mono font-medium
                                {{ $tag->source === 'auto' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }}">
                                {{ $tag->name }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-xs text-gray-500">
                            @if($tag->source === 'auto')
                                <span class="flex items-center gap-1"><i class="fas fa-robot text-purple-400"></i> Automatique (IA)</span>
                            @else
                                <span class="flex items-center gap-1"><i class="fas fa-user-edit text-blue-400"></i> Manuel</span>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-center">
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-gray-100 text-gray-700 text-xs font-bold">
                                {{ $tag->occurrences }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="px-5 py-12 text-center text-gray-400">
                            <i class="fas fa-tags text-3xl mb-3 block opacity-40"></i>
                            <p class="text-sm">Aucun tag créé.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($tags->hasPages())
            <div class="p-5 border-t border-gray-100">{{ $tags->links() }}</div>
        @endif
    </div>

    {{-- Actions --}}
    <div class="space-y-5">
        {{-- Fusion de tags --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <h4 class="font-semibold text-gray-800 text-sm mb-4 flex items-center gap-2">
                <i class="fas fa-compress-alt text-amber-500"></i> Fusionner deux tags
            </h4>
            <form action="{{ route('admin.crm.tags.merge') }}" method="POST" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Tag source (à supprimer)</label>
                    <input type="text" name="source_tag" required list="tags-list"
                           class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 outline-none font-mono"
                           placeholder="ancien-tag">
                </div>
                <div class="flex justify-center text-gray-400">
                    <i class="fas fa-arrow-down"></i>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Tag cible (à conserver)</label>
                    <input type="text" name="target_tag" required list="tags-list"
                           class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 outline-none font-mono"
                           placeholder="nouveau-tag">
                </div>
                <datalist id="tags-list">
                    @foreach($tags as $tag)
                        <option value="{{ $tag->name }}">
                    @endforeach
                </datalist>
                <button type="submit" onclick="return confirm('Fusionner ces deux tags ? Cette action est irréversible.')"
                        class="w-full py-2.5 bg-amber-500 hover:bg-amber-600 text-white rounded-xl text-sm font-semibold transition-colors">
                    <i class="fas fa-compress-alt mr-1"></i> Fusionner
                </button>
            </form>
        </div>

        {{-- Légende --}}
        <div class="bg-gray-50 rounded-2xl p-4 text-xs text-gray-500 space-y-2 border border-gray-100">
            <p class="font-semibold text-gray-700 mb-2">Légende des sources</p>
            <p><span class="inline-block px-2 py-0.5 bg-purple-100 text-purple-700 rounded-full text-[10px] font-mono mr-1">auto</span> Détecté automatiquement par Sophie (IA)</p>
            <p><span class="inline-block px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full text-[10px] font-mono mr-1">manual</span> Ajouté manuellement par un agent</p>
        </div>
    </div>
</div>
@endsection
