<?php
require __DIR__.'/../vendor/autoload.php';

use tommyknocker\chain\Chain;
use tommyknocker\chain\tests\fixtures\Counter;

// Clone chains for independent branching
$base = Chain::of(new Counter());
$a = $base->clone()->inc(5)->inc(5)->getValue()->get();
$b = $base->clone()->inc(2)->getValue()->get();
$c = $base->getValue()->get();

echo "Branched counters: A=$a, B=$b, C=$c\n";
