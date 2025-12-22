@extends('layouts.admin')

@section('title', 'Logs Webhook')
@section('page_title', 'Historique des Webhooks')

@section('content')
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
            <h3 class="font-bold text-gray-800">Dernières activités techniques</h3>
            <span class="px-3 py-1 bg-gray-100 text-gray-600 rounded-full text-xs font-bold">
                {{ $logs->total() }} logs enregistrés
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-gray-50 text-xs uppercase text-gray-500 font-semibold">
                        <th class="px-6 py-4">Date & Heure</th>
                        <th class="px-6 py-4">Statut</th>
                        <th class="px-6 py-4">IP</th>
                        <th class="px-6 py-4">Réponse</th>
                        <th class="px-6 py-4 text-right">Détails</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($logs as $log)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <span
                                        class="text-sm font-medium text-gray-900">{{ $log->created_at->format('d/m/Y') }}</span>
                                    <span class="text-xs text-gray-400">{{ $log->created_at->format('H:i:s') }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span
                                    class="px-2 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider {{ $log->status === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                    {{ $log->status === 'success' ? 'Succès' : 'Échec' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                {{ $log->ip_address ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-xs text-gray-500 truncate max-w-xs">
                                    {{ is_array($log->response) ? json_encode($log->response) : $log->response }}
                                </p>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                    <i class="fas fa-code"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div class="p-6 border-t border-gray-100 bg-gray-50/30">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
@endsection