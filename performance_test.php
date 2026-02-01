<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== PERFORMANCE PROFILING ===\n\n";

// Test 1: Sadece MatchEngine
echo "Test 1: MatchEngine gol üretimi (1000 iterasyon)\n";
$matchEngine = new \App\Domain\Simulation\MatchEngine();

$start = microtime(true);
for ($i = 0; $i < 1000; $i++) {
    $matchEngine->simulateMatch(
        \App\Models\Team::first(),
        \App\Models\Team::skip(1)->first(),
        collect(),
        1
    );
}
$end = microtime(true);
$duration = ($end - $start) * 1000;
echo "Toplam: " . round($duration, 2) . "ms\n";
echo "Ortalama: " . round($duration / 1000, 2) . "ms per match\n\n";

// Test 2: PredictionService
echo "Test 2: PredictionService hesaplama\n";
$predictionService = app(\App\Services\League\PredictionService::class);
$groupTeams = \App\Models\GroupTeam::with('team')->where('group_id', 1)->get();

$start = microtime(true);
for ($i = 0; $i < 100; $i++) {
    $predictionService->calculatePredictionsInMemory($groupTeams, 1);
}
$end = microtime(true);
$duration = ($end - $start) * 1000;
echo "Toplam (100 iterasyon): " . round($duration, 2) . "ms\n";
echo "Ortalama: " . round($duration / 100, 2) . "ms per calculation\n\n";

// Test 3: Tek maç simülasyonu (gerçek)
echo "Test 3: Gerçek tek maç simülasyonu\n";
$matchService = app(\App\Services\League\MatchService::class);

try {
    $start = microtime(true);
    $result = $matchService->playNextMatch();
    $end = microtime(true);
    $duration = ($end - $start) * 1000;
    echo "Süre: " . round($duration, 2) . "ms\n";
} catch (\Exception $e) {
    echo "Hata: " . $e->getMessage() . "\n";
}
