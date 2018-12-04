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
