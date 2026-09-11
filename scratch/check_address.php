<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$withoutKec = \App\Models\Order::where('address', 'not like', '%Kec%')->count();
$withoutKab = \App\Models\Order::where('address', 'not like', '%Kab%')->count();
echo "Without Kec: $withoutKec, Without Kab: $withoutKab\n";

if ($withoutKec > 0) {
    echo "Examples without Kec:\n";
    foreach(\App\Models\Order::where('address', 'not like', '%Kec%')->take(5)->get() as $o) {
        echo "  {$o->id} | {$o->customer_name}: {$o->address}\n";
    }
}
