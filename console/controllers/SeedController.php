<?php

declare(strict_types=1);

namespace console\controllers;

use common\enums\OfferStatus;
use common\enums\OfferType;
use common\models\Casino;
use common\models\Offer;
use common\models\OfferTerms;
use common\models\User;
use Yii;
use yii\base\Model;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Inflector;

/**
 * Development data.
 *
 * Both actions are idempotent: they skip records whose unique key already
 * exists, so running them twice changes nothing.
 */
class SeedController extends Controller
{
    /**
     * Offers generated per casino; see {@see templatesFor()}.
     */
    public const OFFERS_PER_CASINO = 10;

    /**
     * Fills the database with the demo catalogue: one batch of offers per
     * casino in {@see casinoDefinitions()}.
     *
     * The spread is deliberate: every type and status occurs, wagering values
     * straddle 35 so the "max wagering" filter shows a difference, some offers
     * never expire, some are already past their date, and a third of them carry
     * no terms row at all.
     *
     * Usage: php yii seed/offers
     */
    public function actionOffers(): int
    {
        $transaction = Yii::$app->db->beginTransaction();

        try {
            [$casinos, $casinosCreated] = $this->seedCasinos();
            $offersCreated = $this->seedOffers($casinos);
            $transaction->commit();
        } catch (\Throwable $exception) {
            $transaction->rollBack();

            throw $exception;
        }

        $this->stdout("$casinosCreated casinos, $offersCreated offers created.\n");
        $this->stdout(
            'Totals: ' . Casino::find()->count() . ' casinos, '
            . Offer::find()->count() . ' offers, '
            . OfferTerms::find()->count() . " terms rows.\n",
        );

        return ExitCode::OK;
    }

    /**
     * Creates one activated administrator, because the backend is behind a login.
     *
     * Usage: php yii seed/admin <username> <email> <password>
     */
    public function actionAdmin(string $username, string $email, string $password): int
    {
        if (User::find()->where(['username' => $username])->orWhere(['email' => $email])->exists()) {
            $this->stderr("User \"$username\" (or e-mail \"$email\") already exists.\n");

            return ExitCode::DATAERR;
        }

        $user = new User();
        $user->username = $username;
        $user->email = $email;
        $user->status = User::STATUS_ACTIVE;
        $user->setPassword($password);
        $user->generateAuthKey();

        if (!$user->save()) {
            $this->stderr('Could not save user: ' . print_r($user->getErrors(), true) . "\n");

            return ExitCode::UNSPECIFIED_ERROR;
        }

        $this->stdout("Created user #{$user->id} \"$username\".\n");

        return ExitCode::OK;
    }

    /**
     * The demo operators: name, rating and whether they are active.
     *
     * One of them is inactive on purpose — an inactive casino and its offers
     * must stay off the public site while remaining editable in the admin.
     *
     * @return list<array{0: string, 1: string, 2: bool}>
     */
    private function casinoDefinitions(): array
    {
        return [
            ['Neon Palace', '4.7', true],
            ['Golden Reels', '4.2', true],
            ['Silver Spin', '3.8', false],
            ['Royal Flush Club', '4.5', true],
            ['Midnight Jackpot', '3.9', true],
            ['Aurora Bay Casino', '4.4', true],
        ];
    }

    /**
     * @return array{0: list<Casino>, 1: int} the casinos and how many were new
     */
    private function seedCasinos(): array
    {
        $definitions = $this->casinoDefinitions();

        $casinos = [];
        $created = 0;

        foreach ($definitions as [$name, $rating, $isActive]) {
            $slug = $this->slugify($name);
            $casino = Casino::findOne(['slug' => $slug]);

            if ($casino === null) {
                $casino = new Casino(['name' => $name, 'rating' => $rating, 'is_active' => $isActive]);

                if (!$casino->save()) {
                    throw new \RuntimeException(
                        "Casino \"$name\" failed validation: " . print_r($casino->getErrors(), true),
                    );
                }

                $created++;
            }

            $casinos[] = $casino;
        }

        return [$casinos, $created];
    }

    /**
     * @param list<Casino> $casinos
     * @return int how many offers were created
     */
    private function seedOffers(array $casinos): int
    {
        $created = 0;

        foreach ($this->offerDefinitions($casinos) as $definition) {
            [$offerAttributes, $termsAttributes, $backdatedExpiry] = $definition;

            if (Offer::find()->where(['slug' => $this->slugify($offerAttributes['title'])])->exists()) {
                continue;
            }

            $offer = new Offer($offerAttributes);
            $terms = new OfferTerms($termsAttributes);
            $terms->offerType = $offer->type;

            // Seed data goes through the same rules as admin input, so it can
            // never drift away from what the application considers valid.
            if (!Model::validateMultiple([$offer, $terms])) {
                throw new \RuntimeException(
                    "Offer \"{$offerAttributes['title']}\" failed validation: "
                    . print_r($offer->getErrors() + $terms->getErrors(), true),
                );
            }

            if (!$offer->saveWithTerms($terms)) {
                throw new \RuntimeException("Offer \"{$offerAttributes['title']}\" could not be saved.");
            }

            if ($backdatedExpiry !== null) {
                // An offer whose expiry already passed cannot be *created* —
                // the rules forbid entering a past date, on purpose. Such a row
                // only exists because time moved on after publication, so the
                // seed reproduces that by writing the date afterwards instead
                // of relaxing the validator.
                $offer->updateAttributes(['expires_at' => $backdatedExpiry]);
            }

            $created++;
        }

        return $created;
    }

