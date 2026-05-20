@extends('layouts.admin')

@section('title', 'Comptes & Épargne')
@section('page_title', 'Gestion des Comptes')

@section('content')
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-100 flex items-center justify-between bg-blue-50/30">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center text-white">
                    <i class="fas fa-university"></i>
                </div>
                <div>
                    <h3 class="font-bold text-gray-900">Comptes & Épargne</h3>
                    <p class="text-xs text-gray-500">Ces données servent de contexte à l'IA pour répondre aux clients.</p>
                </div>
            </div>
            <a href="{{ route('admin.accounts.create') }}"
                class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-bold hover:bg-blue-700 transition-colors">
                <i class="fas fa-plus mr-2"></i> Nouveau Compte
            </a>
        </div>

        @if(session('success'))
            <div class="m-6 p-4 bg-green-50 border border-green-100 text-green-700 rounded-xl text-sm font-medium">
                {{ session('success') }}
            </div>
        @endif
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-gray-50 text-xs uppercase text-gray-500 font-semibold">
                        <th class="px-6 py-4">Produit</th>
                        <th class="px-6 py-4">Type / Catégorie</th>
                        <th class="px-6 py-4">Devise</th>
                        <th class="px-6 py-4">Taux</th>
                        <th class="px-6 py-4">Statut</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($accounts as $account)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4">
                                <div>
                                    <p class="text-sm font-bold text-gray-900">{{ $account->display_name }}</p>
                                    <p class="text-[10px] text-gray-400 font-mono">{{ $account->reference }}</p>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col gap-1">
                                    <span class="text-xs text-gray-600">{{ $account->account_type }}</span>
                                    <span class="text-[10px] font-bold text-blue-500 uppercase">{{ $account->category }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span
                                    class="font-bold {{ $account->currency === 'USD' ? 'text-green-600' : 'text-blue-600' }}">{{ $account->currency }}</span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                {{ $account->interest_rate }}
                            </td>
                            <td class="px-6 py-4">
                                <span
                                    class="px-2 py-1 rounded-full text-[10px] font-bold uppercase {{ $account->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                    {{ $account->is_active ? 'Actif' : 'Inactif' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('admin.accounts.edit', $account->uuid) }}"
                                        class="p-2 text-gray-400 hover:text-blue-600 transition-colors"><i
                                            class="fas fa-edit"></i></a>
                                    <form action="{{ route('admin.accounts.destroy', $account->uuid) }}" method="POST"
                                        onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce compte ?');"
                                        class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-2 text-gray-400 hover:text-red-600 transition-colors">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection