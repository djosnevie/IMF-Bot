@extends('layouts.admin')

@section('title', 'Ticket ' . $ticket->reference)
@section('page_title', 'Détail du Ticket')

@section('content')
    {{-- Messages flash --}}
    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6 flex items-center gap-2">
            <i class="fas fa-check-circle"></i>
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-center gap-2">
            <i class="fas fa-exclamation-circle"></i>
            {{ session('error') }}
        </div>
    @endif

    {{-- Bouton retour --}}
    <div class="mb-6">
        <a href="{{ route('admin.tickets.index') }}" class="text-sm text-gray-500 hover:text-gray-700 flex items-center gap-1">
            <i class="fas fa-arrow-left"></i> Retour à la liste
        </a>
    </div>

    {{-- En-tête du ticket --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <h2 class="text-xl font-bold text-gray-900 flex items-center gap-3">
                    <i class="fas fa-ticket-alt text-blue-600"></i>
                    {{ $ticket->reference }}
                </h2>
                <p class="text-sm text-gray-500 mt-1">
                    Créé le {{ $ticket->created_at->format('d/m/Y à H:i') }}
                    @if($ticket->resolved_at)
                        — Résolu le {{ $ticket->resolved_at->format('d/m/Y à H:i') }}
                    @endif
                </p>
            </div>
            <div class="flex items-center gap-3">
                @php
                    $statusColors = [
                        'new' => 'bg-blue-100 text-blue-700',
                        'in_progress' => 'bg-yellow-100 text-yellow-700',
                        'pending' => 'bg-orange-100 text-orange-700',
                        'resolved' => 'bg-green-100 text-green-700',
                        'closed' => 'bg-gray-100 text-gray-600',
                    ];
                    $statusLabels = [
                        'new' => 'Nouveau',
                        'in_progress' => 'En cours',
                        'pending' => 'En attente',
                        'resolved' => 'Résolu',
                        'closed' => 'Fermé',
                    ];
                    $priorityColors = [
                        'low' => 'bg-gray-100 text-gray-600',
                        'medium' => 'bg-blue-100 text-blue-700',
                        'high' => 'bg-orange-100 text-orange-700',
                        'urgent' => 'bg-red-100 text-red-700',
                    ];
                    $priorityLabels = [
                        'low' => 'Basse',
                        'medium' => 'Moyenne',
                        'high' => 'Haute',
                        'urgent' => 'Urgente',
                    ];
                @endphp
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $statusColors[$ticket->status] ?? 'bg-gray-100' }}">
                    {{ $statusLabels[$ticket->status] ?? $ticket->status }}
                </span>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $priorityColors[$ticket->priority] ?? 'bg-gray-100' }}">
                    Priorité : {{ $priorityLabels[$ticket->priority] ?? $ticket->priority }}
                </span>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- ============================================ --}}
        {{-- COLONNE GAUCHE : Infos + Historique WhatsApp --}}
        {{-- ============================================ --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Informations de la plainte --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-4 border-b border-gray-100 bg-gray-50">
                    <h3 class="font-bold text-gray-800 text-sm uppercase tracking-wider">
                        <i class="fas fa-file-alt text-blue-600 mr-2"></i> Détails de la plainte
                    </h3>
                </div>
                <div class="p-6 space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <span class="text-xs font-semibold text-gray-400 uppercase">Sujet</span>
                            <p class="text-sm font-medium text-gray-900 mt-1">{{ $ticket->complaint->subject }}</p>
                        </div>
                        <div>
                            <span class="text-xs font-semibold text-gray-400 uppercase">Catégorie</span>
                            @php
                                $categoryColors = [
                                    'credit' => 'bg-purple-100 text-purple-700',
                                    'account' => 'bg-blue-100 text-blue-700',
                                    'service' => 'bg-orange-100 text-orange-700',
                                    'other' => 'bg-gray-100 text-gray-700',
                                ];
                                $categoryLabels = [
                                    'credit' => 'Crédit',
                                    'account' => 'Compte',
                                    'service' => 'Service',
                                    'other' => 'Autre',
                                ];
                                $cat = $ticket->complaint->category;
                            @endphp
                            <p class="mt-1">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $categoryColors[$cat] ?? $categoryColors['other'] }}">
                                    {{ $categoryLabels[$cat] ?? 'Autre' }}
                                </span>
                            </p>
                        </div>
                        <div>
                            <span class="text-xs font-semibold text-gray-400 uppercase">Numéro WhatsApp</span>
                            <p class="text-sm text-gray-700 mt-1">
                                <i class="fab fa-whatsapp text-green-500 mr-1"></i>
                                {{ $ticket->complaint->whatsapp_number }}
                            </p>
                        </div>
                        <div>
                            <span class="text-xs font-semibold text-gray-400 uppercase">Date de soumission</span>
                            <p class="text-sm text-gray-700 mt-1">{{ $ticket->complaint->created_at->format('d/m/Y à H:i') }}</p>
                        </div>
                    </div>
                    <div>
                        <span class="text-xs font-semibold text-gray-400 uppercase">Description</span>
                        <div class="mt-1 p-4 bg-gray-50 rounded-lg text-sm text-gray-700 leading-relaxed">
                            {{ $ticket->complaint->description }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Historique de la conversation WhatsApp --}}
            @if($ticket->complaint->conversation)
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-4 border-b border-gray-100 bg-gray-50">
                        <h3 class="font-bold text-gray-800 text-sm uppercase tracking-wider">
                            <i class="fab fa-whatsapp text-green-500 mr-2"></i> Historique de la conversation
                        </h3>
                    </div>
                    <div class="p-6 max-h-96 overflow-y-auto space-y-3" style="background: #e5ddd5;">
                        @foreach($ticket->complaint->conversation->messages as $message)
                            <div class="flex {{ $message->sender_type === 'user' ? 'justify-start' : 'justify-end' }}">
                                <div class="max-w-xs lg:max-w-md px-4 py-2 rounded-xl text-sm shadow-sm
                                    {{ $message->sender_type === 'user'
                                        ? 'bg-white text-gray-800 rounded-tl-none'
                                        : 'bg-green-100 text-gray-800 rounded-tr-none' }}">
                                    <p class="leading-relaxed whitespace-pre-line">{{ $message->content }}</p>
                                    <p class="text-xs text-gray-400 mt-1 text-right">
                                        {{ $message->created_at->format('H:i') }}
                                        @if($message->sender_type === 'bot')
                                            <i class="fas fa-check-double text-blue-400 ml-1"></i>
                                        @endif
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        {{-- ============================================ --}}
        {{-- COLONNE DROITE : Actions + Commentaires      --}}
        {{-- ============================================ --}}
        <div class="space-y-6">

            @can('tickets.assign')
            {{-- Assignation --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-4 border-b border-gray-100 bg-gray-50">
                    <h3 class="font-bold text-gray-800 text-sm uppercase tracking-wider">
                        <i class="fas fa-user-check text-blue-600 mr-2"></i> Assignation
                    </h3>
                </div>
                <div class="p-4">
                    <form action="{{ route('admin.tickets.assign', $ticket) }}" method="POST" class="space-y-3">
                        @csrf
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">Agent assigné</label>
                            <select name="assigned_to" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">— Sélectionner un agent —</option>
                                @foreach($agents as $agent)
                                    <option value="{{ $agent->uuid }}" {{ $ticket->assigned_to == $agent->id ? 'selected' : '' }}>
                                        {{ $agent->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors">
                            <i class="fas fa-user-plus mr-1"></i> Assigner
                        </button>
                    </form>
                </div>
            </div>
            @endcan

            @can('tickets.assign')
            {{-- Changement de statut --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-4 border-b border-gray-100 bg-gray-50">
                    <h3 class="font-bold text-gray-800 text-sm uppercase tracking-wider">
                        <i class="fas fa-exchange-alt text-blue-600 mr-2"></i> Changer le statut
                    </h3>
                </div>
                <div class="p-4">
                    <form action="{{ route('admin.tickets.updateStatus', $ticket) }}" method="POST" class="space-y-3">
                        @csrf
                        @method('PATCH')
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">Nouveau statut</label>
                            <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="new" {{ $ticket->status === 'new' ? 'selected' : '' }}>Nouveau</option>
                                <option value="in_progress" {{ $ticket->status === 'in_progress' ? 'selected' : '' }}>En cours</option>
                                <option value="pending" {{ $ticket->status === 'pending' ? 'selected' : '' }}>En attente</option>
                                <option value="resolved" {{ $ticket->status === 'resolved' ? 'selected' : '' }}>Résolu</option>
                                <option value="closed" {{ $ticket->status === 'closed' ? 'selected' : '' }}>Fermé</option>
                            </select>
                        </div>
                        <button type="submit" class="w-full bg-slate-700 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-slate-800 transition-colors">
                            <i class="fas fa-save mr-1"></i> Mettre à jour
                        </button>
                    </form>
                </div>
            </div>
            @endcan

            {{-- Commentaires --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-4 border-b border-gray-100 bg-gray-50">
                    <h3 class="font-bold text-gray-800 text-sm uppercase tracking-wider">
                        <i class="fas fa-comments text-blue-600 mr-2"></i> Commentaires
                        <span class="text-gray-400 font-normal">({{ $ticket->comments->count() }})</span>
                    </h3>
                </div>

                {{-- Fil de commentaires --}}
                <div class="p-4 max-h-64 overflow-y-auto space-y-3">
                    @forelse($ticket->comments as $comment)
                        <div class="p-3 rounded-lg {{ $comment->is_internal ? 'bg-yellow-50 border border-yellow-200' : 'bg-gray-50 border border-gray-200' }}">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-xs font-bold text-gray-700">
                                    {{ $comment->author->name ?? 'Inconnu' }}
                                </span>
                                <div class="flex items-center gap-2">
                                    @if($comment->is_internal)
                                        <span class="text-xs bg-yellow-200 text-yellow-800 px-1.5 py-0.5 rounded font-medium">
                                            <i class="fas fa-lock text-xxs mr-0.5"></i> Interne
                                        </span>
                                    @else
                                        <span class="text-xs bg-green-200 text-green-800 px-1.5 py-0.5 rounded font-medium">
                                            <i class="fab fa-whatsapp text-xxs mr-0.5"></i> Envoyé
                                        </span>
                                    @endif
                                    <span class="text-xs text-gray-400">{{ $comment->created_at->format('d/m H:i') }}</span>
                                </div>
                            </div>
                            <p class="text-sm text-gray-700 leading-relaxed">{{ $comment->content }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400 text-center py-4">Aucun commentaire pour l'instant.</p>
                    @endforelse
                </div>

                @canany(['tickets.comment_internal', 'tickets.comment_public'])
                {{-- Formulaire d'ajout --}}
                <div class="p-4 border-t border-gray-100">
                    <form action="{{ route('admin.tickets.comment', $ticket) }}" method="POST" class="space-y-3">
                        @csrf
                        <div>
                            <textarea name="content" rows="3" placeholder="Écrire un commentaire..."
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none"
                                required maxlength="2000"></textarea>
                        </div>
                        @can('tickets.comment_public')
                        <div class="flex items-center justify-between">
                            <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
                                <input type="checkbox" name="is_internal" value="1"
                                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span>Note interne uniquement</span>
                            </label>
                            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors">
                                <i class="fas fa-paper-plane mr-1"></i> Envoyer
                            </button>
                        </div>
                        <p class="text-xs text-gray-400 mt-2">
                            <i class="fas fa-info-circle mr-1"></i>
                            Sans cocher "Note interne", le commentaire sera envoyé au client via WhatsApp.
                        </p>
                        @else
                        <div class="flex items-center justify-between">
                            <label class="flex items-center gap-2 text-sm text-gray-600 cursor-not-allowed">
                                <input type="hidden" name="is_internal" value="1">
                                <input type="checkbox" checked disabled
                                    class="rounded border-gray-300 text-gray-400">
                                <span>Note interne (permission publique manquante)</span>
                            </label>
                            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors">
                                <i class="fas fa-paper-plane mr-1"></i> Ajouter
                            </button>
                        </div>
                        @endcan
                    </form>
                </div>
                @endcanany
            </div>
        </div>
    </div>
@endsection
