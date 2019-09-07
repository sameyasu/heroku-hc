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

/**
 * Runner Class
 **/
final class Runner
{
    use MonologLoggerTrait;

    const DEFAULT_INTERVAL_IN_SECONDS = 600;

    /**
     * Constructor
     **/
    private function __construct()
    {
        $this->initLogger();
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
        list($minInterval, $maxInterval) = $this->calculateInterval($interval);

        $hours = getenv('HOURS') ?: '0-23';
        $runningHours = $this->calculateRunningHours($hours);

        $this->logger->info(
            'Started',
            [
                'URL' => $url,
                'interval' => [$minInterval, $maxInterval],
                'hours' => $runningHours,
            ]
        );

        $checker = new HttpUrlChecker();

        $timer = (
            new Timer(
                // FIXME
                function () use ($checker, $url) {
                    $checker->check($url);
                }
            )
        )
        ->setRandomInterval($minInterval, $maxInterval)
        ->setRunningHours($runningHours)
        ->start();
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
}
