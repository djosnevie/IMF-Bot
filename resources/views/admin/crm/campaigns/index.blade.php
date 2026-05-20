@extends('layouts.admin')
@section('title', 'CRM — Campagnes')
@section('page_title', 'Campagnes WhatsApp')

@section('content')
<div class="flex items-center justify-between mb-6 flex-wrap gap-3">
    <p class="text-sm text-gray-500">{{ $campaigns->total() }} campagne(s)</p>
    @can('crm.campaigns.manage')
    <a href="{{ route('admin.crm.campaigns.create') }}"
       class="flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-medium transition-colors">
        <i class="fas fa-plus"></i> Nouvelle campagne
    </a>
    @endcan
</div>

@if(session('success'))
    <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl mb-5 text-sm flex items-center gap-2">
        <i class="fas fa-check-circle"></i> {{ session('success') }}
    </div>
@endif
@if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-5 text-sm flex items-center gap-2">
        <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
    </div>
@endif

<div class="space-y-4">
    @forelse($campaigns as $campaign)
    @php
        $statusConfig = [
            'draft'     => ['label'=>'Brouillon',  'bg'=>'bg-gray-100 text-gray-600'],
            'scheduled' => ['label'=>'Planifiée',   'bg'=>'bg-blue-100 text-blue-700'],
            'sent'      => ['label'=>'Envoyée',     'bg'=>'bg-emerald-100 text-emerald-700'],
            'cancelled' => ['label'=>'Annulée',     'bg'=>'bg-red-100 text-red-600'],
        ];
        $cfg = $statusConfig[$campaign->status] ?? ['label'=>$campaign->status,'bg'=>'bg-gray-100 text-gray-600'];
    @endphp
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <div class="flex items-start justify-between gap-4 flex-wrap">
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-3 mb-2 flex-wrap">
                    <h3 class="font-bold text-gray-900 text-base">{{ $campaign->name }}</h3>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $cfg['bg'] }}">{{ $cfg['label'] }}</span>
                </div>
                <p class="text-sm text-gray-500 line-clamp-2 mb-3">{{ Str::limit($campaign->message_template, 150) }}</p>
                <div class="flex flex-wrap gap-4 text-xs text-gray-500">
                    @if($campaign->scheduled_at)
                    <span><i class="fas fa-clock mr-1 text-blue-400"></i> Planifié : {{ $campaign->scheduled_at->format('d/m/Y H:i') }}</span>
                    @endif
                    <span><i class="fas fa-paper-plane mr-1 text-emerald-400"></i> {{ $campaign->recipients_count }} destinataires</span>
                    <span><i class="fas fa-reply mr-1 text-purple-400"></i> {{ number_format($campaign->response_rate, 1) }}% de réponses</span>
                    <span><i class="fas fa-user mr-1 text-gray-400"></i> {{ $campaign->creator?->name ?? 'Système' }}</span>
                </div>
            </div>
            @if(in_array($campaign->status, ['draft','scheduled']))
            <form action="{{ route('admin.crm.campaigns.cancel', $campaign->id) }}" method="POST"
                  onsubmit="return confirm('Annuler cette campagne ?');">
                @csrf
                <button class="px-3 py-2 bg-red-50 hover:bg-red-100 text-red-600 rounded-xl text-xs font-medium transition-colors border border-red-100">
                    <i class="fas fa-ban mr-1"></i> Annuler
                </button>
            </form>
            @endif
        </div>
        @if($campaign->status === 'sent' && $campaign->logs_count > 0)
        <div class="mt-4 pt-4 border-t border-gray-50">
            <div class="flex items-center gap-3">
                <span class="text-xs text-gray-500 font-medium">Taux de réponse</span>
                <div class="flex-1 bg-gray-100 rounded-full h-1.5">
                    <div class="h-1.5 rounded-full bg-emerald-500 transition-all"
                         style="width: {{ min($campaign->response_rate, 100) }}%"></div>
                </div>
                <span class="text-xs font-bold text-emerald-600">{{ number_format($campaign->response_rate, 1) }}%</span>
            </div>
        </div>
        @endif
    </div>
    @empty
    <div class="text-center py-20 text-gray-400 bg-white rounded-2xl border border-gray-100">
        <i class="fas fa-paper-plane text-5xl mb-4 block opacity-40"></i>
        <p class="text-sm font-medium">Aucune campagne créée.</p>
        @can('crm.campaigns.manage')
        <a href="{{ route('admin.crm.campaigns.create') }}" class="inline-block mt-3 px-4 py-2 bg-blue-600 text-white rounded-xl text-sm font-medium hover:bg-blue-700 transition-colors">
            Créer ma première campagne
        </a>
        @endcan
    </div>
    @endforelse
</div>

@if($campaigns->hasPages())
    <div class="mt-6">{{ $campaigns->links() }}</div>
@endif
@endsection