    /**
     * {@see OFFERS_PER_CASINO} offers per casino, every type and status
     * represented across the catalogue.
     *
     * @param list<Casino> $casinos
     * @return list<array{0: array<string, mixed>, 1: array<string, mixed>, 2: string|null}>
     */
    private function offerDefinitions(array $casinos): array
    {
        $at = static fn (string $modifier): string => date('Y-m-d H:i:s', strtotime($modifier));

        $definitions = [];

        foreach ($casinos as $casino) {
            foreach ($this->templatesFor($casino->name) as $template) {
                [$title, $type, $amount, $status, $expiry, $terms] = $template;

                $definitions[] = [
                    [
                        'casino_id' => $casino->id,
                        'title' => $title,
                        'type' => $type,
                        'amount' => $amount,
                        'status' => $status,
                        // A lapsed offer is inserted without a date and
                        // backdated below; see seedOffers().
                        'expires_at' => match ($expiry) {
                            'never', 'gone' => null,
                            'urgent' => $at('+4 days'),
                            'soon' => $at('+10 days'),
                            'later' => $at('+6 months'),
                            default => throw new \LogicException("Unknown expiry marker \"$expiry\"."),
                        },
                    ],
                    $terms,
                    $expiry === 'gone' ? $at('-2 months') : null,
                ];
            }
        }

        return $definitions;
    }

    /**
     * Ten offers per casino, shaped so each type carries terms that make sense
     * for it: welcome bonuses state a minimum deposit, no-deposit offers must
     * not, free spins get a short validity window.
     *
     * @return list<array{0: string, 1: string, 2: string, 3: string, 4: string, 5: array<string, mixed>}>
     */
    private function templatesFor(string $casinoName): array
    {
        return [
            [
                "$casinoName Welcome Package",
                OfferType::Welcome->value,
                '200.00',
                OfferStatus::Active->value,
                'never',
                [
                    'wagering_multiplier' => '35.0',
                    'min_deposit' => '20.00',
                    'max_bonus' => '200.00',
                    'max_cashout' => '2000.00',
                    'valid_days' => 30,
                    'terms_url' => 'https://example.com/terms/welcome',
                    'terms_note' => 'Slots only. 18+.',
                ],
            ],
            [
                "$casinoName First Deposit Boost",
                OfferType::Welcome->value,
                '150.00',
                OfferStatus::Active->value,
                'later',
                [
                    'wagering_multiplier' => '45.0',
                    'min_deposit' => '50.00',
                    'max_bonus' => '150.00',
                    'max_cashout' => '1500.00',
                    'valid_days' => 14,
                ],
            ],
            [
                // Spins tied to a qualifying deposit, which is how most free-spin
                // offers actually work. The other free-spins template below is
                // claimable without paying in, so both shapes are in the data.
                "$casinoName Free Spins Friday",
                OfferType::FreeSpins->value,
                '100.00',
                OfferStatus::Active->value,
                'soon',
                [
                    'wagering_multiplier' => '25.0',
                    'min_deposit' => '20.00',
                    'max_cashout' => '100.00',
                    'valid_days' => 7,
                    'terms_note' => 'Spins credited over five days.',
                ],
            ],
            [
                "$casinoName No Deposit Starter",
                OfferType::NoDeposit->value,
                '10.00',
                OfferStatus::Active->value,
                'later',
                [
                    'wagering_multiplier' => '60.0',
                    'max_cashout' => '50.00',
                    'valid_days' => 3,
                ],
            ],
            [
                "$casinoName Summer Special",
                OfferType::FreeSpins->value,
                '75.00',
                OfferStatus::Expired->value,
                'gone',
                [],
            ],
            [
                "$casinoName Upcoming VIP Reload",
                OfferType::Welcome->value,
                '500.00',
                OfferStatus::Draft->value,
                'never',
                [],
            ],
            [
                // Expiry inside the warning window, so the public card shows
                // its red "Ends in N days" state.
                "$casinoName Midweek Reload",
                OfferType::Welcome->value,
                '75.00',
                OfferStatus::Active->value,
                'urgent',
                [
                    'wagering_multiplier' => '30.0',
                    'min_deposit' => '10.00',
                    'max_bonus' => '75.00',
                    'valid_days' => 5,
                ],
            ],
            [
                "$casinoName Weekend Free Spins",
                OfferType::FreeSpins->value,
                '25.00',
                OfferStatus::Active->value,
                'never',
                [
                    'wagering_multiplier' => '20.0',
                ],
            ],
            [
                "$casinoName High Roller Welcome",
                OfferType::Welcome->value,
                '2000.00',
                OfferStatus::Active->value,
                'later',
                [
                    'wagering_multiplier' => '40.0',
                    'min_deposit' => '500.00',
                    'max_bonus' => '2000.00',
                    'max_cashout' => '10000.00',
                    'valid_days' => 60,
                    'terms_url' => 'https://example.com/terms/high-roller',
                    'terms_note' => "Table games excluded.\nOne claim per household.",
                ],
            ],
            [
                // No terms row at all: the card falls back to "No bonus terms
                // published." and the detail page says the same.
                "$casinoName Loyalty No Deposit",
                OfferType::NoDeposit->value,
                '5.00',
                OfferStatus::Active->value,
                'soon',
                [],
            ],
        ];
    }

    private function slugify(string $value): string
    {
        return Inflector::slug($value);
    }
}
