<?php

namespace Makhmudovazeez\Paymelaravel\Services;

use Makhmudovazeez\Paymelaravel\Exceptions\PaymeException;

class Response
{
    /**
     * Sends response with the given result and error.
     * @param mixed $result result of the request.
     * @param mixed|null $error error.
     */
    public static function send($result, $error = null)
    {
        header('Content-Type: application/json; charset=UTF-8');

        $response['jsonrpc'] = '2.0';
        $response['id']      = request('id');
        $response['result']  = $result;
        $response['error']   = $error;

        echo json_encode($response);
    }

    /**
     * Generates PaymeException exception with given parameters.
     * @param int $code error code.
     * @param string|array $message error message.
     * @param string $data parameter name, that resulted to this error.
     * @throws PaymeException
     */
    public static function error($code, $message = null, $data = null)
    {
        throw new PaymeException(request('id'), $message, $code, $data);
    }
}
