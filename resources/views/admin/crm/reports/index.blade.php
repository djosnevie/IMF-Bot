@extends('layouts.admin')
@section('title', 'CRM — Rapports')
@section('page_title', 'Rapports CRM')

@section('content')
{{-- Métriques globales --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
    @foreach([
        ['label'=>'Contacts total','value'=>$totals['contacts'],'icon'=>'fa-users','color'=>'from-blue-500 to-blue-600'],
        ['label'=>'Leads actifs','value'=>$totals['leads'],'icon'=>'fa-user-plus','color'=>'from-amber-500 to-amber-600'],
        ['label'=>'Clients convertis','value'=>$totals['clients'],'icon'=>'fa-user-check','color'=>'from-emerald-500 to-emerald-600'],
        ['label'=>'Score moyen','value'=>$totals['avg_score'].'/100','icon'=>'fa-star','color'=>'from-purple-500 to-purple-600'],
    ] as $metric)
    <div class="bg-gradient-to-br {{ $metric['color'] }} rounded-2xl p-5 text-white shadow-sm">
        <div class="flex items-center justify-between mb-3">
            <p class="text-sm font-medium text-white/80">{{ $metric['label'] }}</p>
            <i class="fas {{ $metric['icon'] }} text-white/60"></i>
        </div>
        <p class="text-3xl font-bold">{{ $metric['value'] }}</p>
    </div>
    @endforeach
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    {{-- 1. Nouveaux contacts par semaine --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-4">
            <h4 class="font-bold text-gray-800 flex items-center gap-2"><i class="fas fa-chart-bar text-blue-500"></i> Nouveaux contacts / semaine</h4>
        </div>
        <canvas id="chartNewContacts" height="180"></canvas>
    </div>

    {{-- 2. Conversion par statut --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-4">
            <h4 class="font-bold text-gray-800 flex items-center gap-2"><i class="fas fa-funnel-dollar text-amber-500"></i> Répartition pipeline</h4>
        </div>
        <canvas id="chartConversion" height="180"></canvas>
    </div>

    {{-- 3. Top produits consultés --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <h4 class="font-bold text-gray-800 mb-4 flex items-center gap-2"><i class="fas fa-trophy text-yellow-500"></i> Produits les plus consultés</h4>
        <div class="space-y-3">
            @forelse($topProducts as $product)
            @php $maxProd = $topProducts->max('total') ?: 1; @endphp
            <div class="flex items-center gap-3">
                <span class="text-sm text-gray-700 w-40 truncate">{{ $product['name'] }}</span>
                <div class="flex-1 bg-gray-100 rounded-full h-2">
                    <div class="h-2 rounded-full bg-blue-500 transition-all" style="width: {{ ($product['total'] / $maxProd) * 100 }}%"></div>
                </div>
                <span class="text-xs text-gray-500 w-8 text-right font-semibold">{{ $product['total'] }}</span>
            </div>
            @empty
            <p class="text-sm text-gray-400 italic">Aucune donnée.</p>
            @endforelse
        </div>
    </div>

    {{-- 4. Performance campagnes --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-4">
            <h4 class="font-bold text-gray-800 flex items-center gap-2"><i class="fas fa-paper-plane text-green-500"></i> Performance campagnes</h4>
            <a href="{{ route('admin.crm.reports.export') }}" class="text-xs text-blue-600 hover:underline">
                <i class="fas fa-download mr-1"></i> CSV
            </a>
        </div>
        <div class="space-y-3">
            @forelse($campaigns as $campaign)
            <div class="flex items-center gap-3">
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-700 truncate">{{ $campaign['name'] }}</p>
                    <p class="text-xs text-gray-400">{{ $campaign['sent'] }} envois · {{ $campaign['replied'] }} réponses</p>
                </div>
                <span class="text-sm font-bold {{ $campaign['response_rate'] >= 30 ? 'text-emerald-600' : ($campaign['response_rate'] >= 10 ? 'text-amber-600' : 'text-red-500') }}">
                    {{ $campaign['response_rate'] }}%
                </span>
            </div>
            @empty
            <p class="text-sm text-gray-400 italic">Aucune campagne envoyée.</p>
            @endforelse
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
const chartDefaults = { responsive: true, plugins: { legend: { display: false } } };

// Graphique nouveaux contacts par semaine
new Chart(document.getElementById('chartNewContacts'), {
    type: 'bar',
    data: {
        labels: {!! $newContactsPerWeek->pluck('week')->map(fn($w) => '"S'.$w.'"')->implode(',') !!},
        datasets: [{ data: [{{ $newContactsPerWeek->pluck('total')->implode(',') }}], backgroundColor: '#3B82F6', borderRadius: 6 }]
    },
    options: { ...chartDefaults, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
});

// Graphique conversion pipeline
new Chart(document.getElementById('chartConversion'), {
    type: 'doughnut',
    data: {
        labels: [{{ $conversionByStatus->pluck('commercial_status')->map(fn($s) => '"'.ucfirst(str_replace('_',' ',$s)).'"')->implode(',') }}],
        datasets: [{
            data: [{{ $conversionByStatus->pluck('total')->implode(',') }}],
            backgroundColor: ['#6B7280','#3B82F6','#F59E0B','#10B981','#EF4444'],
            borderWidth: 0
        }]
    },
    options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { padding: 16, font: { size: 12 } } } } }
});
</script>
@endpush
@endsection
