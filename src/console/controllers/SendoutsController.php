<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\console\controllers;

use Craft;
use craft\helpers\Console;
use putyourlightson\campaign\Campaign;
use Throwable;
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
     * Runs pending sendouts.
     *
     * @return int
     * @throws Throwable
     */
    public function actionRunPendingSendouts(string $arg = 'run'): int
    {
        if ($arg == 'run') {
            $count = Campaign::$plugin->sendouts->queuePendingSendouts();
            Craft::$app->getQueue()->run();
            $this->stdout(Craft::t('campaign', 'Running {count} pending sendout(s)', ['count' => $count]).PHP_EOL, Console::FG_GREEN);
        } elseif ($arg == 'queue-only') {
            $count = Campaign::$plugin->sendouts->queuePendingSendouts();
            $this->stdout(Craft::t('campaign', '{count} pending sendout(s) queued.', ['count' => $count]).PHP_EOL, Console::FG_GREEN);
        } else {
            $this->stdout(Craft::t('campaign', 'Invalid argument: {arg}', ['arg' => $arg]).PHP_EOL, Console::FG_RED);
            return ExitCode::USAGE;
        }

        return ExitCode::OK;
    }
}
