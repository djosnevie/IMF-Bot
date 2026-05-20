@extends('layouts.admin')

@section('title', 'Conversations')
@section('page_title', 'Gestion des Conversations')

@section('content')
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
            <h3 class="font-bold text-gray-800">Toutes les conversations</h3>
            <div class="flex gap-2">
                <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-xs font-bold">
                    {{ $conversations->total() }} au total
                </span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-gray-50 text-xs uppercase text-gray-500 font-semibold">
                        <th class="px-6 py-4">ID / Utilisateur</th>
                        <th class="px-6 py-4">Plateforme</th>
                        <th class="px-6 py-4">Messages</th>
                        <th class="px-6 py-4">Statut</th>
                        <th class="px-6 py-4">Dernière activité</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($conversations as $conv)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center text-blue-600 font-bold text-xs">
                                        {{ substr($conv->user_identifier, -4) }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-gray-900">{{ $conv->user_identifier }}</p>
                                        <p class="text-xs text-gray-400">ID: #{{ $conv->id }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center gap-1 text-xs font-medium text-gray-600">
                                    <i class="fab fa-whatsapp text-green-500"></i>
                                    {{ ucfirst($conv->platform) }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-600 font-medium">{{ $conv->messages_count }} messages</span>
                            </td>
                            <td class="px-6 py-4">
                                <span
                                    class="px-2 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider {{ $conv->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                    {{ $conv->status === 'active' ? 'Actif' : 'Inactif' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                {{ $conv->last_message_at ? $conv->last_message_at->diffForHumans() : 'Jamais' }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('admin.conversations.show', $conv->uuid) }}"
                                    class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                                    <i class="fas fa-eye text-blue-500"></i>
                                    Voir
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($conversations->hasPages())
            <div class="p-6 border-t border-gray-100 bg-gray-50/30">
                {{ $conversations->links() }}
            </div>
        @endif
    </div>
@endsection