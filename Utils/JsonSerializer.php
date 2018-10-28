<?php
/**
 * Mohamed Boullouz <mohamed.boullouz@gmail.com>
 */

namespace Axescloud\ApiBundle\Utils;

use JMS\Serializer\SerializationContext;
use JMS\Serializer\Serializer;
use JMS\Serializer\SerializerBuilder;

class JsonSerializer {
    /**
     * @var Serializer
     */
    private static $instance = null;

    public static function toJson($data = null) {
        if (empty(self::$instance)) {
            self::$instance = SerializerBuilder::create()
                ->setPropertyNamingStrategy(new \JMS\Serializer\Naming\IdenticalPropertyNamingStrategy())
                ->build();
        }
        return self::$instance
            ->serialize($data, 'json', SerializationContext::create()->enableMaxDepthChecks());

    }
}