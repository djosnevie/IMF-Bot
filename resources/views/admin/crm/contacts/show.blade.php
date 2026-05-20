@extends('layouts.admin')
@section('title', 'Contact — ' . ($contact->display_name ?? $contact->whatsapp_number))
@section('page_title', 'Fiche Contact 360°')

@section('content')
<div class="mb-5 flex items-center justify-between flex-wrap gap-3">
    <a href="{{ route('admin.crm.contacts.index') }}" class="text-sm text-gray-500 hover:text-gray-800 flex items-center gap-2">
        <i class="fas fa-arrow-left"></i> Retour aux contacts
    </a>
    <div class="flex gap-2 flex-wrap">
        @php
            $statusColors = ['lead'=>'bg-gray-100 text-gray-600','prospect'=>'bg-blue-100 text-blue-700','en_cours'=>'bg-amber-100 text-amber-700','client'=>'bg-emerald-100 text-emerald-700','inactif'=>'bg-red-100 text-red-600'];
            $statusLabels = ['lead'=>'Lead','prospect'=>'Prospect','en_cours'=>'En cours','client'=>'Client','inactif'=>'Inactif'];
        @endphp
        <span class="px-3 py-1 rounded-full text-xs font-bold {{ $statusColors[$contact->commercial_status] ?? '' }}">
            {{ $statusLabels[$contact->commercial_status] ?? $contact->commercial_status }}
        </span>
        <span class="px-3 py-1 rounded-full text-xs font-bold bg-indigo-100 text-indigo-700">
            Score : {{ $contact->interest_score }}/100
        </span>
    </div>
</div>

