<?php

namespace Axescloud\ApiBundle\Execeptions;

use Axescloud\ApiBundle\Utils\ModelStateResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

class InternalServerException extends  HttpException  {

    /**
     *
     * @param ModelStateResponse $modelState
     */
    public function __construct(ModelStateResponse $modelState) {
        parent::__construct($modelState, 500);
    }

    static function fail(string $msg, bool $log = true) {
        //if ($log) (new Logger())->error($msg, [], true);
        throw new InternalServerException(ModelStateResponse::fail($msg));
    }
}