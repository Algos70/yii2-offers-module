<?php

declare(strict_types=1);

return [
    // Offer 1 is a welcome bonus: it carries a minimum deposit.
    'welcomeTerms' => [
        'offer_id' => 1,
        'wagering_multiplier' => '35.0',
        'min_deposit' => '20.00',
        'max_bonus' => '500.00',
        'max_cashout' => '1000.00',
        'valid_days' => 30,
        'terms_url' => 'https://example.com/terms/welcome',
        'terms_note' => 'Slots only.',
        'created_at' => 1700000000,
        'updated_at' => 1700000000,
    ],
    // Offer 2 is free spins: no deposit figure, higher wagering.
    'freeSpinsTerms' => [
        'offer_id' => 2,
        'wagering_multiplier' => '45.0',
        'min_deposit' => null,
        'max_bonus' => null,
        'max_cashout' => '100.00',
        'valid_days' => 7,
        'terms_url' => null,
        'terms_note' => null,
        'created_at' => 1700000000,
        'updated_at' => 1700000000,
    ],
    // Offers 3, 4 and 5 deliberately have no terms row: the relation is optional.
];
