@extends('layouts.admin')

@section('title', 'Accès Refusé')
@section('page_title', 'Erreur 403 - Non autorisé')

@section('content')
<div class="min-h-[60vh] flex flex-col items-center justify-center text-center px-4">
    <div class="w-24 h-24 bg-red-100 rounded-full flex items-center justify-center mb-6">
        <i class="fas fa-shield-alt text-4xl text-red-600"></i>
    </div>
    
    <h1 class="text-3xl font-bold text-gray-900 mb-2">Accès Refusé</h1>
    
    <p class="text-lg text-gray-600 mb-8 max-w-md">
        Désolé, vous n'avez pas les permissions nécessaires pour accéder à cette ressource ou effectuer cette action.
    </p>

    @if(isset($exception) && config('app.debug'))
        <div class="mb-8 p-4 bg-gray-100 rounded text-sm text-gray-500 text-left max-w-lg w-full break-words">
            <strong>Détails (Debug) :</strong> {{ $exception->getMessage() }}
        </div>
    @endif
    
    <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
        <i class="fas fa-home mr-2"></i>
        Retour au tableau de bord
    </a>
</div>
@endsection
