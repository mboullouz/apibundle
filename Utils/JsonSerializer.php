<?php
/**
 * Mohamed Boullouz <mohamed.boullouz@gmail.com>
 */

namespace Axescloud\ApiBundle\Utils;

use JMS\Serializer\Naming\SerializedNameAnnotationStrategy;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerBuilder;

class JsonSerializer {
    public static function toJson($data = null) {
        return SerializerBuilder::create()
            ->setPropertyNamingStrategy(new \JMS\Serializer\Naming\IdenticalPropertyNamingStrategy())
            ->build()
            ->serialize($data, 'json', SerializationContext::create()->enableMaxDepthChecks());

    }
}