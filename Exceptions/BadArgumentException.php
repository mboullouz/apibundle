<?php

namespace Axescloud\ApiBundle\Execeptions;

/**
 *
 * @author mboullouz
 *
 */
class BadArgumentException extends \RuntimeException {

    public function __construct($message = "Bad arguments", $code = 0, \Exception $previous = null) {
        parent::__construct($message, $code, $previous);
    }

}