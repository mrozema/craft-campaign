<?php

namespace putyourlightson\campaign\migrations;

use craft\db\Migration;

/**
 * m201228_120000_add_unsubscribe_url_override migration.
 */
class m201228_120000_add_unsubscribe_url_override extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        if (!$this->db->columnExists('{{%campaign_mailinglisttypes}}', 'unsubscribeUrlOverride')) {
            $this->addColumn('{{%campaign_mailinglisttypes}}', 'unsubscribeUrlOverride', $this->text()->after('unsubscribeSuccessTemplate'));
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        if (!$this->db->columnExists('{{%campaign_mailinglisttypes}}', 'unsubscribeUrlOverride')) {
            $this->dropColumn('{{%campaign_mailinglisttypes}}', 'unsubscribeUrlOverride');
        }

        return true;
    }
}
