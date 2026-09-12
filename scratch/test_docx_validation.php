<?php

use App\Services\OutgoingLetterTemplateStorage;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\UploadedFile;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$storage = app(OutgoingLetterTemplateStorage::class);
$reflection = new ReflectionClass($storage);
$method = $reflection->getMethod('isSafeDocx');

$file = new UploadedFile(
    base_path('public/templates/template-surat-dinas-bagian.docx'),
    'template-surat-dinas-bagian.docx',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    null,
    true
);

$isSafe = $method->invoke($storage, $file);
echo 'IS_SAFE_DOCX: '.($isSafe ? 'YES' : 'NO').PHP_EOL;

if ($isSafe) {
    echo 'File size: '.filesize(base_path('public/templates/template-surat-dinas-bagian.docx'))." bytes\n";
    echo 'SHA256: '.hash_file('sha256', base_path('public/templates/template-surat-dinas-bagian.docx'))."\n";
}
