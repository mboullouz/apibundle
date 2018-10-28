<?php
/**
 * Mohamed Boullouz <mohamed.boullouz@gmail.com>
 */

namespace Axescloud\ApiBundle\Utils;

use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerBuilder;

class JsonSerializer {
    public static function toJson($data = null) {
        return SerializerBuilder::create()
            ->build()
            ->serialize($data, 'json', SerializationContext::create()->enableMaxDepthChecks());

    }
}