<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$file = $argv[1] ?? '';
$confirmed = in_array('--confirm', $argv, true);

if ($file === '' || !is_file($file)) {
    fwrite(STDERR, "Usage: php scripts/run-sql.php <file.sql> [--confirm]\n");
    exit(1);
}

$sql = file_get_contents($file);

$config = DB::connection()->getConfig();

echo "Database: {$config['host']} / {$config['database']}\n";

$writes = (bool) preg_match('/\b(insert|update|delete|alter|drop|create|truncate)\b/i', $sql);

if ($writes && !$confirmed) {
    fwrite(STDERR, "This file changes data. Check the database above, then run again with --confirm.\n");
    exit(1);
}

try {
    if ($writes || str_contains($sql, '$$')) {
        DB::connection()->getPdo()->exec($sql);
        echo "OK\n";
        exit(0);
    }

    $statements = array_filter(array_map('trim', preg_split('/;\s*(?:\R|$)/', $sql)));

    foreach ($statements as $statement) {
        $statement = trim(preg_replace('/^\s*--.*$/m', '', $statement));

        if ($statement === '') {
            continue;
        }

        echo "\n> " . strtok($statement, "\n") . "\n";

        $rows = array_map(fn($row) => (array) $row, DB::select($statement));

        if (!$rows) {
            echo "0 rows\n";
            continue;
        }

        $headers = array_keys($rows[0]);
        $widths = array_map(
            fn($h) => max(strlen($h), ...array_map(fn($r) => strlen((string) $r[$h]), $rows)),
            $headers
        );

        $line = fn(array $cells) => implode(' | ', array_map(
            fn($cell, $w) => str_pad((string) $cell, $w),
            $cells,
            $widths
        )) . "\n";

        echo $line($headers);
        echo implode('-+-', array_map(fn($w) => str_repeat('-', $w), $widths)) . "\n";

        foreach ($rows as $row) {
            echo $line(array_values($row));
        }

        echo count($rows) . " row(s)\n";
    }
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}
