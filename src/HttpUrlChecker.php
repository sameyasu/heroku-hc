<?php
/**
 * Http/Https URL Checker
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
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;
use HerokuHC\Entity\HttpUrlCheckerResult;

/**
 * Http/Https URL Checker
 */
class HttpUrlChecker
{
    use MonologLoggerTrait;

    private $httpClient;

    private $url;

    /**
     * Constructor
     * @param string $url
     */
    public function __construct(string $url)
    {
        $this->initLogger();
        $this->initHttpClient();
        $this->url = $url;
    }

    /**
     * Initialize guzzlehttp client object
     * @return void
     */
    protected function initHttpClient() : void
    {
        $this->httpClient = new Client([
            'timeout'  => 10.0,
        ]);
    }

    /**
     * Checks URL
     * @return HttpUrlCheckerResult
     */
    public function check() : HttpUrlCheckerResult
    {
        $result = new HttpUrlCheckerResult();
        $this->httpClient->requestAsync('GET', $this->url, [
            'on_stats' => function ($stats) use (&$result) {
                $result->transferTime = $stats->getTransferTime();
            }
        ])
        ->then(
            function (ResponseInterface $response) use (&$result) {
                $result->responseStatusCode = $response->getStatusCode();
                if ($response->getStatusCode() === 200) {
                    $result->responseBody = $response->getBody()->getContents();
                    $result->ok = true;
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

        $this->logger->info('Checked', ['result' => $result->toArray()]);
        return $result;
    }
}
