<?php

namespace Axescloud\ApiBundle\Execeptions;


use Axescloud\ApiBundle\Utils\ModelStateResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

class BadRequestException extends  HttpException {

    /**
     *
     * @param ModelStateResponse $modelState
     */
    public function __construct(ModelStateResponse $modelState) {
        parent::__construct($modelState->message, 400);
    }

    static function fail(string $msg, bool $log = true) {
       // if ($log) (new Logger())->error($msg,[],true);
        throw new BadRequestException(ModelStateResponse::fail($msg));
    }
}