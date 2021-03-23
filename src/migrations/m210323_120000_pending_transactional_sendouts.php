<?php

namespace putyourlightson\campaign\migrations;

use craft\db\Migration;

class m210323_120000_pending_transactional_sendouts extends Migration
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->createTable('{{%campaign_pendingtransactionalsendouts}}', [
            'id' => $this->primaryKey(),
            'pid' => $this->uid(),
            'contactId' => $this->integer()->notNull(),
            'sendoutId' => $this->integer()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->createIndex(null, '{{%campaign_pendingtransactionalsendouts}}', 'pid', true);

        $this->addForeignKey(null, '{{%campaign_pendingtransactionalsendouts}}', 'sendoutId', '{{%campaign_sendouts}}', 'id', 'CASCADE');
        $this->addForeignKey(null, '{{%campaign_pendingtransactionalsendouts}}', 'contactId', '{{%campaign_contacts}}', 'id', 'CASCADE');

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        if ($this->db->tableExists('{{%campaign_pendingtransactionalsendouts}}')) {
            $this->dropTable('{{%campaign_pendingtransactionalsendouts}}');
        }
        return true;
    }
}
