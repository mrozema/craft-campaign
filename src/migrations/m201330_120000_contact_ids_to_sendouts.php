<?php

namespace putyourlightson\campaign\migrations;

use craft\db\Migration;

/**
 * m201330_120000_contact_ids_to_sendouts migration.
 */
class m201330_120000_contact_ids_to_sendouts extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        if (!$this->db->columnExists('{{%campaign_sendouts}}', 'contactIds')) {
            $this->addColumn('{{%campaign_sendouts}}', 'contactIds', $this->text()->after('segmentIds'));
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        if (!$this->db->columnExists('{{%campaign_sendouts}}', 'contactIds')) {
            $this->dropColumn('{{%campaign_sendouts}}', 'contactIds');
        }

        return true;
    }
}
