<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\console\controllers;

use Craft;
use craft\helpers\Console;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\models\PendingTransactionalSendoutModel;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * Allows you to run pending sendouts.
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.3.0
 */
class SendoutsController extends Controller
{
    // Public Methods
    // =========================================================================
    /**
     * Queues pending sendouts.
     *
     * @return int
     */
    public function actionQueue(): int
    {
        $count = Campaign::$plugin->sendouts->queuePendingSendouts();

        $this->stdout(Craft::t('campaign', '{count} pending sendout(s) queued.', ['count' => $count]).PHP_EOL, Console::FG_GREEN);

        return ExitCode::OK;
    }

    /**
     * Runs pending sendouts.
     *
     * @return int
     */
    public function actionRun(): int
    {
        $this->actionQueue();

        Craft::$app->getQueue()->run();

        return ExitCode::OK;
    }

    public function actionAddContactToTransactionalSendout(string $email, int $sendoutId): int
    {
        $contact = Campaign::$plugin->contacts->getContactByEmail($email);

        if (!$contact) {
            $this->stderr(Craft::t('campaign', '{email} is not a contact.', ['email' => $email]).PHP_EOL, Console::FG_RED);
            return ExitCode::DATAERR;
        }

        $sendout = Campaign::$plugin->sendouts->getSendoutById($sendoutId);

        if (!$sendout) {
            $this->stderr(Craft::t('campaign', '{sendoutId} is not a valid sendout ID.', ['sendoutId' => $sendoutId]).PHP_EOL, Console::FG_RED);
        }

        $transactionalSendout = new PendingTransactionalSendoutModel();
        $transactionalSendout->contactId = $contact->id;
        $transactionalSendout->sendoutId = $sendout->id;
        
        if (!Campaign::$plugin->sendouts->savePendingTransactionalSendout($transactionalSendout)) {
            return ExitCode::DATAERR;
        }

        $this->stdout(Craft::t('campaign', 'Pending sendout created for {email} on {title}.', ['email' => $email, 'title' => $sendout->title]).PHP_EOL, Console::FG_GREEN);
        return ExitCode::OK;
    }

    /**
     * Runs pending sendouts (deprecated).
     *
     * @return int
     * @deprecated in 1.18.1. Use [[campaign/sendouts/run]] instead.
     */
    public function actionRunPendingSendouts(): int
    {
        Craft::$app->getDeprecator()->log('campaign/sendouts/run-pending-sendouts', 'The “campaign/sendouts/run-pending-sendouts” console command has been deprecated. Use “campaign/sendouts/run” instead.');

        $this->actionRun();

        return ExitCode::OK;
    }
}
