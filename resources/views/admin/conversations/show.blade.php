@extends('layouts.admin')

@section('title', 'Conversation #' . $conversation->id)
@section('page_title', 'Détails de la Conversation')

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <a href="{{ route('admin.conversations') }}"
            class="text-sm text-gray-500 hover:text-gray-800 flex items-center gap-2">
            <i class="fas fa-arrow-left"></i>
            Retour à la liste
        </a>
        <div class="flex gap-3">
            <span class="px-3 py-1 bg-gray-100 text-gray-600 rounded-lg text-xs font-medium">
                ID: {{ $conversation->user_identifier }}
            </span>
            <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-lg text-xs font-medium">
                {{ $conversation->platform }}
            </span>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Chat Window -->
        <div
            class="lg:col-span-2 flex flex-col bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden h-[700px]">
            <!-- Chat Header -->
            <div class="p-4 border-b border-gray-100 bg-gray-50 flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-blue-600 flex items-center justify-center text-white font-bold">
                    {{ substr($conversation->user_identifier, -2) }}
                </div>
                <div>
                    <h4 class="font-bold text-gray-900">{{ $conversation->user_identifier }}</h4>
                    <p class="text-xs text-green-600 flex items-center gap-1">
                        <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                        En ligne
                    </p>
                </div>
            </div>

            <!-- Messages Area -->
            <div class="flex-1 overflow-y-auto p-6 space-y-6 bg-slate-50">
                @foreach($conversation->messages as $message)
                    @if($message->sender_type === 'user')
                        <!-- User Message -->
                        <div class="flex justify-end">
                            <div class="max-w-[80%]">
                                <div class="bg-blue-600 text-white p-4 rounded-2xl rounded-tr-none shadow-sm">
                                    <p class="text-sm">{{ $message->content }}</p>
                                </div>
                                <p class="text-[10px] text-gray-400 mt-1 text-right">{{ $message->created_at->format('H:i') }}</p>
                            </div>
                        </div>
                    @else
                        <!-- Bot Message -->
                        <div class="flex justify-start">
                            <div class="max-w-[80%]">
                                <div class="flex items-start gap-2">
                                    <div
                                        class="w-8 h-8 rounded-full bg-purple-100 flex items-center justify-center text-purple-600 flex-shrink-0 mt-1">
                                        <i class="fas fa-robot text-xs"></i>
                                    </div>
                                    <div>
                                        <div
                                            class="bg-white text-gray-800 p-4 rounded-2xl rounded-tl-none shadow-sm border border-gray-100">
                                            <p class="text-sm whitespace-pre-wrap">{{ $message->content }}</p>

                                            @if($message->ai_response_metadata)
                                                <div
                                                    class="mt-3 pt-3 border-t border-gray-50 flex items-center gap-4 text-[10px] text-gray-400">
                                                    <span><i class="fas fa-microchip mr-1"></i>
                                                        {{ $message->ai_response_metadata['model'] ?? 'IA' }}</span>
                                                    @if(isset($message->ai_response_metadata['tokens_used']))
                                                        <span><i class="fas fa-coins mr-1"></i>
                                                            {{ $message->ai_response_metadata['tokens_used'] }} tokens</span>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                        <p class="text-[10px] text-gray-400 mt-1">{{ $message->created_at->format('H:i') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>

            <!-- Chat Footer (Read Only for now) -->
            <div class="p-4 bg-white border-t border-gray-100">
                <div class="bg-gray-50 p-3 rounded-xl text-center">
                    <p class="text-xs text-gray-500 italic">Le mode réponse manuelle n'est pas encore activé. Sophie gère
                        cette conversation.</p>
                </div>
            </div>
        </div>

        <!-- Sidebar Info -->
        <div class="space-y-6">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                <h4 class="font-bold text-gray-900 mb-4">Informations Client</h4>
                <div class="space-y-4">
                    <div>
                        <label class="text-[10px] uppercase font-bold text-gray-400 tracking-wider">Identifiant
                            WhatsApp</label>
                        <p class="text-sm font-medium text-gray-800">{{ $conversation->user_identifier }}</p>
                    </div>
                    <div>
                        <label class="text-[10px] uppercase font-bold text-gray-400 tracking-wider">Première
                            interaction</label>
                        <p class="text-sm font-medium text-gray-800">{{ $conversation->created_at->format('d/m/Y H:i') }}
                        </p>
                    </div>
                    <div>
                        <label class="text-[10px] uppercase font-bold text-gray-400 tracking-wider">Statut</label>
                        <div class="mt-1">
                            <span class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs font-bold">ACTIF</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                <h4 class="font-bold text-gray-900 mb-4">Actions</h4>
                <div class="space-y-2">
                    <button
                        class="w-full py-2 px-4 bg-gray-50 hover:bg-gray-100 text-gray-700 rounded-xl text-sm font-medium transition-colors flex items-center justify-center gap-2">
                        <i class="fas fa-archive text-gray-400"></i>
                        Archiver la conversation
                    </button>
                    <button
                        class="w-full py-2 px-4 bg-red-50 hover:bg-red-100 text-red-600 rounded-xl text-sm font-medium transition-colors flex items-center justify-center gap-2">
                        <i class="fas fa-ban"></i>
                        Bloquer l'utilisateur
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection