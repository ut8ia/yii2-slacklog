<?php

namespace ut8ia\slacklog;


use yii;
use yii\helpers\Json;
use yii\helpers\VarDumper;
use yii\log\Logger;
use yii\log\Target;
use yii\web\Request;
use yii\httpclient\Client;

class SlackTarget extends Target
{
    /**
     * @var string
     */
    public $emoji = ":ghost:";

    /**
     * @var Client
     */
    public $httpclient;

    /**
     * @var string
     */
    public $defaultText = 'Some text for slack';

    /**
     * @var string
     * Web hook url.
     */
    public $urlWebHook = "";

    /**
     * @var string Name for bot.
     */
    public $botName = "ErrorBot";

    /**
     * @var bool write to slack.
     */
    public $enabled = true;

    public function init()
    {
        parent::init();
        $this->httpclient = new Client();
    }

    /**
     * Exports log [[messages]] to a specific destination.
     * Child classes must implement this method.
     */
    public function export()
    {
        if ($this->enabled)
            $this->send("Log message", $this->emoji, $this->getAttachments());
    }

    /**
     * Formats a log message for display as a string.
     * @param array $message the log message to be formatted.
     * The message structure follows that in [[Logger::messages]].
     * @return string the formatted message
     */
    public function formatMessage($message)
    {
        list($text, $level, $category, $timestamp) = $message;
        $level = Logger::getLevelName($level);
        if (!is_string($text)) {
            if ($text instanceof \Throwable || $text instanceof \Exception) {
                $text = (string)$text;
            } else {
                $text = VarDumper::export($text);
            }
        }
        $traces = [];
        if (isset($message[4])) {
            foreach ($message[4] as $trace) {
                $traces[] = "in {$trace['file']}:{$trace['line']}";
            }
        }

        $prefix = $this->getMessagePrefix($message);

        $formatDateTime = 'Y-m-d H:i:s';
        $timeLocal = 'Local : ' . date($formatDateTime, time()) . "\n";
        $timeUTC = 'UTC : ' . gmdate($formatDateTime, time()) . "\n";
        $dateTime = new \DateTime('now', new \DateTimeZone("Europe/Kiev"));
        $timeKiev = 'Kiev : ' . date_format($dateTime, $formatDateTime) . "\n";

        $text = ucfirst($text);

        return "{$timeLocal}{$timeKiev}{$timeUTC}$text{$prefix}[$level][$category]"
        . (empty($traces) ? '' : "\n    " . implode("\n    ", $traces));
    }


    /**
     * @param null $text
     * @param null $icon
     * @param array $attachments
     */
    public function send($text = null, $icon = null, $attachments = [])
    {
        $this->httpclient->post($this->urlWebHook, [
            'payload' => Json::encode($this->getPayload($text, $icon, $attachments)),
        ])->send();
    }

    /**
     * @param null $text
     * @param null $icon
     * @param array $attachments
     * @return array
     */
    protected function getPayload($text = null, $icon = null, $attachments = [])
    {
        if ($text === null) {
            $text = $this->defaultText;
        }

        $payload = [
            'text' => $text,
            'username' => $this->botName,
            'attachments' => $attachments,
        ];
        if ($icon !== null) {
            $payload['icon_emoji'] = $icon;
        }
        return $payload;
    }

    /**
     * @return array
     */
    public function getAttachments()
    {
        $attachments = [];
        foreach ($this->messages as $ind => $message) {
            $attachment = [
                'fallback' => 'Error ' . ($ind + 1),
                'pretext' => $message[0],
                'color' => $this->getLevelColor($message[1]),
                'text' => $this->formatMessage($message),
                'fields' => [
                    [
                        'title' => 'Application ID',
                        'value' => Yii::$app->id,
                        'short' => true,
                    ]
                ],
            ];
            if (Yii::$app->has('request') && (($request = Yii::$app->request) instanceof Request)) {
                $attachment['fields'][] = [
                    'title' => 'Referrer',
                    'value' => $request->getReferrer(),
                    'short' => true,
                ];
                $attachment['fields'][] = [
                    'title' => 'User IP',
                    'value' => $request->getUserIP(),
                    'short' => true,
                ];
                $attachment['fields'][] = [
                    'title' => 'URL',
                    'value' => $request->getAbsoluteUrl(),
                    'short' => true,
                ];
            }
            $attachments[] = $attachment;
        }
        return $attachments;
    }

    /**
     * @param $level
     * @return mixed|string
     */
    public function getLevelColor($level)
    {
        $colors = [
            Logger::LEVEL_ERROR => 'danger',
            Logger::LEVEL_WARNING => 'danger',
            Logger::LEVEL_INFO => 'good',
            Logger::LEVEL_PROFILE => 'warning',
            Logger::LEVEL_TRACE => 'warning',
        ];
        if (!isset($colors[$level])) {
            return 'good';
        }
        return $colors[$level];
    }
}
