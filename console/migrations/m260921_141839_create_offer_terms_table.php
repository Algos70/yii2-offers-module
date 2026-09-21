<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * Handles the creation of table `{{%offer_terms}}`.
 *
 * Bonus terms are a strict 1:1 extension of `{{%offer}}`: `offer_id` is both the
 * primary key and the foreign key, so an offer can have at most one terms row and
 * the row disappears with its offer.
 *
 * Has foreign keys to the tables:
 *
 * - `{{%offer}}`
 */
class m260921_141839_create_offer_terms_table extends Migration
{
    private const TABLE = '{{%offer_terms}}';

    public function safeUp(): void
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable(self::TABLE, [
            'offer_id' => $this->integer()->notNull(),
            // 35.0 is rendered as "35x"; NULL means the offer carries no wagering requirement.
            'wagering_multiplier' => $this->decimal(5, 1),
            // NULL for offers that need no deposit.
            'min_deposit' => $this->decimal(10, 2),
            'max_bonus' => $this->decimal(10, 2),
            'max_cashout' => $this->decimal(10, 2),
            // Days the player has to use the offer after claiming it.
            'valid_days' => $this->smallInteger(),
            'terms_url' => $this->string(255),
            'terms_note' => $this->string(500),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], $tableOptions);

        // Primary key and foreign key on the same column: 1:1 is unrepresentable otherwise.
        $this->addPrimaryKey('pk-offer_terms-offer_id', self::TABLE, 'offer_id');

        $this->addForeignKey(
            'fk-offer_terms-offer_id',
            self::TABLE,
            'offer_id',
            '{{%offer}}',
            'id',
            'CASCADE',
            'CASCADE',
        );

        // Ranges hold even if a write bypasses the model rules.
        $this->addCheck(
            'chk-offer_terms-wagering_multiplier',
            self::TABLE,
            '[[wagering_multiplier]] IS NULL OR ([[wagering_multiplier]] >= 0 AND [[wagering_multiplier]] <= 200)',
        );
        $this->addCheck(
            'chk-offer_terms-min_deposit',
            self::TABLE,
            '[[min_deposit]] IS NULL OR [[min_deposit]] >= 0',
        );
        $this->addCheck(
            'chk-offer_terms-max_bonus',
            self::TABLE,
            '[[max_bonus]] IS NULL OR [[max_bonus]] >= 0',
        );
        $this->addCheck(
            'chk-offer_terms-max_cashout',
            self::TABLE,
            '[[max_cashout]] IS NULL OR [[max_cashout]] >= 0',
        );
        $this->addCheck(
            'chk-offer_terms-valid_days',
            self::TABLE,
            '[[valid_days]] IS NULL OR ([[valid_days]] >= 1 AND [[valid_days]] <= 365)',
        );

        // Serves the admin list's "max wagering" filter and its sortable column.
        $this->createIndex('idx-offer_terms-wagering_multiplier', self::TABLE, 'wagering_multiplier');
    }

    public function safeDown(): void
    {
        $this->dropForeignKey('fk-offer_terms-offer_id', self::TABLE);
        $this->dropTable(self::TABLE);
    }
}
