<?php

return [
    'cutoff' => env('CANTINE_CUTOFF', '10:00'),
    'trash_days' => (int) env('CANTINE_TRASH_DAYS', 30),
    'payment_env' => env('PAYMENT_ENV', 'test'),
    'upload_max_kb' => (int) env('UPLOAD_MAX_KB', 5120),
    'demo' => (bool) env('CANTINE_DEMO', false),
    'demo_now' => env('CANTINE_DEMO_NOW'),

    'allergens' => [
        'arachide' => 'Arachide', 'gluten' => 'Gluten', 'lactose' => 'Lactose',
        'fruits-de-mer' => 'Fruits de mer', 'poisson' => 'Poisson', 'oeuf' => 'Œuf',
    ],
    'diets' => ['vegetarien' => 'Végétarien', 'sans-porc' => 'Sans porc'],
    'categories' => ['entree' => 'Entrée', 'plat' => 'Plat', 'dessert' => 'Dessert', 'boisson' => 'Boisson'],
    'category_icons' => ['entree' => 'salad', 'plat' => 'utensils', 'dessert' => 'cake', 'boisson' => 'cup'],
    'classes' => ['6e A', '6e B', '5e A', '5e B', '4e A', '4e B', '3e A', '3e B', '2nde C', '1re D', 'Tle A', 'CM2'],
    'slots' => ['11:30' => 40, '12:00' => 50, '12:30' => 50, '13:00' => 40],
    'max_quantity' => 5,
    'forbidden_words' => ['idiot', 'idiots', 'imbécile', 'imbéciles', 'arnaque', 'bit.ly', 'http', 'www', 'cliquez', 'gagnez', 'connard'],
];
