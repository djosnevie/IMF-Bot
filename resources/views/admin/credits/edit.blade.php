@extends('layouts.admin')

@section('title', 'Modifier Crédit')
@section('page_title', 'Modifier le Produit de Crédit')

@section('content')
    <div class="max-w-4xl mx-auto">
        <div class="mb-6">
            <a href="{{ route('admin.credits') }}"
                class="text-sm text-gray-500 hover:text-gray-800 flex items-center gap-2">
                <i class="fas fa-arrow-left"></i>
                Retour à la liste
            </a>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-8">
                <form action="{{ route('admin.credits.update', $credit->id) }}" method="POST" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="display_name" class="block text-sm font-semibold text-gray-700 mb-2">Nom du
                                Crédit</label>
                            <input type="text" name="display_name" id="display_name"
                                value="{{ old('display_name', $credit->display_name) }}" required
                                class="block w-full px-4 py-3 border border-gray-200 rounded-xl text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                placeholder="Ex: Crédit Scolaire">
                            @error('display_name')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="reference" class="block text-sm font-semibold text-gray-700 mb-2">Référence (ID
                                unique)</label>
                            <input type="text" name="reference" id="reference"
                                value="{{ old('reference', $credit->reference) }}" required
                                class="block w-full px-4 py-3 border border-gray-200 rounded-xl text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                placeholder="Ex: credit_scolaire">
                            @error('reference')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label for="amount_range" class="block text-sm font-semibold text-gray-700 mb-2">Tranche de
                                Montant</label>
                            <input type="text" name="amount_range" id="amount_range"
                                value="{{ old('amount_range', $credit->amount_range) }}"
                                class="block w-full px-4 py-3 border border-gray-200 rounded-xl text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                placeholder="Ex: 100 $ à 5.000 $">
                        </div>

                        <div>
                            <label for="duration_range" class="block text-sm font-semibold text-gray-700 mb-2">Durée</label>
                            <input type="text" name="duration_range" id="duration_range"
                                value="{{ old('duration_range', $credit->duration_range) }}"
                                class="block w-full px-4 py-3 border border-gray-200 rounded-xl text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                placeholder="Ex: 1 à 12 mois">
                        </div>

                        <div>
                            <label for="interest_rate" class="block text-sm font-semibold text-gray-700 mb-2">Taux
                                d'intérêt</label>
                            <input type="text" name="interest_rate" id="interest_rate"
                                value="{{ old('interest_rate', $credit->interest_rate) }}"
                                class="block w-full px-4 py-3 border border-gray-200 rounded-xl text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                placeholder="Ex: 2% par mois">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="display_order" class="block text-sm font-semibold text-gray-700 mb-2">Ordre
                                d'affichage</label>
                            <input type="number" name="display_order" id="display_order"
                                value="{{ old('display_order', $credit->display_order) }}"
                                class="block w-full px-4 py-3 border border-gray-200 rounded-xl text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                        </div>

                        <div class="flex items-center pt-8">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $credit->is_active) ? 'checked' : '' }}
                                class="w-5 h-5 text-green-600 border-gray-300 rounded focus:ring-green-500">
                            <label for="is_active" class="ml-2 text-sm font-semibold text-gray-700">Produit Actif</label>
                        </div>
                    </div>

                    <div class="pt-6 border-t border-gray-100">
                        <button type="submit"
                            class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-4 px-4 rounded-xl shadow-lg shadow-green-200 transition-all transform active:scale-[0.98]">
                            Mettre à jour le Crédit
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection