<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%connections}}`.
 */
class m240811_182057_create_connections_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%connections}}', [
            'id' => $this->primaryKey(),
            'token' => $this->string()->notNull(),
            'user_id' => $this->integer()->notNull(),
            'user_agent' => $this->string(),
            'active' => $this->boolean()->notNull(),
            'opened_at' => $this->dateTime()->notNull(),
            'closed_at' => $this->dateTime(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%connections}}');
    }
}
