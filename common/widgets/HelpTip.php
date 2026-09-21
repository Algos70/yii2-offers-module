<?php

declare(strict_types=1);

namespace common\widgets;

use common\assets\HelpTipAsset;
use Yii;
use yii\helpers\Html;
use yii\web\View;

/**
 * The "?" marker that explains a piece of bonus jargon.
 *
 * One glossary for both applications: a visitor reading a term tile and an
 * administrator filling the same field in a form get the same definition, so
 * the two can never drift apart.
 *
 * The text is carried in the `title` attribute, which Bootstrap picks up and
 * turns into a styled tooltip. With JavaScript off the browser's own tooltip
 * shows instead, so the explanation is never simply lost.
 */
final class HelpTip
{
    /**
     * Term key => definition. Wording is aimed at a player first: an admin who
     * knows the jargon loses nothing, a new one learns the domain.
     *
     * @var array<string, string>
     */
    private const TERMS = [
        // Offer shape
        'offer_type' => 'Welcome bonus: extra funds matched to a deposit you make. '
            . 'No deposit: a small bonus just for signing up, with nothing to pay in. '
            . 'Free spins: a number of spins on selected slots, sometimes tied to a deposit.',
        'welcome' => 'A deposit match: pay in, and the casino tops your balance up. '
            . 'It is the biggest offer a site usually runs, and it always states a minimum deposit.',
        'no_deposit' => 'Claimed without paying anything in - usually a small amount, given for '
            . 'signing up. It can still carry wagering and a withdrawal cap.',
        'free_spins' => 'A set number of spins on chosen slot games. Some are unlocked by a '
            . 'deposit, others are handed out with no deposit at all.',
        'amount' => 'What the offer is worth: a cash figure, or the number of spins for a '
            . 'free-spins offer.',

        // Bonus terms
        'wagering' => 'How many times the bonus must be staked before winnings can be withdrawn. '
            . 'At 35x, a EUR 20 bonus means EUR 700 wagered in total.',
        'min_deposit' => 'The smallest deposit that unlocks the offer. A no-deposit bonus never '
            . 'has one - that is what makes it a no-deposit bonus.',
        'max_bonus' => 'The largest bonus the offer will pay, no matter how much you deposit '
            . 'beyond that point.',
        'max_cashout' => 'The most you are allowed to withdraw from what the bonus wins, however '
            . 'much it actually wins.',
        'valid_days' => 'How long you have to use the bonus and finish its wagering. Miss the '
            . 'window and the bonus and anything won with it are forfeited.',
        'terms_url' => 'Link to the operator\'s full terms and conditions - the legally binding '
            . 'version of everything summarised here.',
        'terms_note' => 'Any restriction that does not fit the fields above: excluded games, '
            . 'a country limit, one claim per household.',

        // Admin filters
        'max_wagering_filter' => 'Shows only offers whose wagering is at most this number, so '
            . 'a low figure surfaces the offers that are genuinely easy to clear.',
    ];

    /**
     * The marker on its own, for placing beside text that is already rendered.
     */
    public static function for(string $term): string
    {
        $view = Yii::$app->getView();

        // Asset bundles need a web view; a console-rendered view (mail bodies)
        // has no asset manager, and there the plain markup is still correct.
        if ($view instanceof View) {
            HelpTipAsset::register($view);
        }

        return Html::tag(
            'span',
            '?',
            [
                'class' => 'help-tip',
                'title' => self::text($term),
                'data-bs-toggle' => 'tooltip',
                // Focusable so the definition is reachable without a mouse.
                'tabindex' => 0,
                'role' => 'note',
                'aria-label' => 'What this means: ' . self::text($term),
            ],
        );
    }

    /**
     * A label with the marker after it, for form fields and grid headers.
     */
    public static function label(string $label, string $term): string
    {
        return Html::encode($label) . ' ' . self::for($term);
    }

    public static function text(string $term): string
    {
        return self::TERMS[$term]
            ?? throw new \InvalidArgumentException("No help text is defined for \"$term\".");
    }
}
