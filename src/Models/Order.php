<?php

namespace Makhmudovazeez\Paymelaravel\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Makhmudovazeez\Paymelaravel\Exceptions\PaymeException;

class Order extends Model
{
    use HasFactory;

    protected $guarded = [];
    
    /** Order is available for sell, anyone can buy it. */
    const STATE_AVAILABLE = 0;

    /** Pay in progress, order must not be changed. */
    const STATE_WAITING_PAY = 1;

    /** Order completed and not available for sell. */
    const STATE_PAY_ACCEPTED = 2;

    /** Order is cancelled. */
    const STATE_CANCELLED = 3;

    /**
     * Validates amount and account values.
     * @param array $params amount and account parameters to validate.
     * @return bool true - if validation passes
     * @throws PaymeException - if validation fails
     */
    public function validate(array $params)
    {
        // todo: Validate amount, if failed throw error
        // for example, check amount is numeric
        if (!is_numeric($params['amount'])) {
            throw new PaymeException(
                $this->request_id,
                'Incorrect amount.',
                PaymeException::ERROR_INVALID_AMOUNT
            );
        }

        // todo: Validate account, if failed throw error
        // assume, we should have order_id
        if (!isset($params['account']['order_id']) || !$params['account']['order_id']) {
            throw new PaymeException(
                $this->request_id,
                PaymeException::message(
                    'Неверный код заказа.',
                    'Harid kodida xatolik.',
                    'Incorrect order code.'
                ),
                PaymeException::ERROR_INVALID_ACCOUNT,
                'order_id'
            );
        }

        // todo: Check is order available

        // assume, after find() $this will be populated with Order data
        $order = $this->find($params['account']);

        // Check, is order found by specified order_id
        if (!$order || !$order->id) {
            throw new PaymeException(
                $this->request_id,
                PaymeException::message(
                    'Неверный код заказа.',
                    'Harid kodida xatolik.',
                    'Incorrect order code.'
                ),
                PaymeException::ERROR_INVALID_ACCOUNT,
                'order_id'
            );
        }

        // validate amount
        // convert $this->amount to coins
        // $params['amount'] already in coins
        if ((100 * $this->amount) != (1 * $params['amount'])) {
            throw new PaymeException(
                $this->request_id,
                'Incorrect amount.',
                PaymeException::ERROR_INVALID_AMOUNT
            );
        }

        // for example, order state before payment should be 'waiting pay'
        if ($this->state != self::STATE_WAITING_PAY) {
            throw new PaymeException(
                $this->request_id,
                'Order state is invalid.',
                PaymeException::ERROR_COULD_NOT_PERFORM
            );
        }

        // keep params for further use
        $this->params = $params;

        return true;
    }

}