@if(session('success'))
    <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl mb-5 text-sm flex items-center gap-2">
        <i class="fas fa-check-circle"></i> {{ session('success') }}
    </div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-5 gap-6">

    {{-- ====== COLONNE GAUCHE ====== --}}
    <div class="lg:col-span-2 space-y-5">

        {{-- Identité --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="h-2" style="background: linear-gradient(to right, #3B82F6, #6366F1)"></div>
            <div class="p-5">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white font-bold text-lg flex-shrink-0">
                            {{ strtoupper(substr($contact->whatsapp_number, -2)) }}
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-900 text-lg">{{ $contact->display_name ?? $contact->whatsapp_number }}</h3>
                            @if($contact->display_name)
                                <p class="text-sm text-gray-500 flex items-center gap-1"><i class="fab fa-whatsapp text-green-500"></i> {{ $contact->whatsapp_number }}</p>
                            @endif
                            <p class="text-xs text-gray-400 mt-1">Langue : <span class="font-semibold">{{ strtoupper($contact->detected_language ?? 'fr') }}</span></p>
                            <p class="text-xs text-gray-500 mt-0.5">Agent : <span class="font-semibold text-indigo-600">{{ $contact->agent?->name ?? 'Non assigné' }}</span></p>
                        </div>
                    </div>
                    <button onclick="toggleEditContact()" class="p-2 bg-gray-50 hover:bg-gray-100 text-gray-500 hover:text-gray-800 rounded-xl transition-colors" title="Modifier/Enregistrer le contact">
                        <i class="fas fa-edit"></i>
                    </button>
                </div>

                {{-- Formulaire d'édition (caché par défaut) --}}
                <form id="edit-contact-form" action="{{ route('admin.crm.contacts.update', $contact->uuid) }}" method="POST" class="hidden border-t border-gray-100 pt-4 mt-4 space-y-3">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Nom complet / Affichage</label>
                        <input type="text" name="display_name" value="{{ old('display_name', $contact->display_name) }}"
                               class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none"
                               placeholder="Ex: Jean Dupont">
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Langue</label>
                            <select name="detected_language" class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                                <option value="fr" {{ $contact->detected_language === 'fr' ? 'selected' : '' }}>Français</option>
                                <option value="en" {{ $contact->detected_language === 'en' ? 'selected' : '' }}>English</option>
                                <option value="sw" {{ $contact->detected_language === 'sw' ? 'selected' : '' }}>Swahili</option>
                            </select>
                        </div>
                        @if(auth()->user()->hasAnyRole(['super-admin','admin','supervisor']))
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Agent assigné</label>
                            <select name="assigned_to" class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                                <option value="">Non assigné</option>
                                @foreach($agents as $agent)
                                    <option value="{{ $agent->id }}" {{ $contact->assigned_to == $agent->id ? 'selected' : '' }}>{{ $agent->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @else
                        <input type="hidden" name="assigned_to" value="{{ $contact->assigned_to }}">
                        @endif
                    </div>
                    <div class="flex gap-2 pt-2">
                        <button type="submit" class="flex-1 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-semibold transition-colors">
                            Enregistrer
                        </button>
                        <button type="button" onclick="toggleEditContact()" class="px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-xl text-xs font-medium transition-colors">
                            Annuler
                        </button>
                    </div>
                </form>

                <div class="grid grid-cols-2 gap-3 text-sm mt-4">
                    <div class="bg-gray-50 rounded-xl p-3">
                        <p class="text-[10px] text-gray-400 uppercase font-bold tracking-wider">1ère interaction</p>
                        <p class="font-semibold text-gray-700 mt-1">{{ $contact->first_contact_at?->format('d/m/Y') ?? '—' }}</p>
                    </div>
                    <div class="bg-gray-50 rounded-xl p-3">
                        <p class="text-[10px] text-gray-400 uppercase font-bold tracking-wider">Dernier contact</p>
                        <p class="font-semibold text-gray-700 mt-1">{{ $contact->last_contact_at?->diffForHumans() ?? '—' }}</p>
                    </div>
                </div>
            </div>
        </div>


        {{-- Score + sparkline --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <h4 class="font-semibold text-gray-800 mb-3 flex items-center gap-2 text-sm">
                <i class="fas fa-chart-line text-indigo-500"></i> Score d'intérêt
            </h4>
            <div class="flex items-end justify-between mb-2">
                <span class="text-3xl font-bold text-indigo-600">{{ $contact->interest_score }}</span>
                <span class="text-sm text-gray-400">/ 100</span>
            </div>
            <div class="w-full bg-gray-100 rounded-full h-2.5 mb-3">
                <div class="h-2.5 rounded-full transition-all"
                     style="width:{{ $contact->interest_score }}%; background: {{ $contact->interest_score >= 75 ? '#10B981' : ($contact->interest_score >= 40 ? '#F59E0B' : '#EF4444') }}">
                </div>
            </div>
            {{-- Sparkline SVG --}}
            @if($contact->scoreHistory->count() > 1)
            @php
                $scores = $contact->scoreHistory->pluck('score')->toArray();
                $max = max($scores) ?: 1;
                $points = collect($scores)->map(fn($s, $i) => ($i / (count($scores)-1)) * 200 . ',' . (40 - ($s / $max) * 38))->implode(' ');
            @endphp
            <svg viewBox="0 0 200 40" class="w-full h-10">
                <polyline fill="none" stroke="#6366F1" stroke-width="2" points="{{ $points }}" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            @endif
        </div>

        {{-- Changement de stage --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <h4 class="font-semibold text-gray-800 mb-3 text-sm flex items-center gap-2">
                <i class="fas fa-exchange-alt text-amber-500"></i> Pipeline
            </h4>
            <form action="{{ route('admin.crm.contacts.stage', $contact->uuid) }}" method="POST">
                @csrf
                <select name="commercial_status" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm mb-2">
                    @foreach(['lead'=>'Lead','prospect'=>'Prospect','en_cours'=>'En cours','client'=>'Client','inactif'=>'Inactif'] as $val=>$label)
                        <option value="{{ $val }}" {{ $contact->commercial_status===$val?'selected':'' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <button class="w-full py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-lg text-sm font-medium transition-colors">
                    Mettre à jour le stage
                </button>
            </form>
        </div>

        {{-- Tags --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <h4 class="font-semibold text-gray-800 mb-3 text-sm flex items-center gap-2">
                <i class="fas fa-tags text-blue-500"></i> Tags ({{ $contact->tags->count() }})
            </h4>
            <div class="flex flex-wrap gap-2 mb-4">
                @forelse($contact->tags as $tag)
                    <span class="px-2.5 py-1 rounded-full text-xs font-medium {{ $tag->source === 'auto' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }}">
                        {{ $tag->source === 'auto' ? '🤖 ' : '✏️ ' }}{{ $tag->name }}
                    </span>
                @empty
                    <p class="text-xs text-gray-400 italic">Aucun tag pour l'instant.</p>
                @endforelse
            </div>
            @can('crm.tags.manage')
            <form action="{{ route('admin.crm.tags.store') }}" method="POST" class="flex gap-2">
                @csrf
                <input type="hidden" name="contact_uuid" value="{{ $contact->uuid }}">
                <input type="text" name="name" placeholder="nouveau-tag" pattern="[a-z0-9\-\:\_]+"
                       class="flex-1 border border-gray-200 rounded-lg px-3 py-1.5 text-sm">
                <button class="px-3 py-1.5 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors">
                    <i class="fas fa-plus"></i>
                </button>
            </form>
            @endcan
        </div>

        {{-- Résumé IA --}}
        @php $summary = $contact->metadata['last_conversation_summary'] ?? null; @endphp
        @if($summary)
        <div class="bg-gradient-to-br from-purple-50 to-indigo-50 rounded-2xl border border-purple-100 p-5">
            <h4 class="font-semibold text-purple-800 mb-3 text-sm flex items-center gap-2">
                <i class="fas fa-robot text-purple-500"></i> Résumé IA — Dernière conversation
            </h4>
            <div class="space-y-3 text-sm">
                <div>
                    <p class="text-[10px] text-purple-500 uppercase font-bold tracking-wider mb-1">Besoin principal</p>
                    <p class="text-gray-700">{{ $summary['besoin_principal'] ?? '—' }}</p>
                </div>
                @if(!empty($summary['produits_mentionnes']))
                <div>
                    <p class="text-[10px] text-purple-500 uppercase font-bold tracking-wider mb-1">Produits</p>
                    <div class="flex flex-wrap gap-1">
                        @foreach($summary['produits_mentionnes'] as $p)
                            <span class="px-2 py-0.5 bg-white rounded-full text-xs text-purple-700 border border-purple-200">{{ $p }}</span>
                        @endforeach
                    </div>
                </div>
                @endif
                <div class="flex gap-4">
                    <div>
                        <p class="text-[10px] text-purple-500 uppercase font-bold tracking-wider mb-1">Sentiment</p>
                        <span class="px-2 py-1 rounded-full text-xs font-bold {{ $summary['sentiment']==='positif'?'bg-emerald-100 text-emerald-700':($summary['sentiment']==='négatif'?'bg-red-100 text-red-600':'bg-gray-100 text-gray-600') }}">
                            {{ ucfirst($summary['sentiment'] ?? '—') }}
                        </span>
                    </div>
                    <div>
                        <p class="text-[10px] text-purple-500 uppercase font-bold tracking-wider mb-1">Recommandation</p>
                        <span class="text-xs text-gray-700">{{ str_replace('_', ' ', $summary['recommandation'] ?? '—') }}</span>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- Notes agents --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <h4 class="font-semibold text-gray-800 mb-3 text-sm flex items-center gap-2">
                <i class="fas fa-sticky-note text-yellow-500"></i> Notes agents
            </h4>
            @php $notes = $contact->metadata['notes'] ?? []; @endphp
            @forelse(array_reverse($notes) as $note)
            <div class="bg-yellow-50 rounded-xl p-3 mb-2 text-sm border border-yellow-100">
                <p class="text-gray-700">{{ $note['content'] }}</p>
                <p class="text-[10px] text-gray-400 mt-1">{{ $note['author'] }} · {{ \Carbon\Carbon::parse($note['date'])->format('d/m/Y H:i') }}</p>
            </div>
            @empty
            <p class="text-xs text-gray-400 italic mb-3">Aucune note.</p>
            @endforelse
            <form action="{{ route('admin.crm.contacts.note', $contact->uuid) }}" method="POST" class="mt-3">
                @csrf
                <textarea name="note" rows="2" placeholder="Ajouter une note..." maxlength="1000"
                          class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm resize-none mb-2"></textarea>
                <button class="w-full py-2 bg-yellow-400 hover:bg-yellow-500 text-white rounded-lg text-sm font-medium transition-colors">
                    <i class="fas fa-plus mr-1"></i> Ajouter
                </button>
            </form>
        </div>
    </div>

    {{-- ====== COLONNE DROITE — Timeline 360° ====== --}}
    <div class="lg:col-span-3">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h4 class="font-bold text-gray-800 flex items-center gap-2">
                    <i class="fas fa-history text-blue-500"></i> Timeline 360°
                </h4>
                <span class="text-xs text-gray-400">{{ $timeline->count() }} événement(s)</span>
            </div>
            <div class="p-6 space-y-0 max-h-[700px] overflow-y-auto">
                @forelse($timeline as $event)
                @php
                    $colors = ['blue'=>'border-blue-300 bg-blue-50','purple'=>'border-purple-300 bg-purple-50','orange'=>'border-orange-300 bg-orange-50','green'=>'border-green-300 bg-green-50'];
                    $iconColors = ['blue'=>'bg-blue-500','purple'=>'bg-purple-500','orange'=>'bg-orange-500','green'=>'bg-green-500'];
                @endphp
                <div class="flex gap-4 pb-6 relative">
                    {{-- Ligne verticale --}}
                    @if(!$loop->last)
                    <div class="absolute left-4 top-8 bottom-0 w-px bg-gray-100"></div>
                    @endif
                    {{-- Icône --}}
                    <div class="w-8 h-8 rounded-full flex-shrink-0 flex items-center justify-center text-white text-xs {{ $iconColors[$event['color']] ?? 'bg-gray-400' }} shadow-sm z-10">
                        <i class="fas {{ $event['icon'] }}"></i>
                    </div>
                    {{-- Contenu --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between gap-2">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ $event['label'] }}</p>
                            <p class="text-[10px] text-gray-400 flex-shrink-0">{{ $event['date']->format('d/m H:i') }}</p>
                        </div>
                        <p class="text-sm text-gray-700 mt-1 leading-relaxed">{{ $event['content'] }}</p>
                    </div>
                </div>
                @empty
                <div class="text-center py-12 text-gray-400">
                    <i class="fas fa-history text-4xl mb-3 block"></i>
                    <p class="text-sm">Aucun événement dans la timeline.</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function toggleEditContact() {
        const form = document.getElementById('edit-contact-form');
        if (form) {
            form.classList.toggle('hidden');
        }
    }
</script>
@endpush
