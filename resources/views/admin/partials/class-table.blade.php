<div class="card"><canvas id="ch-classes" height="90"></canvas></div>
<div class="table-wrap card mt"><table class="tbl"><tr><th>Classe</th><th class="num">Élèves</th><th class="num">Réservations</th><th class="num">Servis</th><th class="num">Non retirés</th><th class="num">Repas par élève</th></tr>
    @foreach ($stats as $r)<tr><td><b>{{ $r['class'] }}</b></td><td class="num">{{ $r['students'] }}</td><td class="num">{{ $r['n'] }}</td><td class="num">{{ $r['served'] }}</td><td class="num">{{ $r['missed'] }}</td><td class="num">{{ $r['per'] }}</td></tr>@endforeach
</table><p class="xs muted mt">Seuls les élèves ayant renseigné leur classe sont comptés ; la classe est figée au moment de chaque réservation (class_snapshot).</p></div>
@push('scripts')
<script src="{{ asset('vendor/chart.umd.js') }}"></script>
<script>
const C = @json($stats->values());
new Chart(document.getElementById('ch-classes'), { type: 'bar', data: { labels: C.map(c => c.class), datasets: [{ label: 'Réservations', data: C.map(c => c.n), backgroundColor: '#87c1e9', borderRadius: 4 }, { label: 'Servis', data: C.map(c => c.served), backgroundColor: '#1d1d1b', borderRadius: 4 }] }, options: { plugins: { legend: { position: 'bottom' } } } });
</script>
@endpush
