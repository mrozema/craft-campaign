<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\controllers;

use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\elements\MailingListElement;
use putyourlightson\campaign\elements\SendoutElement;
use putyourlightson\campaign\records\LinkRecord;

use Craft;
use craft\errors\ElementNotFoundException;
use craft\web\Controller;
use craft\web\View;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * TrackerController
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0   
 */
class TrackerController extends Controller
{
    // Properties
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected $allowAnonymous = ['open', 'click', 'subscribe', 'unsubscribe', 'verify-email'];

    // Public Methods
    // =========================================================================

    /**
     * Open
     *
     * @return Response|null
     * @throws ElementNotFoundException
     * @throws Exception
     * @throws \Throwable
     */
    public function actionOpen()
    {
        // Get contact and sendout
        $contact = $this->_getContact();
        $sendout = $this->_getSendout();

        if ($contact AND $sendout) {
            // Track open
            Campaign::$plugin->tracker->open($contact, $sendout);
        }

        // Return tracking image
        $response = Craft::$app->getResponse();
        $response->getHeaders()->set('Content-Type', 'image/gif');
        $response->format = Response::FORMAT_RAW;
        $response->stream = fopen('@putyourlightson/campaign/resources/images/t.gif', 'rb');

        return $response->send();
    }

    /**
     * Click
     *
     * @return Response
     * @throws ElementNotFoundException
     * @throws Exception
     * @throws NotFoundHttpException
     * @throws \Throwable
     */
    public function actionClick(): Response
    {
        // Get contact, sendout and link
        $contact = $this->_getContact();
        $sendout = $this->_getSendout();
        $linkRecord = $this->_getLink();

        if ($linkRecord === null) {
            throw new NotFoundHttpException('Link not found.');
        }

        $url = $linkRecord->url;

        if ($contact AND $sendout) {
            // Track click
            Campaign::$plugin->tracker->click($contact, $sendout, $linkRecord);

            // If Google Analytics link tracking
            if ($sendout->googleAnalyticsLinkTracking) {
                $hasQuery = strpos($url, '?');
                $url .= $hasQuery === false ? '?' : '&';
                $url .= 'utm_source=campaign-plugin&utm_medium=email&utm_campaign='.$sendout->subject;
            }
        }

        // Redirect to URL
        return $this->redirect($url);
    }

    /**
     * Subscribe
     *
     * @return Response|null
     * @throws NotFoundHttpException
     * @throws \Throwable
     * @throws ElementNotFoundException
     * @throws Exception
     * @throws InvalidConfigException
     * @throws BadRequestHttpException
     */
    public function actionSubscribe()
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();

        // Get mailing list by slug
        $mailingListSlug = $request->getRequiredBodyParam('mailingList');
        /** @var MailingListElement $mailingList */
        $mailingList = MailingListElement::find()
            ->slug($mailingListSlug)
            ->one();

        if ($mailingList === null) {
            throw new NotFoundHttpException('Mailing list not found');
        }

        // If MLID is required
        if ($mailingList->mailingListType->requireMlid) {
            $mlid = $request->getBodyParam('mlid');

            // Get mailing list by MLID
            /** @var MailingListElement $mailingList */
            $mailingList = MailingListElement::find()
                ->slug($mailingListSlug)
                ->where(['mlid' => $mlid])
                ->one();

            if ($mailingList === null) {
                throw new NotFoundHttpException('Mailing list not found');
            }
        }

        // Check if contact with submitted email address exists
        $email = $request->getRequiredBodyParam('email');
        $contact = Campaign::$plugin->contacts->getContactByEmail($email);

        if ($contact === null) {
            $contact = new ContactElement();
            $contact->email = $email;
        }

        // Set the field layout ID
        $contact->fieldLayoutId = Campaign::$plugin->getSettings()->contactFieldLayoutId;

        // Set the field locations
        $fieldsLocation = $request->getParam('fieldsLocation', 'fields');
        $contact->setFieldValuesFromRequest($fieldsLocation);

        // If not double opt-in
        if (!$mailingList->mailingListType->doubleOptIn) {
            // Verify contact
            $contact->pending = false;
        }

