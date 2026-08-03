<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erreur - IMF Bisou Bisou</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen p-4">
    <div class="max-w-md w-full bg-white rounded-xl shadow-lg p-8 text-center border-t-4 border-red-500">
        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
            <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
        </div>
        <h2 class="text-xl font-bold text-gray-900 mb-2">Lien invalide ou expiré</h2>
        <p class="text-gray-600 mb-6">{{ $message ?? 'Ce lien n\'est plus valide.' }}</p>
        <p class="text-sm text-gray-500">Vous pouvez fermer cette page et retourner sur WhatsApp pour initier une nouvelle demande.</p>
    </div>
</body>
</html>
