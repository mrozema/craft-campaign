<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\records;

use craft\db\SoftDeleteTrait;
use putyourlightson\campaign\base\BaseActiveRecord;
use yii\db\ActiveQuery;
use craft\db\Table;

/**
 * PendingTransactionalSendoutRecord
 *
 * @property int         $id                         ID
 * @property string      $pid                        Pending ID
 * @property int         $contactId                  Contact ID
 * @property int         $sendoutId                  Sendout ID
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0
 */
class PendingTransactionalSendoutRecord extends BaseActiveRecord
{
    use SoftDeleteTrait;

    // Public Static Methods
    // =========================================================================

    /**
     * @inheritdoc
     *
     * @return string the table name
     */
    public static function tableName(): string
    {
        return '{{%campaign_pendingtransactionalsendouts}}';
    }

    /**
     * @inheritdoc
     */
    public static function find(): ActiveQuery
    {
        return parent::find();
    }
}
