<?php

$vendorDir = __DIR__ . '/../vendor';

if (!@include($vendorDir . '/autoload.php')) {
    $help = <<<EOT
You must set up the project dependencies, run the following commands:
wget http://getcomposer.org/composer.phar
php composer.phar install --dev

EOT;

    die($help);
}

// pdfcpu 0.16+ refuses to run on a configuration written by an older version, which the older binaries
// under test create in the default location. Older versions ignore this variable. It is set in $_ENV
// as well, because symfony/process does not pass on variables which are only set via putenv().
$pdfcpuConfigRoot = sys_get_temp_dir() . '/toolkit-pdftk-pdfcpu-config';
putenv('PDFCPU_CONFIG_ROOT=' . $pdfcpuConfigRoot);
$_ENV['PDFCPU_CONFIG_ROOT'] = $pdfcpuConfigRoot;
