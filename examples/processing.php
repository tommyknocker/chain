<?php

require __DIR__ . '/../vendor/autoload.php';

use tommyknocker\chain\Chain;
use tommyknocker\chain\tests\fixtures\DataProcessor;
use tommyknocker\chain\tests\fixtures\Report;

/*
 * Data processing with iteration and debugging
 * Demonstrates: tap(), filter/transform, pipe(), dump(), each(), value()
 */

echo "\nData Processing Examples\n";
echo str_repeat('=', 60) . "\n\n";

// ==================== Processing with dump() ====================

echo "1. Processing with debug output\n";
echo str_repeat('-', 60) . "\n";

$data = [10, 25, 5, 30, 15];

$report = Chain::of((object) ['data' => $data])
    ->pipe(
        fn ($obj) => $obj->data,
        fn ($data) => (object) ['data' => $data]
    )
    ->dump('Raw data')
    ->pipe(
        fn ($obj) => array_filter($obj->data, fn ($x) => $x >= 10),
        fn ($data) => (object) ['data' => $data]
    )
    ->dump('After filter')
    ->pipe(
        fn ($obj) => array_map(fn ($x) => $x * 2, $obj->data),
        fn ($data) => (object) ['data' => $data]
    )
    ->dump('After transform')
    ->pipe(fn ($obj) => $obj->data)
    ->value();

echo "\n";

// ==================== Iteration with each() ====================

echo "2. Iterate over collection\n";
echo str_repeat('-', 60) . "\n";

$users = ['Alice', 'Bob', 'Charlie'];
$log = [];

Chain::of((object) ['users' => $users])
    ->pipe(fn ($obj) => $obj->users)
    ->each(function ($name, $index) use (&$log) {
        $log[] = "$index: $name";
        echo "Processing user #$index: $name\n";
    })
    ->value();

echo "\n";

// ==================== Complex transformation pipeline ====================

echo "3. Full pipeline with reporting\n";
echo str_repeat('-', 60) . "\n";

$finalReport = Chain::of(new DataProcessor())
    ->addItem(100)
    ->addItem(250)
    ->addItem(75)
    ->addItem(300)
    ->filter(fn ($x) => $x >= 100)
    ->transform(fn ($x) => $x * 1.08)
    ->tap(fn ($p) => print('Items processed: ' . $p->count() . "\n"))
    ->pipe(
        fn ($p) => ['total' => $p->sum(), 'count' => $p->count()],
        fn ($stats) => new Report($stats['total'], $stats['count']),
        fn ($report) => $report->format()
    )
    ->value(); // using value() instead of get()

echo "Final report: $finalReport\n\n";

echo str_repeat('=', 60) . "\n";
echo "✓ dump() - debug output during chain\n";
echo "✓ each() - iterate over collections\n";
echo "✓ value() - semantic result extraction\n";
echo "✓ pipe() - transformation pipelines\n";
echo str_repeat('=', 60) . "\n\n";
