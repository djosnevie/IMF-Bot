@extends('layouts.admin')

@section('title', 'Crédits & Prêts')
@section('page_title', 'Gestion des Crédits')

@section('content')
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-100 flex items-center justify-between bg-green-50/30">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-green-600 rounded-lg flex items-center justify-center text-white">
                    <i class="fas fa-hand-holding-usd"></i>
                </div>
                <div>
                    <h3 class="font-bold text-gray-900">Crédits & Prêts</h3>
                    <p class="text-xs text-gray-500">Informations sur les types de crédits disponibles.</p>
                </div>
            </div>
            <button
                class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-bold hover:bg-green-700 transition-colors">
                <i class="fas fa-plus mr-2"></i> Nouveau Crédit
            </button>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-gray-50 text-xs uppercase text-gray-500 font-semibold">
                        <th class="px-6 py-4">Produit</th>
                        <th class="px-6 py-4">Montant</th>
                        <th class="px-6 py-4">Durée</th>
                        <th class="px-6 py-4">Taux</th>
                        <th class="px-6 py-4">Statut</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($credits as $credit)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4">
                                <div>
                                    <p class="text-sm font-bold text-gray-900">{{ $credit->display_name }}</p>
                                    <p class="text-[10px] text-gray-400 font-mono">{{ $credit->reference }}</p>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                {{ $credit->amount_range }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                {{ $credit->duration_range }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                {{ $credit->interest_rate }}
                            </td>
                            <td class="px-6 py-4">
                                <span
                                    class="px-2 py-1 rounded-full text-[10px] font-bold uppercase {{ $credit->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                    {{ $credit->is_active ? 'Actif' : 'Inactif' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex justify-end gap-2">
                                    <button class="p-2 text-gray-400 hover:text-blue-600 transition-colors"><i
                                            class="fas fa-edit"></i></button>
                                    <button class="p-2 text-gray-400 hover:text-red-600 transition-colors"><i
                                            class="fas fa-trash"></i></button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection