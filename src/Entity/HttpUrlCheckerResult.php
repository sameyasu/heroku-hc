<?php
namespace HerokuHC\Entity;

/**
 * Result of HttpUrlChecker
 */
class HttpUrlCheckerResult extends AbstractEntity
{
    public $ok = false;
    public $transferTime;
    public $responseStatusCode;
    public $responseBody;
}
