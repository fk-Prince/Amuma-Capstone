<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$sql = trim($argv[1] ?? '');

if ($sql === '') {
    fwrite(STDERR, "Usage: php scripts/query.php \"<sql>\"\n");
    exit(1);
}

echo 'Database: ' . DB::connection()->getDatabaseName() . "\n";

try {
    if (preg_match('/^\s*(select|show|with|explain)\b/i', $sql)) {
        $rows = array_map(fn($row) => (array) $row, DB::select($sql));

        if (!$rows) {
            echo "0 rows\n";
            exit(0);
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
    } else {
        DB::statement($sql);
        echo "OK\n";
    }
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}
