@extends('layouts.admin')
@section('title', 'CRM — Contacts')
@section('page_title', 'Contacts CRM')

@section('content')
<div class="flex items-center justify-between mb-6 flex-wrap gap-3">
    <div>
        <h2 class="text-xl font-bold text-gray-800">Base Contacts</h2>
        <p class="text-sm text-gray-500">{{ $contacts->total() }} contact(s) trouvé(s)</p>
    </div>
    @can('crm.contacts.view')
    <a href="{{ route('admin.crm.contacts.export', request()->query()) }}"
       class="flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-sm font-medium transition-colors">
        <i class="fas fa-download"></i> Exporter CSV
    </a>
    @endcan
</div>

{{-- Filtres --}}
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 mb-6">
    <form method="GET" class="flex flex-wrap gap-3 items-end">
        <div>
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Statut</label>
            <select name="status" class="border border-gray-200 rounded-lg px-3 py-2 text-sm">
                <option value="">Tous</option>
                @foreach(['lead'=>'Lead','prospect'=>'Prospect','en_cours'=>'En cours','client'=>'Client','inactif'=>'Inactif'] as $val=>$label)
                    <option value="{{ $val }}" {{ request('status')===$val?'selected':'' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Tag</label>
            <select name="tag" class="border border-gray-200 rounded-lg px-3 py-2 text-sm">
                <option value="">Tous</option>
                @foreach($popularTags as $tag)
                    <option value="{{ $tag }}" {{ request('tag')===$tag?'selected':'' }}>{{ $tag }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Score min</label>
            <input type="number" name="min_score" min="0" max="100" value="{{ request('min_score') }}"
                   class="border border-gray-200 rounded-lg px-3 py-2 text-sm w-24">
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Score max</label>
            <input type="number" name="max_score" min="0" max="100" value="{{ request('max_score') }}"
                   class="border border-gray-200 rounded-lg px-3 py-2 text-sm w-24">
        </div>
        @if(auth()->user()->hasAnyRole(['super-admin','admin','supervisor']))
        <div>
            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Agent</label>
            <select name="agent_id" class="border border-gray-200 rounded-lg px-3 py-2 text-sm">
                <option value="">Tous</option>
                @foreach($agents as $agent)
                    <option value="{{ $agent->id }}" {{ request('agent_id')==$agent->id?'selected':'' }}>{{ $agent->name }}</option>
                @endforeach
            </select>
        </div>
        @endif
        <div class="flex gap-2">
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors">
                <i class="fas fa-filter mr-1"></i> Filtrer
            </button>
            <a href="{{ route('admin.crm.contacts.index') }}" class="px-4 py-2 bg-gray-100 text-gray-600 rounded-lg text-sm font-medium hover:bg-gray-200 transition-colors">
                Réinitialiser
            </a>
        </div>
    </form>
</div>

{{-- Pipeline summary --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
    @foreach($stages as $stage)
    @php $count = \App\Models\Crm\Contact::where('commercial_status', strtolower(str_replace(' ','_',$stage->label)))->count(); @endphp
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 flex items-center gap-3">
        <div class="w-3 h-3 rounded-full flex-shrink-0" style="background: {{ $stage->color }}"></div>
        <div>
            <p class="text-sm font-semibold text-gray-700">{{ $stage->label }}</p>
            <p class="text-xl font-bold" style="color: {{ $stage->color }}">{{ $count }}</p>
        </div>
    </div>
    @endforeach
</div>

{{-- Tableau --}}
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    @if(session('success'))
        <div class="bg-emerald-50 border-b border-emerald-200 text-emerald-700 px-5 py-3 text-sm flex items-center gap-2">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
        </div>
    @endif
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead class="bg-gray-50 text-xs uppercase text-gray-500 font-semibold">
                <tr>
                    <th class="px-5 py-3">Contact</th>
                    <th class="px-5 py-3">Statut</th>
                    <th class="px-5 py-3">Score</th>
                    <th class="px-5 py-3">Dernier contact</th>
                    <th class="px-5 py-3">Tags</th>
                    <th class="px-5 py-3">Agent</th>
                    <th class="px-5 py-3">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($contacts as $contact)
                @php
                    $statusColors = ['lead'=>'bg-gray-100 text-gray-600','prospect'=>'bg-blue-100 text-blue-700','en_cours'=>'bg-amber-100 text-amber-700','client'=>'bg-emerald-100 text-emerald-700','inactif'=>'bg-red-100 text-red-600'];
                    $statusLabels = ['lead'=>'Lead','prospect'=>'Prospect','en_cours'=>'En cours','client'=>'Client','inactif'=>'Inactif'];
                @endphp
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-5 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                                {{ strtoupper(substr($contact->whatsapp_number, -2)) }}
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <p class="font-semibold text-gray-800 text-sm">{{ $contact->display_name ?? $contact->whatsapp_number }}</p>
                                    @if(!$contact->display_name)
                                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-amber-100 text-amber-800 flex items-center gap-1">
                                            <i class="fas fa-user-plus text-[9px]"></i> Nouveau
                                        </span>
                                    @endif
                                </div>
                                @if($contact->display_name)
                                    <p class="text-xs text-gray-400">{{ $contact->whatsapp_number }}</p>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="px-5 py-4">
                        <span class="px-2.5 py-1 rounded-full text-xs font-semibold {{ $statusColors[$contact->commercial_status] ?? 'bg-gray-100 text-gray-600' }}">
                            {{ $statusLabels[$contact->commercial_status] ?? $contact->commercial_status }}
                        </span>
                    </td>
                    <td class="px-5 py-4">
                        <div class="flex items-center gap-2 min-w-[90px]">
                            <div class="flex-1 bg-gray-100 rounded-full h-1.5">
                                <div class="h-1.5 rounded-full transition-all"
                                     style="width: {{ $contact->interest_score }}%; background: {{ $contact->interest_score >= 75 ? '#10B981' : ($contact->interest_score >= 40 ? '#F59E0B' : '#EF4444') }}">
                                </div>
                            </div>
                            <span class="text-xs font-bold text-gray-600 w-8 text-right">{{ $contact->interest_score }}</span>
                        </div>
                    </td>
                    <td class="px-5 py-4 text-sm text-gray-500">
                        {{ $contact->last_contact_at?->diffForHumans() ?? '—' }}
                    </td>
                    <td class="px-5 py-4">
                        <div class="flex flex-wrap gap-1">
                            @foreach($contact->tags->take(3) as $tag)
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-medium {{ $tag->source === 'auto' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }}">
                                    {{ $tag->name }}
                                </span>
                            @endforeach
                            @if($contact->tags->count() > 3)
                                <span class="text-xs text-gray-400">+{{ $contact->tags->count() - 3 }}</span>
                            @endif
                        </div>
                    </td>
                    <td class="px-5 py-4 text-sm text-gray-600">
                        {{ $contact->agent?->name ?? '—' }}
                    </td>
                    <td class="px-5 py-4">
                        <div class="flex items-center gap-3">
                            <a href="{{ route('admin.crm.contacts.show', $contact->uuid) }}"
                               class="text-indigo-600 hover:text-indigo-800 text-sm font-semibold flex items-center gap-1">
                                <i class="fas fa-eye"></i> Voir
                            </a>
                            @if(!$contact->display_name)
                                <button type="button" onclick="openQuickRegisterModal('{{ $contact->uuid }}', '{{ $contact->whatsapp_number }}')"
                                        class="text-emerald-600 hover:text-emerald-800 text-xs font-semibold flex items-center gap-1 border border-emerald-200 hover:border-emerald-300 bg-emerald-50 px-2 py-1 rounded-lg transition-all">
                                    <i class="fas fa-user-plus"></i> Enregistrer
                                </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-5 py-12 text-center text-gray-400">
                        <i class="fas fa-users text-4xl mb-3 block"></i>
                        <p class="text-sm">Aucun contact trouvé.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($contacts->hasPages())
        <div class="p-5 border-t border-gray-100">{{ $contacts->links() }}</div>
    @endif
</div>

{{-- Modal Enregistrement Rapide --}}
<div id="quick-register-modal" class="fixed inset-0 z-50 overflow-y-auto hidden">
    {{-- Backdrop --}}
    <div class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity"></div>

    <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
        <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-md border border-gray-100">
            <div class="absolute right-4 top-4">
                <button type="button" onclick="closeQuickRegisterModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="quick-register-form" action="" method="POST">
                @csrf
                <div class="bg-white px-6 pb-6 pt-8">
                    <div class="mb-4 text-center">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 mb-3">
                            <i class="fas fa-user-plus text-lg"></i>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900">Enregistrer ce numéro</h3>
                        <p class="text-xs text-gray-500 mt-1">Donnez un nom complet à ce contact pour l'identifier dans le CRM.</p>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Numéro WhatsApp</label>
                            <input type="text" id="quick-whatsapp-display" class="w-full bg-gray-50 border border-gray-100 rounded-xl px-3 py-2.5 text-sm text-gray-500 outline-none cursor-not-allowed" readonly>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Nom complet / Affichage <span class="text-red-500">*</span></label>
                            <input type="text" name="display_name" required autofocus
                                   class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-emerald-500 outline-none animate-pulse-once"
                                   placeholder="Ex: Jean Dupont">
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-50 px-6 py-4 flex gap-3 justify-end rounded-b-2xl">
                    <button type="button" onclick="closeQuickRegisterModal()"
                            class="px-4 py-2 bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 rounded-xl text-sm font-medium transition-colors">
                        Annuler
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-sm font-semibold transition-colors">
                        Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function openQuickRegisterModal(uuid, whatsappNumber) {
        const modal = document.getElementById('quick-register-modal');
        const form = document.getElementById('quick-register-form');
        const inputDisplay = document.getElementById('quick-whatsapp-display');
        
        if (modal && form && inputDisplay) {
            // Dynamically set action URL
            form.action = `/admin/crm/contacts/${uuid}/update`;
            inputDisplay.value = whatsappNumber;
            
            modal.classList.remove('hidden');
        }
    }
    
    function closeQuickRegisterModal() {
        const modal = document.getElementById('quick-register-modal');
        if (modal) {
            modal.classList.add('hidden');
        }
    }
</script>
@endpush
