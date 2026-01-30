<?php

declare(strict_types=1);

use App\Collection;

require __DIR__ . '/vendor/autoload.php';

$users = new Collection([
    [
        'name' => 'John',
        'active' => true,
        'profile' => ['age' => 25],
    ],
    [
        'name' => 'Anna',
        'active' => false,
        'profile' => ['age' => 17],
    ],
    [
        'name' => 'Sraka',
        'active' => true,
        'profile' => ['age' => 22],
    ],
]);

$result = $users
    ->where('active', '=', true)
    ->map(function ($user) {
        return $user['name'];
    })
    ->toArray();


dd($result);