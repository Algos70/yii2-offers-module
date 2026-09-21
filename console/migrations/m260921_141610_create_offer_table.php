<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * Handles the creation of table `{{%offer}}`.
 *
 * Has foreign keys to the tables:
 *
 * - `{{%casino}}`
 */
class m260921_141610_create_offer_table extends Migration
{
    private const TABLE = '{{%offer}}';

    public function safeUp(): void
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable(self::TABLE, [
            'id' => $this->primaryKey(),
            'casino_id' => $this->integer()->notNull(),
            'title' => $this->string(160)->notNull(),
            'slug' => $this->string(180)->notNull(),
            // The enum is mirrored by common\enums\OfferType, which feeds the model's "in" rule.
            'type' => "ENUM('welcome','no_deposit','free_spins') NOT NULL",
            // Currency amount for deposit offers, number of spins for free_spins offers.
            'amount' => $this->decimal(12, 2)->notNull(),
            // NULL means the offer never expires.
            'expires_at' => $this->dateTime(),
            // Mirrored by common\enums\OfferStatus.
            'status' => "ENUM('draft','active','expired') NOT NULL DEFAULT 'draft'",
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], $tableOptions);

        $this->createIndex('idx-offer-slug', self::TABLE, 'slug', true);
        $this->createIndex('idx-offer-casino_id', self::TABLE, 'casino_id');
        $this->createIndex('idx-offer-type', self::TABLE, 'type');
        // Serves the public listing predicate: status = 'active' AND (expires_at IS NULL OR expires_at > NOW()).
        $this->createIndex('idx-offer-status-expires_at', self::TABLE, ['status', 'expires_at']);

        $this->addCheck('chk-offer-amount', self::TABLE, '[[amount]] >= 0');

        // An offer cannot outlive its casino.
        $this->addForeignKey(
            'fk-offer-casino_id',
            self::TABLE,
            'casino_id',
            '{{%casino}}',
            'id',
            'CASCADE',
            'CASCADE',
        );
    }

    public function safeDown(): void
    {
        $this->dropForeignKey('fk-offer-casino_id', self::TABLE);
        $this->dropTable(self::TABLE);
    }
}
