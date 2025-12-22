@extends('layouts.admin')

@section('title', 'Nouveau Compte')
@section('page_title', 'Ajouter un Produit Bancaire')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('admin.accounts') }}" class="text-sm text-gray-500 hover:text-gray-800 flex items-center gap-2">
            <i class="fas fa-arrow-left"></i>
            Retour à la liste
        </a>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-8">
            <form action="{{ route('admin.accounts.store') }}" method="POST" class="space-y-6">
                @csrf
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="display_name" class="block text-sm font-semibold text-gray-700 mb-2">Nom du Produit</label>
                        <input type="text" name="display_name" id="display_name" value="{{ old('display_name') }}" required 
                            class="block w-full px-4 py-3 border border-gray-200 rounded-xl text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                            placeholder="Ex: Épargne Classique">
                        @error('display_name')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="reference" class="block text-sm font-semibold text-gray-700 mb-2">Référence (ID unique)</label>
                        <input type="text" name="reference" id="reference" value="{{ old('reference') }}" required 
                            class="block w-full px-4 py-3 border border-gray-200 rounded-xl text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                            placeholder="Ex: epargne_classique">
                        @error('reference')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label for="account_type" class="block text-sm font-semibold text-gray-700 mb-2">Type de Compte</label>
                        <select name="account_type" id="account_type" required
                            class="block w-full px-4 py-3 border border-gray-200 rounded-xl text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                            <option value="Compte d'Épargne">Compte d'Épargne</option>
                            <option value="Compte Courant">Compte Courant</option>
                            <option value="Compte à Terme">Compte à Terme</option>
                        </select>
                    </div>

                    <div>
                        <label for="category" class="block text-sm font-semibold text-gray-700 mb-2">Catégorie</label>
                        <select name="category" id="category" required
                            class="block w-full px-4 py-3 border border-gray-200 rounded-xl text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                            <option value="Particuliers">Particuliers</option>
                            <option value="Entreprises">Entreprises</option>
                            <option value="Groupements">Groupements</option>
                        </select>
                    </div>

                    <div>
                        <label for="currency" class="block text-sm font-semibold text-gray-700 mb-2">Devise</label>
                        <select name="currency" id="currency" required
                            class="block w-full px-4 py-3 border border-gray-200 rounded-xl text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                            <option value="USD">USD ($)</option>
                            <option value="CDF">CDF (FC)</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label for="interest_rate" class="block text-sm font-semibold text-gray-700 mb-2">Taux d'intérêt</label>
                        <input type="text" name="interest_rate" id="interest_rate" value="{{ old('interest_rate') }}"
                            class="block w-full px-4 py-3 border border-gray-200 rounded-xl text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                            placeholder="Ex: 3% l'an">
                    </div>

                    <div>
                        <label for="initial_deposit" class="block text-sm font-semibold text-gray-700 mb-2">Dépôt Initial</label>
                        <input type="text" name="initial_deposit" id="initial_deposit" value="{{ old('initial_deposit') }}"
                            class="block w-full px-4 py-3 border border-gray-200 rounded-xl text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                            placeholder="Ex: 10 $">
                    </div>

                    <div>
                        <label for="maintenance_fee" class="block text-sm font-semibold text-gray-700 mb-2">Frais de Tenue</label>
                        <input type="text" name="maintenance_fee" id="maintenance_fee" value="{{ old('maintenance_fee') }}"
                            class="block w-full px-4 py-3 border border-gray-200 rounded-xl text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                            placeholder="Ex: Gratuit">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="display_order" class="block text-sm font-semibold text-gray-700 mb-2">Ordre d'affichage</label>
                        <input type="number" name="display_order" id="display_order" value="{{ old('display_order', 0) }}"
                            class="block w-full px-4 py-3 border border-gray-200 rounded-xl text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                    </div>

                    <div class="flex items-center pt-8">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                            class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <label for="is_active" class="ml-2 text-sm font-semibold text-gray-700">Produit Actif</label>
                    </div>
                </div>

                <div class="pt-6 border-t border-gray-100">
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 px-4 rounded-xl shadow-lg shadow-blue-200 transition-all transform active:scale-[0.98]">
                        Enregistrer le Produit
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
