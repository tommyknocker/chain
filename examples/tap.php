<?php
require __DIR__.'/../vendor/autoload.php';

use tommyknocker\chain\Chain;
use tommyknocker\chain\tests\fixtures\Logger;

// Using tap for side effects without breaking the chain
$logger = Chain::of(new Logger())
    ->log('Starting process')
    ->log('Loading data')
    ->tap(fn($l) => print("Current logs: " . $l->count() . "\n"))
    ->log('Processing')
    ->tap(fn($l) => print("Logs so far: " . implode(', ', $l->getLogs()) . "\n"))
    ->log('Completed')
    ->instance();

echo "Total logs: " . $logger->count() . "\n";

