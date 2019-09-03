<?php
/**
 * HerokuHC/Runner
 *
 * @author sameyasu
 * @version 0.1
 * @copyright 2019 sameyasu
 * @package HerokuHC
 * @license MIT
 **/
namespace HerokuHC;

use GuzzleHttp\Client;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\RequestException;

/**
 * Runner Class
 **/
final class Runner
{
    use MonologLoggerTrait;

    private $httpClient;

    private $runningHours;

    /**
     * Constructor
     **/
    private function __construct()
    {
        $this->initLogger();
        $this->initHttpClient();
    }

    /**
     * Starting runner
     * @return void
     */
    public static function start() : void
    {
        require_once __DIR__ . '/../vendor/autoload.php';

        $me = new self();
        $me->run();
    }

    /**
     * Running
     */
    public function run() : void
    {
        $url = getenv('HC_URL');
        if (preg_match('|\Ahttps?://|', $url, $matches) !== 1) {
            $this->logger->warning('Invalid URL', ['url' => $url]);
            return;
        }

        $interval = getenv('INTERVAL') ? getenv('INTERVAL') : 10 * 60;
        if (preg_match('/\A(?<min>[0-9]+)-(?<max>[0-9]+)\z/', $interval, $matches) === 1) {
            $minInterval = $matches['min'];
            $maxInterval = $matches['max'];
        } elseif (!is_numeric($interval)) {
            $this->logger->warning('Invalid Interval', ['interval' => $interval]);
            return;
        } else {
            $minInterval = $interval;
            $maxInterval = $interval;
        }

        $hours = getenv('HOURS') ? getenv('HOURS') : '0-23';
        $this->runningHours = $this->calculateRunningHours($hours);

        $this->logger->info(
            'Started',
            [
                'URL' => $url,
                'interval' => $interval,
                'hours' => $this->runningHours,
            ]
        );

        while (true) {
            if ($this->isRunningTime(time())) {
                $result = $this->checkUrl($url);
                $this->logger->info('Checked', ['result' => $result]);
            } else {
                $this->logger->info('Skipped (not running time)');
            }

            $intervalInSec = $this->getInterval($minInterval, $maxInterval);
            $nextRuns = date('Y-m-d H:i:sP', time() + $intervalInSec);
            $this->logger->debug('Interval', ['seconds' => $intervalInSec, 'next' => $nextRuns]);
            sleep($intervalInSec);
        }
    }

    private function checkUrl(string $url) : array
    {
        $result = [
            'ok' => false
        ];
        $this->httpClient->requestAsync('GET', $url, [
            'on_stats' => function ($stats) use (&$result) {
                $result['transfer_time'] = $stats->getTransferTime();
            }
        ])
        ->then(
            function (ResponseInterface $response) use (&$result) {
                $result['status_code'] = $response->getStatusCode();
                if ($response->getStatusCode() === 200) {
                    $result['body'] = $response->getBody()->getContents();
                    $result['ok'] = true;
                } else {
                    $this->logger->notice('Invalid StatusCode', [
                        'statusCode' => $response->getStatusCode()
                    ]);
                }
            },
            function (RequestException $exception) {
                $this->logger->notice('Rejected', [
                    'exception' => $exception
                ]);
            }
        )
        ->wait();

        return $result;
    }

    private function initHttpClient() : void
    {
        $this->httpClient = new Client([
            'timeout'  => 10.0,
        ]);
    }

    /**
     * Calculate interval in seconds
     * @param int $min
     * @param int $max
     * @return int
     */
    private function getInterval(int $min, int $max) : int
    {
        if ($min === $max || $max < $min) {
            return $min;
        }

        return mt_rand($min, $max);
    }

    /**
     * Calculate running hours
     * @param string $hours
     * @return array Map of hours
     */
    private function calculateRunningHours(string $hours) : array
    {
        $rangeHours = array_map(
            function ($h) {
                if (preg_match('/\A(?<start>[0-9]+)-(?<end>[0-9]+)\z/', $h, $matches) === 1) {
                    if ($matches['start'] <= $matches['end'] && $matches['start'] >= 0 && $matches['end'] <= 23) {
                        return range($matches['start'], $matches['end']);
                    } else {
                        $this->logger->notice(
                            'Invalid range',
                            ['range' => $h, 'start' => $matches['start'], 'end' => $matches['end']]
                        );
                        return [];
                    }
                } elseif (is_numeric($h)) {
                    return [intval($h)];
                } else {
                    $this->logger->notice('Invalid hour', ['hour' => $h]);
                    return [];
                }
            },
            explode(',', $hours)
        );

        $runningHours = array_reduce(
            $rangeHours,
            function ($carry, $item) {
                if (is_array($item)) {
                    $carry = array_merge($carry, array_values($item));
                } else {
                    $carry[] = $item;
                }
                return $carry;
            },
            []
        );

        return array_values(
            array_intersect(
                range(0, 23),
                $runningHours
            )
        );
    }

    /**
     * Is it running time ?
     * @param int $unixtime
     * @return bool
     */
    private function isRunningTime(int $unixtime) : bool
    {
        $hour = date('G', $unixtime);
        return in_array($hour, $this->runningHours, false);
    }
}
