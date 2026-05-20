@extends('layouts.admin')

@section('title', 'Tickets & Plaintes')
@section('page_title', 'Tickets & Plaintes')

@section('content')
    {{-- Filtres --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
        <form method="GET" action="{{ route('admin.tickets.index') }}" class="flex flex-wrap items-end gap-4">
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Statut</label>
                <select name="status" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Tous</option>
                    <option value="new" {{ request('status') === 'new' ? 'selected' : '' }}>Nouveau</option>
                    <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>En cours</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>En attente</option>
                    <option value="resolved" {{ request('status') === 'resolved' ? 'selected' : '' }}>Résolu</option>
                    <option value="closed" {{ request('status') === 'closed' ? 'selected' : '' }}>Fermé</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Priorité</label>
                <select name="priority" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Toutes</option>
                    <option value="low" {{ request('priority') === 'low' ? 'selected' : '' }}>Basse</option>
                    <option value="medium" {{ request('priority') === 'medium' ? 'selected' : '' }}>Moyenne</option>
                    <option value="high" {{ request('priority') === 'high' ? 'selected' : '' }}>Haute</option>
                    <option value="urgent" {{ request('priority') === 'urgent' ? 'selected' : '' }}>Urgente</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Agent</label>
                <select name="assigned_to" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Tous</option>
                    @foreach($agents as $agent)
                        <option value="{{ $agent->uuid }}" {{ request('assigned_to') == $agent->uuid ? 'selected' : '' }}>{{ $agent->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors">
                    <i class="fas fa-filter mr-1"></i> Filtrer
                </button>
                <a href="{{ route('admin.tickets.index') }}" class="bg-gray-100 text-gray-600 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-200 transition-colors">
                    Réinitialiser
                </a>
            </div>
        </form>
    </div>

    {{-- Messages flash --}}
    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6 flex items-center gap-2">
            <i class="fas fa-check-circle"></i>
            {{ session('success') }}
        </div>
    @endif

    {{-- Tableau des tickets --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-bold text-gray-800">
                <i class="fas fa-ticket-alt text-blue-600 mr-2"></i>
                Liste des tickets
                <span class="text-sm font-normal text-gray-400 ml-2">({{ $tickets->total() }} résultats)</span>
            </h3>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-gray-50 text-xs uppercase text-gray-500 font-semibold">
                        <th class="px-6 py-4">Référence</th>
                        <th class="px-6 py-4">Sujet</th>
                        <th class="px-6 py-4">Catégorie</th>
                        <th class="px-6 py-4">Statut</th>
                        <th class="px-6 py-4">Priorité</th>
                        <th class="px-6 py-4">Agent</th>
                        <th class="px-6 py-4">Date</th>
                        <th class="px-6 py-4">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($tickets as $ticket)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4">
                                <span class="text-sm font-bold text-blue-600">{{ $ticket->reference }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-sm text-gray-900 truncate max-w-xs">{{ $ticket->complaint->subject ?? '—' }}</p>
                            </td>
                            <td class="px-6 py-4">
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
                                    $cat = $ticket->complaint->category ?? 'other';
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $categoryColors[$cat] ?? $categoryColors['other'] }}">
                                    {{ $categoryLabels[$cat] ?? 'Autre' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
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
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$ticket->status] ?? 'bg-gray-100 text-gray-600' }}">
                                    {{ $statusLabels[$ticket->status] ?? $ticket->status }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                @php
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
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $priorityColors[$ticket->priority] ?? 'bg-gray-100 text-gray-600' }}">
                                    {{ $priorityLabels[$ticket->priority] ?? $ticket->priority }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                @if($ticket->assignedAgent)
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-full bg-blue-500 flex items-center justify-center text-white text-xs font-bold">
                                            {{ substr($ticket->assignedAgent->name, 0, 1) }}
                                        </div>
                                        <span class="text-sm text-gray-700">{{ $ticket->assignedAgent->name }}</span>
                                    </div>
                                @else
                                    <span class="text-sm text-gray-400 italic">Non assigné</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                {{ $ticket->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-6 py-4">
                                <a href="{{ route('admin.tickets.show', $ticket) }}"
                                    class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                    <i class="fas fa-eye mr-1"></i> Détails
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-gray-400">
                                <i class="fas fa-ticket-alt text-4xl mb-3 block"></i>
                                <p class="text-sm">Aucun ticket trouvé.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($tickets->hasPages())
            <div class="p-6 border-t border-gray-100">
                {{ $tickets->links() }}
            </div>
        @endif
    </div>
@endsection
