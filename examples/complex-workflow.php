<?php
require __DIR__.'/../vendor/autoload.php';

use tommyknocker\chain\Chain;
use tommyknocker\chain\tests\fixtures\DataProcessor;
use tommyknocker\chain\tests\fixtures\Report;

// Complex workflow with multiple transformations
$report = Chain::of(new DataProcessor())
    ->addItem(10)
    ->addItem(25)
    ->addItem(5)
    ->addItem(30)
    ->addItem(15)
    ->tap(fn($p) => print("Initial data: " . implode(', ', $p->getData()) . "\n"))
    ->filter(fn($x) => $x >= 10)
    ->tap(fn($p) => print("After filter: " . implode(', ', $p->getData()) . "\n"))
    ->transform(fn($x) => $x * 2)
    ->tap(fn($p) => print("After transform: " . implode(', ', $p->getData()) . "\n"))
    ->pipe(
        fn($p) => ['total' => $p->sum(), 'count' => $p->count()],
        fn($stats) => new Report($stats['total'], $stats['count'])
    )
    ->format()
    ->get();

echo "Report: $report\n";


