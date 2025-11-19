<?php

/**
 * Script untuk menampilkan daftar semua tabel yang diurutkan dari terbaru ke terlama
 * berdasarkan timestamp migration (dalam hitungan detik)
 */

$migrations = glob('database/migrations/*.php');
$tables = [];

foreach ($migrations as $file) {
    $content = file_get_contents($file);
    $basename = basename($file);
    
    // Extract timestamp from filename
    $timestamp = null;
    $timestampSeconds = 0;
    
    if (preg_match('/^(\d{4})_(\d{2})_(\d{2})_(\d{2})(\d{2})(\d{2})/', $basename, $matches)) {
        // Format: YYYY_MM_DD_HHMMSS
        $year = (int)$matches[1];
        $month = (int)$matches[2];
        $day = (int)$matches[3];
        $hour = (int)$matches[4];
        $minute = (int)$matches[5];
        $second = (int)$matches[6];
        
        $timestamp = sprintf('%04d_%02d_%02d_%02d%02d%02d', $year, $month, $day, $hour, $minute, $second);
        $timestampSeconds = mktime($hour, $minute, $second, $month, $day, $year);
    } elseif (preg_match('/^(\d{4})_(\d{2})_(\d{2})_(\d{6})/', $basename, $matches)) {
        // Format: YYYY_MM_DD_HHMMSS (alternative)
        $year = (int)$matches[1];
        $month = (int)$matches[2];
        $day = (int)$matches[3];
        $time = $matches[4];
        $hour = (int)substr($time, 0, 2);
        $minute = (int)substr($time, 2, 2);
        $second = (int)substr($time, 4, 2);
        
        $timestamp = sprintf('%04d_%02d_%02d_%02d%02d%02d', $year, $month, $day, $hour, $minute, $second);
        $timestampSeconds = mktime($hour, $minute, $second, $month, $day, $year);
    } else {
        // For Laravel default migrations like 0001_01_01_000000
        // These are base migrations, treat them as oldest
        $timestamp = substr($basename, 0, 19);
        // Parse the timestamp: 0001_01_01_000000 means they're base migrations
        // Set to epoch start (1970-01-01) so they appear at the bottom
        $timestampSeconds = 0;
    }
    
    // Find Schema::create statements
    if (preg_match_all('/Schema::create\([\'\"]([^\'\"]+)[\'\"]/', $content, $matches)) {
        foreach ($matches[1] as $table) {
            $tables[] = [
                'timestamp' => $timestamp,
                'timestamp_seconds' => $timestampSeconds,
                'table' => $table,
                'file' => $basename,
                'date_formatted' => date('Y-m-d H:i:s', $timestampSeconds)
            ];
        }
    }
}

// Sort by timestamp seconds descending (newest first)
usort($tables, function($a, $b) {
    return $b['timestamp_seconds'] <=> $a['timestamp_seconds'];
});

echo "════════════════════════════════════════════════════════════════════════════════════════\n";
echo "DAFTAR TABEL DARI TERBARU KE TERLAMA (BERDASARKAN TIMESTAMP DALAM DETIK)\n";
echo "════════════════════════════════════════════════════════════════════════════════════════\n\n";
printf("%-4s %-25s | %-35s | %-19s | %-15s | %s\n", 
    "No", "Timestamp", "Nama Tabel", "Tanggal & Waktu", "Detik (Unix)", "Waktu Lalu"
);
echo str_repeat("-", 140) . "\n";

$counter = 1;
foreach ($tables as $item) {
    $secondsAgo = time() - $item['timestamp_seconds'];
    $daysAgo = floor($secondsAgo / 86400);
    $hoursAgo = floor(($secondsAgo % 86400) / 3600);
    $minutesAgo = floor(($secondsAgo % 3600) / 60);
    
    $timeAgo = '';
    if ($daysAgo > 0) {
        $timeAgo = sprintf('%d hari, %d jam yang lalu', $daysAgo, $hoursAgo);
    } elseif ($hoursAgo > 0) {
        $timeAgo = sprintf('%d jam, %d menit yang lalu', $hoursAgo, $minutesAgo);
    } elseif ($minutesAgo > 0) {
        $timeAgo = sprintf('%d menit yang lalu', $minutesAgo);
    } else {
        $timeAgo = sprintf('%d detik yang lalu', $secondsAgo);
    }
    
    $secondsDisplay = number_format($item['timestamp_seconds']);
    printf("%3d. %-25s | %-35s | %s | %15s detik | %s\n", 
        $counter++,
        $item['timestamp'],
        $item['table'],
        $item['date_formatted'],
        $secondsDisplay,
        $timeAgo
    );
}

echo "\n════════════════════════════════════════════════════════════════════════════════════════\n";
echo "Total: " . count($tables) . " tabel\n";
echo "════════════════════════════════════════════════════════════════════════════════════════\n";

