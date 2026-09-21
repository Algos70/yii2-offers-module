<?php

declare(strict_types=1);

// Relative dates keep the fixtures meaningful whenever the suite is run.
$future = date('Y-m-d H:i:s', strtotime('+1 year'));
$past = date('Y-m-d H:i:s', strtotime('-1 year'));

return [
    'visibleNoExpiry' => [
        'id' => 1,
        'casino_id' => 1,
        'title' => 'Visible Welcome Bonus',
        'slug' => 'visible-welcome-bonus',
        'type' => 'welcome',
        'amount' => '100.00',
        'expires_at' => null,
        'status' => 'active',
        'created_at' => 1700000000,
        'updated_at' => 1700000000,
    ],
    'visibleFutureExpiry' => [
        'id' => 2,
        'casino_id' => 1,
        'title' => 'Visible Free Spins',
        'slug' => 'visible-free-spins',
        'type' => 'free_spins',
        'amount' => '50.00',
        'expires_at' => $future,
        'status' => 'active',
        'created_at' => 1700000000,
        'updated_at' => 1700000000,
    ],
    'draft' => [
        'id' => 3,
        'casino_id' => 1,
        'title' => 'Draft No Deposit',
        'slug' => 'draft-no-deposit',
        'type' => 'no_deposit',
        'amount' => '10.00',
        'expires_at' => null,
        'status' => 'draft',
        'created_at' => 1700000000,
        'updated_at' => 1700000000,
    ],
    'expiredStatus' => [
        'id' => 4,
        'casino_id' => 2,
        'title' => 'Expired Welcome Bonus',
        'slug' => 'expired-welcome-bonus',
        'type' => 'welcome',
        'amount' => '200.00',
        'expires_at' => $past,
        'status' => 'expired',
        'created_at' => 1700000000,
        'updated_at' => 1700000000,
    ],
    // Written straight to the table on purpose: the model rules forbid this
    // combination, so it can only appear through a stale row whose expiry
    // lapsed after publication. The notExpired() scope has to hide it.
    'activeButLapsed' => [
        'id' => 5,
        'casino_id' => 2,
        'title' => 'Lapsed Free Spins',
        'slug' => 'lapsed-free-spins',
        'type' => 'free_spins',
        'amount' => '25.00',
        'expires_at' => $past,
        'status' => 'active',
        'created_at' => 1700000000,
        'updated_at' => 1700000000,
    ],
];
