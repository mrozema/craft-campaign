<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\models;

use craft\models\FieldLayout;
use craft\models\Site;
use craft\validators\SiteIdValidator;
use putyourlightson\campaign\base\BaseModel;
use putyourlightson\campaign\elements\MailingListElement;
use putyourlightson\campaign\records\MailingListTypeRecord;

use Craft;
use craft\behaviors\FieldLayoutBehavior;
use craft\helpers\UrlHelper;
use craft\validators\HandleValidator;
use craft\validators\UniqueValidator;

/**
 * MailingListTypeModel
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0
 *
 * @mixin FieldLayoutBehavior
 *
 * @property null|Site $site
 * @property FieldLayout $fieldLayout
 * @property string $cpEditUrl
 *
 * @method FieldLayout getFieldLayout()
 * @method setFieldLayout(FieldLayout $fieldLayout)
 */
class MailingListTypeModel extends BaseModel
{
    // Properties
    // =========================================================================

    /**
     * @var int|null ID
     */
    public $id;

    /**
     * @var int|null Site ID
     */
    public $siteId;

    /**
     * @var int|null Field layout ID
     */
    public $fieldLayoutId;

    /**
     * @var string|null Name
     */
    public $name;

    /**
     * @var string|null Handle
     */
    public $handle;

    /**
     * @var bool Subscribe verification required
     */
    public $subscribeVerificationRequired = true;

    /**
     * @var string|null Subscribe verification email subject
     */
    public $subscribeVerificationEmailSubject;

    /**
     * @var string|null Subscribe verification email template
     */
    public $subscribeVerificationEmailTemplate;

    /**
     * @var string|null Subscribe verification success template
     */
    public $subscribeVerificationSuccessTemplate;

    /**
     * @var string|null Subscribe success template
     */
    public $subscribeSuccessTemplate;

    /**
     * @var string|null Unsubscribe URL Override
     */
    public $unsubscribeUrlOverride;

    /**
     * @var bool Unsubscribe form allowed
     */
    public $unsubscribeFormAllowed = false;

    /**
     * @var string|null Unsubscribe verification email subject
     */
    public $unsubscribeVerificationEmailSubject;

    /**
     * @var string|null Unsubscribe verification email template
     */
    public $unsubscribeVerificationEmailTemplate;

    /**
     * @var string|null Unsubscribe success template
     */
    public $unsubscribeSuccessTemplate;

    /**
     * @var string|null UID
     */
    public $uid;

    // Public Methods
    // =========================================================================

    /**
     * Use the handle as the string representation.
     *
     * @return string
     */
    public function __toString(): string
    {
        return (string)$this->handle;
    }

    /**
     * @inheritdoc
     */
    public function behaviors(): array
    {
        return [
            'fieldLayout' => [
                'class' => FieldLayoutBehavior::class,
                'elementType' => MailingListElement::class
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['id', 'siteId', 'fieldLayoutId'], 'integer'],
            [['siteId'], SiteIdValidator::class],
            [['siteId', 'name', 'handle'], 'required'],
            [['name', 'handle'], 'string', 'max' => 255],
            [['name', 'handle'], UniqueValidator::class, 'targetClass' => MailingListTypeRecord::class],
            [['handle'], HandleValidator::class, 'reservedWords' => ['id', 'dateCreated', 'dateUpdated', 'uid', 'title']],
            [['subscribeVerificationRequired', 'unsubscribeFormAllowed'], 'boolean'],
        ];
    }

    /**
     * Returns the CP edit URL.
     *
     * @return string
     */
    public function getCpEditUrl(): string
    {
        return UrlHelper::cpUrl('campaign/settings/mailinglisttypes/'.$this->id);
    }

    /**
     * Returns the site.
     *
     * @return Site|null
     */
    public function getSite()
    {
        if ($this->siteId === null) {
            return Craft::$app->getSites()->getPrimarySite();
        }

        return Craft::$app->getSites()->getSiteById($this->siteId);
    }
}
