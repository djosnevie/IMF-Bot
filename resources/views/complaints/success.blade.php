<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Succès - IMF Bisou Bisou</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen p-4">
    <div class="max-w-md w-full bg-white rounded-xl shadow-lg p-8 text-center border-t-4 border-green-500">
        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100 mb-4">
            <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
        </div>
        <h2 class="text-xl font-bold text-gray-900 mb-2">Formulaire soumis avec succès !</h2>
        <p class="text-gray-600 mb-4">Votre demande a bien été enregistrée.</p>
        <div class="bg-gray-100 p-3 rounded text-sm text-gray-800 mb-6 font-mono">
            Référence: <strong>{{ $reference }}</strong>
        </div>
        <p class="text-sm text-gray-500">Vous allez recevoir une confirmation sur WhatsApp. Vous pouvez fermer cette page en toute sécurité.</p>
    </div>
</body>
</html>
