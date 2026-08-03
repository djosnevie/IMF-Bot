@extends('layouts.admin')

@section('header')
    Gérer les permissions de {{ $user->name }}
@endsection

@php
    $roleTranslations = [
        'super-admin' => 'Super Administrateur',
        'admin' => 'Administrateur',
        'supervisor' => 'Superviseur',
        'agent' => 'Agent de Support',
    ];

    $groupTranslations = [
        'users' => 'Gestion des Utilisateurs',
        'products' => 'Produits (Comptes & Crédits)',
        'conversations' => 'Conversations WhatsApp',
        'tickets' => 'Tickets & Plaintes',
        'logs' => 'Historique Système',
        'config' => 'Configuration',
    ];

    $permissionTranslations = [
        'users.view' => 'Voir la liste des utilisateurs',
        'users.manage' => 'Gérer les utilisateurs (Créer, Éditer, Supprimer)',
        'products.manage' => 'Gérer les produits bancaires',
        'conversations.view_all' => 'Voir toutes les conversations',
        'conversations.view_assigned' => 'Voir uniquement ses conversations assignées',
        'tickets.view_all' => 'Voir tous les tickets',
        'tickets.view_assigned' => 'Voir uniquement ses tickets assignés',
        'tickets.assign' => 'Assigner des tickets aux agents',
        'tickets.comment_internal' => 'Ajouter des notes internes',
        'tickets.comment_public' => 'Répondre publiquement aux clients',
        'logs.view' => 'Consulter les logs (Webhook)',
        'config.manage' => 'Gérer la configuration globale',
    ];
@endphp

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h2 class="text-xl font-semibold text-gray-900">Permissions et Rôle</h2>
        <a href="{{ route('admin.users') }}" class="text-sm text-blue-600 hover:text-blue-500">
            &larr; Retour à la liste
        </a>
    </div>

    <!-- Message d'avertissement si c'est l'utilisateur courant -->
    @if($user->id === auth()->id())
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-yellow-700">
                        Vous ne pouvez pas modifier votre propre rôle ou vos propres permissions.
                    </p>
                </div>
            </div>
        </div>
    @endif

    <!-- Section Informations de Base -->
    <div class="bg-white shadow rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Informations de base</h3>
        <form action="{{ route('admin.users.update', $user->uuid) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                <div class="sm:col-span-3">
                    <label for="name" class="block text-sm font-medium text-gray-700">Nom complet</label>
                    <div class="mt-1">
                        <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" required
                            class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md">
                    </div>
                    @error('name')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="sm:col-span-3">
                    <label for="email" class="block text-sm font-medium text-gray-700">Adresse Email</label>
                    <div class="mt-1">
                        <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" required
                            class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md">
                    </div>
                    @error('email')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="sm:col-span-3">
                    <label for="whatsapp_number" class="block text-sm font-medium text-gray-700">
                        Numéro WhatsApp <span class="text-xs font-normal text-gray-500">(Agents uniquement)</span>
                    </label>
                    <div class="mt-1">
                        <input type="text" name="whatsapp_number" id="whatsapp_number" value="{{ old('whatsapp_number', $user->whatsapp_number) }}"
                            class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md"
                            placeholder="Ex: 243XXXXXXXXX">
                    </div>
                    <p class="mt-1 text-xs text-gray-500">Nécessaire uniquement pour le support client.</p>
                    @error('whatsapp_number')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mt-6">
                <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                    Mettre à jour les informations
                </button>
            </div>
        </form>
    </div>

    <!-- Section Rôle -->
    <div class="bg-white shadow rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Rôle assigné</h3>
        <form action="{{ route('admin.users.role', $user->id) }}" method="POST">
            @csrf
            <div class="flex items-center space-x-4">
                <select name="role" class="block w-full max-w-sm rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" {{ $user->id === auth()->id() ? 'disabled' : '' }}>
                    <option value="">-- Aucun rôle --</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->name }}" {{ $user->hasRole($role->name) ? 'selected' : '' }}>
                            {{ $roleTranslations[$role->name] ?? ucfirst($role->name) }}
                        </option>
                    @endforeach
                </select>
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed" {{ $user->id === auth()->id() ? 'disabled' : '' }}>
                    Mettre à jour le rôle
                </button>
            </div>
            <p class="mt-2 text-sm text-gray-500">Le rôle définit un ensemble de permissions par défaut.</p>
        </form>
    </div>

    <!-- Section Permissions Individuelles -->
    <div class="bg-white shadow rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Permissions individuelles directes</h3>
        <p class="text-sm text-gray-500 mb-6">
            Vous pouvez accorder ou révoquer des permissions spécifiques indépendamment du rôle de l'utilisateur. 
            Les permissions héritées du rôle sont grisées mais actives.
        </p>

        @if($user->roles->isEmpty())
            <div class="bg-orange-50 border-l-4 border-orange-400 p-4 mb-6">
                <div class="flex items-start">
                    <i class="fas fa-info-circle text-orange-400 mt-0.5"></i>
                    <div class="ml-3 text-sm text-orange-700">
                        Cet utilisateur n'a aucun rôle assigné. Il ne peut donc pas recevoir de permissions individuelles. Veuillez d'abord lui assigner un rôle.
                    </div>
                </div>
            </div>
        @endif
        
        <form action="{{ route('admin.users.permissions', $user->id) }}" method="POST">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 opacity-{{ $user->roles->isEmpty() ? '50' : '100' }}">
                @foreach($permissions as $group => $perms)
                    <div class="border rounded-lg p-4">
                        <h4 class="font-medium text-gray-900 mb-3 border-b pb-2">{{ $groupTranslations[$group] ?? ucfirst($group) }}</h4>
                        <div class="space-y-3">
                            @foreach($perms as $permission)
                                @php
                                    // Vérifier si la permission vient du rôle (sans l'avoir directement)
                                    $hasViaRole = $user->hasPermissionTo($permission->name) && !$user->hasDirectPermission($permission->name);
                                @endphp
                                <div class="flex items-start">
                                    <div class="flex items-center h-5">
                                        <input type="checkbox" 
                                               name="permissions[]" 
                                               value="{{ $permission->name }}"
                                               id="perm_{{ $permission->id }}"
                                               class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded"
                                               {{ $user->hasDirectPermission($permission->name) ? 'checked' : '' }}
                                               {{ $hasViaRole || $user->id === auth()->id() || $user->roles->isEmpty() ? 'disabled' : '' }}
                                               {{ $hasViaRole ? 'checked' : '' }}>
                                    </div>
                                    <div class="ml-3 text-sm">
                                        <label for="perm_{{ $permission->id }}" class="font-medium {{ $hasViaRole ? 'text-gray-400' : 'text-gray-700' }}">
                                            {{ $permissionTranslations[$permission->name] ?? $permission->name }}
                                        </label>
                                        @if($hasViaRole)
                                            <span class="block text-xs text-gray-400">Héritée du rôle</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-6 flex justify-end">
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed" {{ $user->id === auth()->id() || $user->roles->isEmpty() ? 'disabled' : '' }}>
                    Enregistrer les permissions individuelles
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