        // Save it
        if (!Craft::$app->getElements()->saveElement($contact)) {
            if ($request->getAcceptsJson()) {
                return $this->asJson([
                    'errors' => $contact->getErrors(),
                ]);
            }

            // Send the contact back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'contact' => $contact
            ]);

            return null;
        }

        // Get referrer
        $referrer = $request->getReferrer();

        // If double opt-in
        if ($mailingList->mailingListType->doubleOptIn) {
            // Send verification email
            Campaign::$plugin->contacts->sendVerificationEmail($contact, $mailingList, $referrer);
        }
        else {
            // Track subscribe
            Campaign::$plugin->tracker->subscribe($contact, $mailingList, $mailingList->mailingListType->doubleOptIn, 'web', $referrer);
        }

        if ($request->getAcceptsJson()) {
            return $this->asJson(['success' => true]);
        }

        if ($request->getBodyParam('redirect')) {
            return $this->redirectToPostedUrl($contact);
        }

        // Get template
        $template = $mailingList !== null ? $mailingList->getMailingListType()->subscribeSuccessTemplate : '';

        // Use message template if none was defined
        if (empty($template)) {
            $template = 'campaign/message';

            // Set template mode to CP
            Craft::$app->getView()->setTemplateMode(View::TEMPLATE_MODE_CP);
        }

        return $this->renderTemplate($template, [
            'title' => 'Subscribed',
            'message' => $mailingList->mailingListType->doubleOptIn ? Craft::t('campaign', 'Thank you for subscribing to the mailing list. Please check your email for a confirmation link.') : Craft::t('campaign', 'You have successfully subscribed to the mailing list.'),
            'mailingList' => $mailingList,
        ]);
    }

    /**
     * Unsubscribe
     *
     * @return Response|null
     * @throws ElementNotFoundException
     * @throws Exception
     * @throws InvalidConfigException
     * @throws \Throwable
     */
    public function actionUnsubscribe()
    {
        // Get contact and sendout
        $contact = $this->_getContact();
        $sendout = $this->_getSendout();

        $mailingList = null;

        if ($contact AND $sendout) {
            // Track unsubscribe
            $mailingList = Campaign::$plugin->tracker->unsubscribe($contact, $sendout);
        }

        if (Craft::$app->getRequest()->getAcceptsJson()) {
            return $this->asJson(['success' => true]);
        }

        // Get template
        $template = $mailingList !== null ? $mailingList->getMailingListType()->unsubscribeSuccessTemplate : '';

        // Use message template if none was defined
        if (empty($template)) {
            $template = 'campaign/message';

            // Set template mode to CP
            Craft::$app->getView()->setTemplateMode(View::TEMPLATE_MODE_CP);
        }

        return $this->renderTemplate($template, [
            'title' => 'Unsubscribed',
            'message' => Craft::t('campaign', 'You have successfully unsubscribed from the mailing list.'),
            'mailingList' => $mailingList,
        ]);
    }

    /**
     * Verifies a contact's email
     *
     * @return Response
     * @throws ElementNotFoundException
     * @throws Exception
     * @throws NotFoundHttpException
     * @throws \Throwable
     */
    public function actionVerifyEmail(): Response
    {
        // Get contact and mailing list
        $contact = $this->_getContact();
        $mailingList = $this->_getMailingList();

        if ($mailingList === null) {
            throw new NotFoundHttpException('Mailing list not found');
        }

        // Get referrer
        $referrer = Craft::$app->getRequest()->getParam('referrer');

        // Track subscribe
        Campaign::$plugin->tracker->subscribe($contact, $mailingList, false, 'web', $referrer);

        // Use message template
        $template = 'campaign/message';

        // Set template mode to CP
        Craft::$app->getView()->setTemplateMode(View::TEMPLATE_MODE_CP);

        return $this->renderTemplate($template, [
            'title' => 'Verified',
            'message' => Craft::t('campaign', 'You have successfully verified your email address.'),
        ]);
    }

    // Private Methods
    // =========================================================================

    /**
     * Gets contact by CID in query param
     *
     * @return ContactElement|null
     */
    private function _getContact()
    {
        $cid = Craft::$app->getRequest()->getParam('cid');

        if ($cid === null) {
            return null;
        }

        return Campaign::$plugin->contacts->getContactByCid($cid);
    }

    /**
     * Gets sendout by SID in query param
     *
     * @return SendoutElement|null
     */
    private function _getSendout()
    {
        $sid = Craft::$app->getRequest()->getParam('sid');

        if ($sid === null) {
            return null;
        }

        return Campaign::$plugin->sendouts->getSendoutBySid($sid);
    }

    /**
     * Gets link by LID in query param
     *
     * @return LinkRecord|null
     */
    private function _getLink()
    {
        $lid = Craft::$app->getRequest()->getParam('lid');

        if ($lid === null) {
            return null;
        }

        return LinkRecord::findOne(['lid' => $lid]);
    }

    /**
     * Gets mailing list by MLID in query param
     *
     * @return MailingListElement|null
     */
    private function _getMailingList()
    {
        $mlid = Craft::$app->getRequest()->getParam('mlid');

        if ($mlid === null) {
            return null;
        }

        return Campaign::$plugin->mailingLists->getMailingListByMlid($mlid);
    }

}