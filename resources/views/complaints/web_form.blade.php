<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Soumettre une plainte - IMF Bisou Bisou</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-gray-50 text-gray-800 antialiased p-4 sm:p-6 min-h-screen flex items-center justify-center">

    <div class="max-w-lg w-full bg-white rounded-2xl shadow-xl overflow-hidden" x-data="{ submitting: false }">
        
        <!-- Header -->
        <div class="bg-[#1C2434] text-white p-6 text-center">
            <h1 class="text-2xl font-bold">IMF Bisou Bisou</h1>
            <p class="text-sm mt-1 text-gray-300">Formulaire de réclamation</p>
        </div>

        <!-- Form Content -->
        <div class="p-6 sm:p-8">
            <p class="text-sm text-gray-600 mb-6">
                Veuillez remplir ce formulaire pour que notre équipe puisse traiter votre demande rapidement.
            </p>

            @if ($errors->any())
                <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800">Il y a des erreurs avec votre soumission</h3>
                            <ul class="mt-1 text-sm text-red-700 list-disc list-inside">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            <form action="{{ route('complaint.submit', ['signature' => request()->query('signature')]) }}" method="POST" @submit="submitting = true">
                @csrf
                <input type="hidden" name="user_identifier" value="{{ $user_identifier }}">
                <input type="hidden" name="conversation_id" value="{{ $conversation_id }}">
                <input type="hidden" name="nonce" value="{{ $nonce }}">

                <!-- Type de plainte -->
                <div class="mb-5">
                    <label for="complaint_type_code" class="block text-sm font-semibold text-gray-700 mb-2">Sujet de votre demande <span class="text-red-500">*</span></label>
                    <select id="complaint_type_code" name="complaint_type_code" required
                        class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-[#1C2434] focus:border-[#1C2434] outline-none transition-colors bg-white">
                        <option value="" disabled selected>Sélectionnez un sujet</option>
                        @foreach($complaintTypes as $type)
                            <option value="{{ $type->code }}" {{ old('complaint_type_code') == $type->code ? 'selected' : '' }}>
                                {{ $type->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Urgence -->
                <div class="mb-5">
                    <label for="urgency" class="block text-sm font-semibold text-gray-700 mb-2">Niveau d'urgence <span class="text-red-500">*</span></label>
                    <select id="urgency" name="urgency" required
                        class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-[#1C2434] focus:border-[#1C2434] outline-none transition-colors bg-white">
                        <option value="low" {{ old('urgency') == 'low' ? 'selected' : '' }}>Faible - Je peux patienter</option>
                        <option value="medium" {{ old('urgency', 'medium') == 'medium' ? 'selected' : '' }}>Moyenne - Gênant mais pas bloquant</option>
                        <option value="high" {{ old('urgency') == 'high' ? 'selected' : '' }}>Haute - Problème critique ou bloquant</option>
                    </select>
                </div>

                <!-- Description -->
                <div class="mb-6">
                    <label for="description" class="block text-sm font-semibold text-gray-700 mb-2">Description détaillée <span class="text-red-500">*</span></label>
                    <textarea id="description" name="description" rows="4" required maxlength="2000"
                        placeholder="Veuillez décrire votre problème en détail..."
                        class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-[#1C2434] focus:border-[#1C2434] outline-none transition-colors resize-none">{{ old('description') }}</textarea>
                    <p class="text-xs text-gray-500 mt-1">Merci d'être le plus précis possible pour nous aider à vous répondre au mieux.</p>
                </div>

                <!-- Submit Button -->
                <button type="submit" 
                    :disabled="submitting"
                    class="w-full bg-[#1C2434] hover:bg-[#2b3548] text-white font-semibold py-3 px-4 rounded-lg transition-colors flex items-center justify-center disabled:opacity-70 disabled:cursor-not-allowed">
                    <span x-show="!submitting">Envoyer la demande</span>
                    <span x-show="submitting" x-cloak class="flex items-center">
                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Envoi en cours...
                    </span>
                </button>

            </form>
        </div>
    </div>
</body>
</html>
