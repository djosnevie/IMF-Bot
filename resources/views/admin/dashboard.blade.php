@extends('layouts.admin')

@section('title', 'Vue d\'ensemble')
@section('page_title', 'Tableau de Bord')

@section('content')
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Stat Card 1 -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-blue-50 rounded-lg flex items-center justify-center text-blue-600">
                    <i class="fas fa-comments text-xl"></i>
                </div>
                <span class="text-xs font-medium text-green-600 bg-green-50 px-2 py-1 rounded-full">+12%</span>
            </div>
            <h3 class="text-gray-500 text-sm font-medium">Conversations Totales</h3>
            <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['total_conversations']) }}</p>
        </div>

        <!-- Stat Card 2 -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-purple-50 rounded-lg flex items-center justify-center text-purple-600">
                    <i class="fas fa-paper-plane text-xl"></i>
                </div>
                <span class="text-xs font-medium text-blue-600 bg-blue-50 px-2 py-1 rounded-full">Aujourd'hui</span>
            </div>
            <h3 class="text-gray-500 text-sm font-medium">Messages (24h)</h3>
            <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['messages_today']) }}</p>
        </div>

        <!-- Stat Card 3 -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-orange-50 rounded-lg flex items-center justify-center text-orange-600">
                    <i class="fas fa-robot text-xl"></i>
                </div>
                <span class="text-xs font-medium text-gray-400">Ratio Bot/Humain</span>
            </div>
            <h3 class="text-gray-500 text-sm font-medium">Réponses IA</h3>
            <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['bot_messages']) }}</p>
        </div>

        <!-- Stat Card 4 -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-red-50 rounded-lg flex items-center justify-center text-red-600">
                    <i class="fas fa-exclamation-triangle text-xl"></i>
                </div>
                @if($stats['failed_webhooks_today'] > 0)
                    <span class="text-xs font-medium text-red-600 bg-red-50 px-2 py-1 rounded-full">Attention</span>
                @else
                    <span class="text-xs font-medium text-green-600 bg-green-50 px-2 py-1 rounded-full">OK</span>
                @endif
            </div>
            <h3 class="text-gray-500 text-sm font-medium">Erreurs Webhook (24h)</h3>
            <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['failed_webhooks_today']) }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Recent Conversations -->
        <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-bold text-gray-800">Conversations Récentes</h3>
                <a href="{{ route('admin.conversations') }}" class="text-sm text-blue-600 hover:underline">Voir tout</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-gray-50 text-xs uppercase text-gray-500 font-semibold">
                            <th class="px-6 py-4">Utilisateur</th>
                            <th class="px-6 py-4">Dernier Message</th>
                            <th class="px-6 py-4">Date</th>
                            <th class="px-6 py-4">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($recentConversations as $conv)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-gray-500 text-xs">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <span class="text-sm font-medium text-gray-900">{{ $conv->user_identifier }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm text-gray-500 truncate max-w-xs">
                                        {{ $conv->messages->last()->content ?? 'Aucun message' }}
                                    </p>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    {{ $conv->last_message_at ? $conv->last_message_at->diffForHumans() : 'N/A' }}
                                </td>
                                <td class="px-6 py-4">
                                    <a href="{{ route('admin.conversations.show', $conv->id) }}"
                                        class="text-blue-600 hover:text-blue-800 text-sm font-medium">Détails</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Quick Actions / AI Status -->
        <div class="space-y-6">
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <h3 class="font-bold text-gray-800 mb-4">Statut de l'IA</h3>
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">Fournisseur</span>
                        <span class="text-sm font-bold text-gray-900">{{ strtoupper(config('chatbot.ai_provider')) }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">Modèle</span>
                        <span class="text-sm font-medium text-gray-700">{{ config('chatbot.openai_model') }}</span>
                    </div>
                    <div class="pt-4 border-t border-gray-100">
                        <div class="flex items-center gap-2 text-green-600 text-sm font-medium">
                            <span class="relative flex h-3 w-3">
                                <span
                                    class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                            </span>
                            Système Opérationnel
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-blue-600 to-blue-700 p-6 rounded-xl shadow-lg text-white">
                <h3 class="font-bold mb-2">Besoin d'aide ?</h3>
                <p class="text-blue-100 text-sm mb-4">Consultez la documentation pour configurer les nouveaux produits ou
                    modifier le comportement de Sophie.</p>
                <a href="#"
                    class="inline-block bg-white text-blue-600 px-4 py-2 rounded-lg text-sm font-bold hover:bg-blue-50 transition-colors">Documentation</a>
            </div>
        </div>
    </div>
@endsection