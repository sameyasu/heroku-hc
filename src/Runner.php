<?php declare(strict_types=1);
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

    const DEFAULT_INTERVAL_IN_SECONDS = 600;

    private $httpClient;

    private $minInterval;

    private $maxInterval;

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

        $tz = getenv('TZ');
        if ($tz !== false) {
            date_default_timezone_set($tz);
        }

        $me = new self();
        $me->run();
    }

    /**
     * Running
     */
    public function run() : void
    {
        $url = getenv('HC_URL') ?: '';
        if (preg_match('|\Ahttps?://|', $url, $matches) !== 1) {
            $this->logger->warning('Invalid URL', ['url' => $url]);
            return;
        }

        $interval = getenv('INTERVAL') ?: '';
        list($this->minInterval, $this->maxInterval) = $this->calculateInterval($interval);

        $hours = getenv('HOURS') ?: '0-23';
        $this->runningHours = $this->calculateRunningHours($hours);

        $this->logger->info(
            'Started',
            [
                'URL' => $url,
                'interval' => [$this->minInterval, $this->maxInterval],
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

            $intervalInSec = $this->getInterval();
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
                    $this->logger->warning('Invalid StatusCode', [
                        'statusCode' => $response->getStatusCode()
                    ]);
                }
            },
            function (RequestException $exception) {
                $this->logger->warning('Rejected', [
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
     * @return int
     */
    private function getInterval() : int
    {
        if ($this->minInterval === $this->maxInterval || $this->maxInterval < $this->minInterval) {
            return $this->minInterval;
        }

        return mt_rand($this->minInterval, $this->maxInterval);
    }

    /**
     * Calculate interval option
     * @param string $interval
     * @return array tuple
     */
    private function calculateInterval(string $interval) : array
    {
        if (preg_match('/\A(?<min>[0-9]+)-(?<max>[0-9]+)\z/', $interval, $matches) === 1) {
            return [
                intval($matches['min']),
                intval($matches['max']),
            ];
        } elseif (is_numeric($interval)) {
            return [
                intval($interval),  // min
                intval($interval),  // max
            ];
        } else {
            $this->logger->warning('Invalid Interval', ['interval' => $interval]);
            return [
                self::DEFAULT_INTERVAL_IN_SECONDS,  // min
                self::DEFAULT_INTERVAL_IN_SECONDS,  // max
            ];
        }
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
                        $this->logger->warning(
                            'Invalid range',
                            ['range' => $h, 'start' => $matches['start'], 'end' => $matches['end']]
                        );
                        return [];
                    }
                } elseif (is_numeric($h)) {
                    return [intval($h)];
                } else {
                    $this->logger->warning('Invalid hour', ['hour' => $h]);
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
