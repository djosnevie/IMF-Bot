@extends('layouts.admin')

@section('title', 'Conversation — ' . ($conversation->contact?->display_name ?? $conversation->user_identifier))
@section('page_title', 'Détails de la Conversation')

@section('content')
<div class="mb-6 flex items-center justify-between flex-wrap gap-3">
    <a href="{{ route('admin.conversations') }}"
        class="text-sm text-gray-500 hover:text-gray-800 flex items-center gap-2 transition-colors">
        <i class="fas fa-arrow-left"></i>
        Retour à la liste
    </a>
    <div class="flex gap-2 flex-wrap">
        <span class="px-3 py-1 bg-gray-100 text-gray-600 rounded-full text-xs font-semibold tracking-wide">
            <i class="fas fa-hashtag mr-1"></i>{{ $conversation->contact?->display_name ?? $conversation->user_identifier }}
        </span>
        <span class="px-3 py-1 rounded-full text-xs font-semibold tracking-wide
            {{ $conversation->status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500' }}">
            <i class="fas fa-circle text-[8px] mr-1"></i>
            {{ ucfirst($conversation->status) }}
        </span>
        <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-xs font-semibold tracking-wide">
            <i class="fab fa-whatsapp mr-1"></i>{{ $conversation->platform }}
        </span>
        <span class="px-3 py-1 bg-slate-100 text-slate-600 rounded-full text-xs font-semibold">
            {{ $conversation->messages->count() }} message{{ $conversation->messages->count() > 1 ? 's' : '' }}
        </span>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- ========== FENÊTRE DE CHAT ========== --}}
    <div class="lg:col-span-2 flex flex-col rounded-2xl overflow-hidden shadow-sm border border-gray-200" style="height: 78vh;">

        {{-- Header style WhatsApp --}}
        <div class="flex items-center gap-3 px-5 py-3 border-b" style="background: #075e54;">
            <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center text-white font-bold text-sm flex-shrink-0">
                {{ strtoupper(substr($conversation->contact?->display_name ?? $conversation->user_identifier, -2)) }}
            </div>
            <div class="flex-1 min-w-0">
                @if($conversation->contact && $conversation->contact->display_name)
                    <p class="font-semibold text-white text-sm truncate">{{ $conversation->contact->display_name }}</p>
                    <p class="text-[11px] text-green-200 opacity-90">
                        {{ $conversation->user_identifier }} · Actif : {{ $conversation->last_message_at?->diffForHumans() ?? '—' }}
                    </p>
                @else
                    <p class="font-semibold text-white text-sm truncate">{{ $conversation->user_identifier }}</p>
                    <p class="text-[11px] text-green-200">
                        Dernière activité : {{ $conversation->last_message_at?->diffForHumans() ?? '—' }}
                    </p>
                @endif
            </div>
            <div class="flex items-center gap-3 text-white/60">
                <i class="fas fa-search text-sm"></i>
                <i class="fas fa-ellipsis-v text-sm"></i>
            </div>
        </div>

        {{-- Zone des messages --}}
        <div id="messages-area" class="flex-1 overflow-y-auto px-4 py-5 space-y-1" style="background: #ece5dd url('data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%22200%22 height=%22200%22 opacity=%220.04%22><rect width=%22200%22 height=%22200%22 fill=%22%23000%22/></svg>') repeat;">

            @php $prevDate = null; @endphp
            @foreach($conversation->messages as $message)

                @php $msgDate = $message->created_at->format('d/m/Y'); @endphp

                {{-- Séparateur de date --}}
                @if($msgDate !== $prevDate)
                    <div class="flex justify-center my-4">
                        <span class="bg-white/80 text-gray-500 text-[11px] font-medium px-3 py-1 rounded-full shadow-sm">
                            {{ $message->created_at->isToday() ? "Aujourd'hui" : ($message->created_at->isYesterday() ? 'Hier' : $message->created_at->format('d M Y')) }}
                        </span>
                    </div>
                    @php $prevDate = $msgDate; @endphp
                @endif

                @if($message->sender_type === 'user')
                    {{-- Message CLIENT (droite, bleu) --}}
                    <div class="flex justify-end mb-1">
                        <div class="max-w-[72%]">
                            <div class="px-3 py-2 rounded-2xl rounded-tr-none shadow-sm text-sm leading-relaxed"
                                 style="background: #dcf8c6; color: #111;">
                                {!! nl2br(e($message->content)) !!}
                            </div>
                            <div class="flex justify-end items-center gap-1 mt-0.5 pr-1">
                                <span class="text-[10px] text-gray-400">{{ $message->created_at->format('H:i') }}</span>
                                <i class="fas fa-check-double text-[10px] text-blue-400"></i>
                            </div>
                        </div>
                    </div>
                @else
                    {{-- Message BOT / AGENT (gauche, blanc) --}}
                    <div class="flex justify-start mb-1 gap-2">
                        <div class="w-7 h-7 rounded-full flex-shrink-0 mt-1 flex items-center justify-center text-white text-xs font-bold shadow"
                             style="background: #128c7e;">
                            <i class="fas fa-robot text-[10px]"></i>
                        </div>
                        <div class="max-w-[72%]">
                            <div class="px-3 py-2 rounded-2xl rounded-tl-none shadow-sm text-sm leading-relaxed text-gray-800 bg-white border border-gray-100 prose-sm prose max-w-none">
                                {!! \App\Helpers\MarkdownHelper::toHtml($message->content) !!}
                            </div>
                            <div class="flex items-center gap-2 mt-0.5 pl-1">
                                <span class="text-[10px] text-gray-400">{{ $message->created_at->format('H:i') }}</span>
                                @if($message->ai_response_metadata)
                                    <span class="text-[10px] text-purple-400">
                                        <i class="fas fa-microchip mr-0.5"></i>
                                        {{ $message->ai_response_metadata['model'] ?? 'IA' }}
                                        @if(isset($message->ai_response_metadata['tokens_used']))
                                            · {{ $message->ai_response_metadata['tokens_used'] }} tokens
                                        @endif
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

            @endforeach

            @if($conversation->messages->isEmpty())
                <div class="flex flex-col items-center justify-center h-full text-gray-400 pt-16">
                    <i class="fas fa-comment-slash text-4xl mb-3"></i>
                    <p class="text-sm">Aucun message dans cette conversation.</p>
                </div>
            @endif
        </div>

        {{-- Footer --}}
        <div class="px-4 py-3 border-t bg-gray-50 flex items-center gap-3">
            <div class="flex-1 bg-white rounded-full px-4 py-2 text-sm text-gray-400 border border-gray-200 cursor-not-allowed select-none">
                <i class="fas fa-lock text-xs mr-2 text-gray-300"></i>Sophie gère cette conversation automatiquement
            </div>
        </div>
    </div>

    {{-- ========== PANNEAU LATÉRAL ========== --}}
    <div class="space-y-4">

        {{-- Infos client --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-50 bg-gradient-to-r from-slate-800 to-slate-700">
                <h4 class="font-semibold text-white text-sm flex items-center gap-2">
                    <i class="fas fa-user-circle"></i> Profil Client
                </h4>
            </div>
            <div class="p-5 space-y-4">
                <div>
                    <p class="text-[10px] uppercase font-bold text-gray-400 tracking-widest mb-1">Identifiant WhatsApp</p>
                    <p class="text-sm font-semibold text-gray-800 flex items-center gap-2">
                        <i class="fab fa-whatsapp text-green-500"></i>
                        {{ $conversation->user_identifier }}
                    </p>
                </div>
                <div>
                    <p class="text-[10px] uppercase font-bold text-gray-400 tracking-widest mb-1">Plateforme</p>
                    <p class="text-sm font-semibold text-gray-800 capitalize">{{ $conversation->platform }}</p>
                </div>
                <div>
                    <p class="text-[10px] uppercase font-bold text-gray-400 tracking-widest mb-1">Première interaction</p>
                    <p class="text-sm font-semibold text-gray-800">{{ $conversation->created_at->format('d/m/Y à H:i') }}</p>
                </div>
                <div>
                    <p class="text-[10px] uppercase font-bold text-gray-400 tracking-widest mb-1">Dernière activité</p>
                    <p class="text-sm font-semibold text-gray-800">{{ $conversation->last_message_at?->format('d/m/Y à H:i') ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-[10px] uppercase font-bold text-gray-400 tracking-widest mb-1">Statut</p>
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold
                        {{ $conversation->status === 'active' ? 'bg-emerald-100 text-emerald-700' : ($conversation->status === 'closed' ? 'bg-red-100 text-red-600' : 'bg-gray-100 text-gray-500') }}">
                        <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                        {{ strtoupper($conversation->status) }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Statistiques --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <h4 class="font-semibold text-gray-800 text-sm mb-4 flex items-center gap-2">
                <i class="fas fa-chart-bar text-blue-500"></i> Statistiques
            </h4>
            <div class="grid grid-cols-2 gap-3">
                <div class="bg-blue-50 rounded-xl p-3 text-center">
                    <p class="text-2xl font-bold text-blue-600">{{ $conversation->messages->count() }}</p>
                    <p class="text-[10px] text-blue-500 font-medium mt-0.5">Messages total</p>
                </div>
                <div class="bg-green-50 rounded-xl p-3 text-center">
                    <p class="text-2xl font-bold text-green-600">{{ $conversation->messages->where('sender_type', 'user')->count() }}</p>
                    <p class="text-[10px] text-green-500 font-medium mt-0.5">Du client</p>
                </div>
                <div class="bg-purple-50 rounded-xl p-3 text-center">
                    <p class="text-2xl font-bold text-purple-600">{{ $conversation->messages->where('sender_type', 'bot')->count() }}</p>
                    <p class="text-[10px] text-purple-500 font-medium mt-0.5">Du bot IA</p>
                </div>
                <div class="bg-orange-50 rounded-xl p-3 text-center">
                    @php
                        $tokens = $conversation->messages->sum(fn($m) => $m->ai_response_metadata['tokens_used'] ?? 0);
                    @endphp
                    <p class="text-2xl font-bold text-orange-600">{{ number_format($tokens) }}</p>
                    <p class="text-[10px] text-orange-500 font-medium mt-0.5">Tokens utilisés</p>
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <h4 class="font-semibold text-gray-800 text-sm mb-4 flex items-center gap-2">
                <i class="fas fa-bolt text-yellow-500"></i> Actions
            </h4>
            <div class="space-y-2">
                <button class="w-full py-2.5 px-4 bg-gray-50 hover:bg-gray-100 text-gray-700 rounded-xl text-sm font-medium transition-colors flex items-center justify-center gap-2 border border-gray-200">
                    <i class="fas fa-archive text-gray-400"></i> Archiver la conversation
                </button>
                <button class="w-full py-2.5 px-4 bg-red-50 hover:bg-red-100 text-red-600 rounded-xl text-sm font-medium transition-colors flex items-center justify-center gap-2 border border-red-100">
                    <i class="fas fa-ban"></i> Bloquer l'utilisateur
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Auto-scroll vers le bas --}}
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const area = document.getElementById('messages-area');
        if (area) area.scrollTop = area.scrollHeight;
    });
</script>
@endsection