<?php
/**
 * Option Parser Util class
 *
 * @author sameyasu
 * @version 0.1
 * @copyright 2019 sameyasu
 * @package HerokuHC
 * @license MIT
 **/
namespace HerokuHC;

/**
 * Option Parser
 **/
final class OptionParser
{
    use MonologLoggerTrait;

    const DEFAULT_INTERVAL_IN_SECONDS = 600;
    const DEFAULT_HOURS = '0-23';
    const DEFAULT_WEEKDAYS = '1,2,3,4,5,6,7';

    /**
     * Constructor
     **/
    public function __construct()
    {
        $this->initLogger();
    }

    /**
     * Parse URL
     * @param string $url
     * @return string
     */
    public function parseUrl(string $url) : ?string
    {
        if (preg_match('|\Ahttps?://|', $url, $matches) === 1) {
            $this->logger->info('URL', ['url' => $url]);
            return $url;
        } else {
            $this->logger->warning('Invalid URL', ['url' => $url]);
            return null;
        }
    }

    /**
     * Parse interval option
     * @param string $interval
     * @return array tuple (minInterval, maxInterval)
     */
    public function parseInterval(string $interval) : array
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
     * Parse running hours
     * @param string $hours
     * @return array Map of hours
     */
    public function parseHours(string $hours) : array
    {
        if ($hours === '') {
            $hours = self::DEFAULT_HOURS;
        }

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
     * Parse WEEKDAYS option
     * @param string $weekdays
     * @return array Array of weekdays
     */
    public function parseWeekdays(string $weekdays) : array
    {
        if (empty($weekdays)) {
            // all days
            $weekdays = self::DEFAULT_WEEKDAYS;
        }

        $rangeWeekdays = array_map(
            function ($w) {
                if (preg_match('/\A(?<start>[1-7]+)-(?<end>[1-7]+)\z/', $w, $matches) === 1) {
                    if ($matches['start'] <= $matches['end']) {
                        return range($matches['start'], $matches['end']);
                    } else {
                        $this->logger->warning(
                            'Invalid weekday range',
                            ['range' => $w, 'start' => $matches['start'], 'end' => $matches['end']]
                        );
                        return [];
                    }
                } elseif (is_numeric($w)) {
                    return [intval($w)];
                } else {
                    $this->logger->warning('Invalid weekday', ['number' => $w]);
                    return [];
                }
            },
            explode(',', $weekdays)
        );

        $runningWeekdays = array_reduce(
            $rangeWeekdays,
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
                range(1, 7),
                $runningWeekdays
            )
        );
    }
}
