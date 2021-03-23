<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\models;

use putyourlightson\campaign\base\BaseModel;
use putyourlightson\campaign\helpers\StringHelper;

/**
 * PendingTransactionalSendoutModel
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0
 */
class PendingTransactionalSendoutModel extends BaseModel
{
    // Properties
    // =========================================================================

    /**
     * @var string Pending ID
     */
    public $pid;

    /**
     * @var int contactId
     */
    public $contactId;

    /**
     * @var int sendoutId
     */
    public $sendoutId;


    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if ($this->pid === null) {
            $this->pid = StringHelper::uniqueId('p');
        }
    }

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['pid', 'contactId', 'sendoutId' ], 'required'],
            [['pid'], 'string', 'max' => 32],
        ];
    }
}
