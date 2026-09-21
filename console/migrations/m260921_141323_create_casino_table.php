<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * Handles the creation of table `{{%casino}}`.
 */
class m260921_141323_create_casino_table extends Migration
{
    private const TABLE = '{{%casino}}';

    public function safeUp(): void
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable(self::TABLE, [
            'id' => $this->primaryKey(),
            'name' => $this->string(120)->notNull(),
            'slug' => $this->string(140)->notNull(),
            'rating' => $this->decimal(2, 1)->notNull()->defaultValue(0),
            'is_active' => $this->boolean()->notNull()->defaultValue(true),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], $tableOptions);

        $this->createIndex('idx-casino-slug', self::TABLE, 'slug', true);
        $this->createIndex('idx-casino-is_active', self::TABLE, 'is_active');

        // Keeps the rating inside its scale even if a write bypasses the model rules.
        $this->addCheck('chk-casino-rating', self::TABLE, '[[rating]] >= 0 AND [[rating]] <= 5');
    }

    public function safeDown(): void
    {
        $this->dropTable(self::TABLE);
    }
}
