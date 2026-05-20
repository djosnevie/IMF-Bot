@extends('layouts.admin')
@section('title', 'CRM — Alertes')
@section('page_title', 'Mes Alertes')

@section('content')
<div class="flex items-center justify-between mb-6 flex-wrap gap-3">
    <p class="text-sm text-gray-500">{{ $alerts->total() }} alerte(s)</p>
    @if($alerts->total() > 0)
    <form action="{{ route('admin.crm.alerts.read-all') }}" method="POST">
        @csrf
        <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-xl text-sm font-medium transition-colors">
            <i class="fas fa-check-double mr-1"></i> Tout marquer comme lu
        </button>
    </form>
    @endif
</div>

@if(session('success'))
    <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl mb-5 text-sm">
        <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
    </div>
@endif

<div class="space-y-3">
    @forelse($alerts as $alert)
    @php
        $typeConfig = [
            'prospect_chaud'   => ['icon'=>'fa-fire','color'=>'text-red-500','bg'=>'bg-red-50 border-red-100','label'=>'Prospect chaud'],
            'client_inactif'   => ['icon'=>'fa-moon','color'=>'text-gray-500','bg'=>'bg-gray-50 border-gray-100','label'=>'Client inactif'],
            'ticket_en_attente'=> ['icon'=>'fa-ticket-alt','color'=>'text-amber-500','bg'=>'bg-amber-50 border-amber-100','label'=>'Ticket en attente'],
            'score_eleve'      => ['icon'=>'fa-star','color'=>'text-indigo-500','bg'=>'bg-indigo-50 border-indigo-100','label'=>'Score élevé'],
        ];
        $cfg = $typeConfig[$alert->type] ?? ['icon'=>'fa-bell','color'=>'text-blue-500','bg'=>'bg-blue-50 border-blue-100','label'=>$alert->type];
    @endphp
    <div class="flex items-start gap-4 p-4 rounded-2xl border {{ $cfg['bg'] }} {{ $alert->isRead() ? 'opacity-60' : '' }} transition-opacity">
        <div class="w-10 h-10 rounded-full bg-white flex items-center justify-center shadow-sm flex-shrink-0">
            <i class="fas {{ $cfg['icon'] }} {{ $cfg['color'] }}"></i>
        </div>
        <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 mb-1">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-wide">{{ $cfg['label'] }}</span>
                @if(!$alert->isRead())
                    <span class="w-2 h-2 bg-red-500 rounded-full"></span>
                @endif
            </div>
            <p class="text-sm text-gray-700">{{ $alert->message }}</p>
            <div class="flex items-center gap-3 mt-2">
                <span class="text-xs text-gray-400">{{ $alert->created_at->diffForHumans() }}</span>
                @if($alert->contact)
                    <a href="{{ route('admin.crm.contacts.show', $alert->contact->uuid) }}"
                       class="text-xs text-blue-600 hover:underline">
                        <i class="fas fa-eye mr-1"></i> Voir le contact
                    </a>
                @endif
            </div>
        </div>
        @if(!$alert->isRead())
        <form action="{{ route('admin.crm.alerts.read', $alert->id) }}" method="POST">
            @csrf
            <button class="px-3 py-1.5 bg-white border border-gray-200 hover:border-gray-300 text-gray-500 rounded-lg text-xs font-medium transition-colors flex-shrink-0">
                Lu
            </button>
        </form>
        @endif
    </div>
    @empty
    <div class="text-center py-16 text-gray-400">
        <i class="fas fa-bell-slash text-5xl mb-4 block"></i>
        <p class="text-sm font-medium">Aucune alerte pour l'instant.</p>
        <p class="text-xs text-gray-400 mt-1">Les nouvelles alertes apparaîtront ici.</p>
    </div>
    @endforelse
</div>

@if($alerts->hasPages())
    <div class="mt-6">{{ $alerts->links() }}</div>
@endif
@endsection
