@extends('layouts.admin')
@section('title', 'Modifier la Campagne')
@section('page_title', 'Modifier la Campagne')

@section('content')
<div class="mb-5">
    <a href="{{ route('admin.crm.campaigns.index') }}" class="text-sm text-gray-500 hover:text-gray-800 flex items-center gap-2">
        <i class="fas fa-arrow-left"></i> Retour aux campagnes
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Formulaire --}}
    <div class="lg:col-span-2">
        <form action="{{ route('admin.crm.campaigns.update', $campaign->id) }}" method="POST" id="campaign-form">
            @csrf
            @method('PUT')

            {{-- Informations générales --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-5">
                <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2 text-sm">
                    <i class="fas fa-info-circle text-blue-500"></i> Informations générales
                </h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1.5">Nom de la campagne *</label>
                        <input type="text" name="name" value="{{ old('name', $campaign->name) }}" required
                               class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                               placeholder="Ex : Relance clients inactifs — Mai 2026">
                        @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1.5">Date & heure d'envoi (optionnel)</label>
                        <input type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at', $campaign->scheduled_at?->format('Y-m-d\TH:i')) }}"
                               class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                        <p class="text-xs text-gray-400 mt-1">Laissez vide pour sauvegarder en brouillon.</p>
                        @error('scheduled_at')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            {{-- Template du message --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-5">
                <h3 class="font-bold text-gray-800 mb-2 flex items-center gap-2 text-sm">
                    <i class="fas fa-comment-dots text-purple-500"></i> Message WhatsApp
                </h3>
                <div class="flex flex-wrap gap-2 mb-3">
                    @foreach(['{nom}'=>'Nom du contact','{produit}'=>'Dernier produit consulté','{score}'=>'Score d\'intérêt','{statut}'=>'Statut pipeline'] as $var => $desc)
                    <button type="button" onclick="insertVar('{{ $var }}')"
                            class="px-2.5 py-1 bg-purple-50 hover:bg-purple-100 text-purple-700 rounded-lg text-xs font-mono transition-colors border border-purple-100">
                        {{ $var }}
                        <span class="text-purple-400 ml-1 font-sans">{{ $desc }}</span>
                    </button>
                    @endforeach
                </div>
                <textarea id="message_template" name="message_template" rows="6" required
                          class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm font-mono focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none resize-none"
                          placeholder="Bonjour {nom}, nous avons remarqué votre intérêt pour {produit}...">{{ old('message_template', $campaign->message_template) }}</textarea>
                <p class="text-xs text-gray-400 mt-1.5">
                    ⚠️ <strong>Contrainte Meta :</strong> Pour contacter des clients hors fenêtre de 24h, utilisez uniquement des templates approuvés dans Meta Business Manager.
                </p>
                @error('message_template')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Ciblage --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-5">
                <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2 text-sm">
                    <i class="fas fa-crosshairs text-amber-500"></i> Ciblage des contacts
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1.5">Tags (au moins un)</label>
                        <div class="border border-gray-200 rounded-xl p-3 max-h-40 overflow-y-auto space-y-1.5">
                            @foreach($availableTags as $tag)
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="criteria_tags[]" value="{{ $tag }}"
                                       class="rounded border-gray-300 text-blue-600"
                                       {{ in_array($tag, old('criteria_tags', $campaign->targeting_criteria['tags'] ?? [])) ? 'checked' : '' }}>
                                <span class="text-xs text-gray-700 font-mono">{{ $tag }}</span>
                            </label>
                            @endforeach
                            @if($availableTags->isEmpty())
                                <p class="text-xs text-gray-400 italic">Aucun tag disponible.</p>
                            @endif
                        </div>
                    </div>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1.5">Score minimum</label>
                            <input type="number" name="criteria_min_score" min="0" max="100"
                                   value="{{ old('criteria_min_score', $campaign->targeting_criteria['min_score'] ?? '') }}"
                                   class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 outline-none"
                                   placeholder="Ex : 40">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1.5">Inactivité minimum (jours)</label>
                            <input type="number" name="criteria_inactivity_days" min="1"
                                   value="{{ old('criteria_inactivity_days', $campaign->targeting_criteria['max_inactivity_days'] ?? '') }}"
                                   class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 outline-none"
                                   placeholder="Ex : 14">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1.5">Statuts ciblés</label>
                            <div class="flex flex-wrap gap-2">
                                @php $tgtStatuses = $campaign->targeting_criteria['statuses'] ?? []; @endphp
                                @foreach(['lead'=>'Lead','prospect'=>'Prospect','en_cours'=>'En cours','client'=>'Client','inactif'=>'Inactif'] as $val=>$label)
                                <label class="flex items-center gap-1.5 cursor-pointer">
                                    <input type="checkbox" name="criteria_statuses[]" value="{{ $val }}"
                                           class="rounded border-gray-300 text-blue-600"
                                           {{ in_array($val, old('criteria_statuses', $tgtStatuses)) ? 'checked' : '' }}>
                                    <span class="text-xs text-gray-700">{{ $label }}</span>
                                </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex gap-3">
                <button type="submit"
                        class="flex-1 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-semibold transition-colors shadow-sm">
                    <i class="fas fa-save mr-2"></i> Mettre à jour la campagne
                </button>
                <a href="{{ route('admin.crm.campaigns.index') }}"
                   class="px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-xl text-sm font-medium transition-colors">
                    Annuler
                </a>
            </div>
        </form>
    </div>

    {{-- Aperçu dynamique --}}
    <div class="space-y-4">
        {{-- Compteur contacts éligibles --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 sticky top-4">
            <h4 class="font-semibold text-gray-800 text-sm mb-4 flex items-center gap-2">
                <i class="fas fa-users text-blue-500"></i> Contacts éligibles
            </h4>
            <div class="text-center py-4">
                <p id="eligible-count" class="text-5xl font-bold text-blue-600">—</p>
                <p class="text-sm text-gray-400 mt-1">contact(s) ciblé(s)</p>
            </div>
            <p id="eligible-status" class="text-xs text-center text-gray-400">Renseignez les critères pour voir le résultat.</p>

            <div class="mt-4 pt-4 border-t border-gray-50 space-y-2 text-xs text-gray-500">
                <p class="font-semibold text-gray-700">Rappels importants :</p>
                <p><i class="fas fa-clock text-amber-400 mr-1"></i> Fenêtre 24h Meta obligatoire</p>
                <p><i class="fas fa-shield-alt text-blue-400 mr-1"></i> Templates approuvés hors fenêtre</p>
                <p><i class="fas fa-robot text-purple-400 mr-1"></i> Personnalisation via variables {nom}, {produit}</p>
            </div>
        </div>

        {{-- Prévisualisation du message --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <h4 class="font-semibold text-gray-800 text-sm mb-3 flex items-center gap-2">
                <i class="fas fa-eye text-gray-400"></i> Aperçu du message
            </h4>
            <div class="bg-gray-50 rounded-xl p-4 text-sm text-gray-700 leading-relaxed min-h-[80px] whitespace-pre-wrap font-mono" id="message-preview">
                <span class="text-gray-300 italic">Votre message apparaîtra ici...</span>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Insertion de variable dans le textarea
function insertVar(variable) {
    const ta = document.getElementById('message_template');
    const start = ta.selectionStart;
    const end = ta.selectionEnd;
    ta.value = ta.value.substring(0, start) + variable + ta.value.substring(end);
    ta.selectionStart = ta.selectionEnd = start + variable.length;
    ta.focus();
    updatePreview();
}

// Aperçu du message
function updatePreview() {
    const text = document.getElementById('message_template').value;
    const preview = document.getElementById('message-preview');
    preview.textContent = text || '';
    if (!text) {
        preview.innerHTML = '<span class="text-gray-300 italic">Votre message apparaîtra ici...</span>';
    }
}

// AJAX : compteur de contacts éligibles
let debounceTimer;
function refreshEligibleCount() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(async () => {
        const form = document.getElementById('campaign-form');
        const data = new FormData(form);
        const params = new URLSearchParams();
        for (const [k, v] of data.entries()) {
            if (k.startsWith('criteria')) params.append(k, v);
        }
        document.getElementById('eligible-status').textContent = 'Calcul en cours...';
        try {
            const resp = await fetch('{{ route('admin.crm.campaigns.eligible-count') }}?' + params.toString());
            const json = await resp.json();
            document.getElementById('eligible-count').textContent = json.count;
            document.getElementById('eligible-status').textContent = json.count === 0
                ? 'Aucun contact ne correspond à ces critères.'
                : `${json.count} contact(s) recevront ce message.`;
        } catch (e) {
            document.getElementById('eligible-status').textContent = 'Erreur de calcul.';
        }
    }, 600);
}

// Événements
document.getElementById('message_template').addEventListener('input', updatePreview);
document.querySelectorAll('input[name^="criteria"]').forEach(el => el.addEventListener('change', refreshEligibleCount));
document.querySelectorAll('input[name="criteria_min_score"], input[name="criteria_inactivity_days"]')
    .forEach(el => el.addEventListener('input', refreshEligibleCount));

updatePreview();
refreshEligibleCount();
</script>
@endpush
@endsection
