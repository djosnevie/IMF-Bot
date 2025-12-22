<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>IMF Bisou Bisou - Produits</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 p-8">
    <div class="max-w-7xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-900">🏦 Nos Produits & Services</h1>
            <span class="bg-blue-100 text-blue-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded">Total:
                {{ $accounts->count() }} produits</span>
        </div>

        <div class="relative overflow-x-auto shadow-md sm:rounded-lg mb-12">
            <table class="w-full text-sm text-left rtl:text-right text-gray-500">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3">Réf</th>
                        <th scope="col" class="px-6 py-3">Nom du Produit</th>
                        <th scope="col" class="px-6 py-3">Type</th>
                        <th scope="col" class="px-6 py-3">Catégorie</th>
                        <th scope="col" class="px-6 py-3">Devise</th>
                        <th scope="col" class="px-6 py-3">Taux</th>
                        <th scope="col" class="px-6 py-3">Dépôt Min</th>
                        <th scope="col" class="px-6 py-3">Frais Tenue</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($accounts as $account)
                        <tr class="bg-white border-b hover:bg-gray-50">
                            <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">
                                {{ $account->reference }}
                            </td>
                            <td class="px-6 py-4 font-bold">
                                {{ $account->display_name }}
                            </td>
                            <td class="px-6 py-4">
                                {{ $account->account_type }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 rounded text-xs font-semibold
                                        {{ $account->category === 'individual' ? 'bg-green-100 text-green-800' : '' }}
                                        {{ $account->category === 'business' ? 'bg-purple-100 text-purple-800' : '' }}
                                        {{ $account->category === 'group' ? 'bg-orange-100 text-orange-800' : '' }}">
                                    {{ ucfirst($account->category) }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span
                                    class="font-bold {{ $account->currency === 'USD' ? 'text-green-600' : 'text-blue-600' }}">
                                    {{ $account->currency }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                {{ $account->interest_rate }}
                            </td>
                            <td class="px-6 py-4">
                                {{ $account->initial_deposit }}
                            </td>
                            <td class="px-6 py-4">
                                {{ $account->maintenance_fee }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Crédits Table -->
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-3xl font-bold text-gray-900">💳 Nos Crédits</h2>
            <span class="bg-green-100 text-green-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded">Total:
                {{ $credits->count() }} crédits</span>
        </div>

        <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
            <table class="w-full text-sm text-left rtl:text-right text-gray-500">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3">Réf</th>
                        <th scope="col" class="px-6 py-3">Nom</th>
                        <th scope="col" class="px-6 py-3">Montant</th>
                        <th scope="col" class="px-6 py-3">Durée</th>
                        <th scope="col" class="px-6 py-3">Taux</th>
                        <th scope="col" class="px-6 py-3">Frais Etude</th>
                        <th scope="col" class="px-6 py-3">Garantie</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($credits as $credit)
                        <tr class="bg-white border-b hover:bg-gray-50">
                            <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">
                                {{ $credit->reference }}
                            </td>
                            <td class="px-6 py-4 font-bold">
                                {{ $credit->display_name }}
                            </td>
                            <td class="px-6 py-4">
                                {{ $credit->amount_range }}
                            </td>
                            <td class="px-6 py-4">
                                {{ $credit->duration_range }}
                            </td>
                            <td class="px-6 py-4">
                                {{ $credit->interest_rate }}
                            </td>
                            <td class="px-6 py-4">
                                {{ $credit->file_fee }}
                            </td>
                            <td class="px-6 py-4 text-xs">
                                {{ Str::limit($credit->guarantee, 50) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>