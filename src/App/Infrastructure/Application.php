<?php

namespace App\Infrastructure;

use Silex\Application as SilexApplication;
use Silex\Application\SecurityTrait;
use Silex\Application\UrlGeneratorTrait;

/**
 * Class Application
 *
 * @package App
 */
class Application extends SilexApplication
{
    use SecurityTrait;
    use UrlGeneratorTrait;
}
