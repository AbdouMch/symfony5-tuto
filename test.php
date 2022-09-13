<?php

require_once 'vendor/autoload.php';

$spells = [
    'alohomora',
    'confundo',
    'engorgio',
    'expecto patronum',
    'expelliarmus',
    'impedimenta',
    'reparo',
];

$rand = array_rand($spells);

dd($spells[array_rand($spells)]);
