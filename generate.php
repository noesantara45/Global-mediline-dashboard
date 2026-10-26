<?php

$models = ['InboundInvoice', 'InboundInvoiceItem', 'OutboundInvoice', 'OutboundInvoiceItem'];
$resources = ['InboundInvoice', 'OutboundInvoice'];

foreach ($models as $model) {
    echo "Creating model $model...\n";
    shell_exec("php artisan make:model $model");

    $file = __DIR__."/app/Models/$model.php";
    if (file_exists($file)) {
        $content = file_get_contents($file);
        if (! str_contains($content, '$guarded')) {
            $content = str_replace('use HasFactory;', "use HasFactory;\n\n    protected \$guarded = [];", $content);
            file_put_contents($file, $content);
        }
    }
}

foreach ($resources as $res) {
    echo "Creating resource $res...\n";
    echo shell_exec("php artisan make:filament-resource $res --generate --force");
}
