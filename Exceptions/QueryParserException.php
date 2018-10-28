<?php

namespace Axescloud\ApiBundle\Execeptions;



use Symfony\Component\HttpKernel\Exception\HttpException;

class QueryParserException  extends  HttpException
{
    /**
     *
     * @param string     $message  The internal exception message
     * @param \Exception $previous The previous exception
     * @param int        $code     The internal exception code
     */
    public function __construct($message = null,$statusCode=400, \Exception $previous = null, $code = 0)
    {
        parent::__construct( $message , 400);
    }
}