<?php

namespace Makhmudovazeez\Paymelaravel\Services;

use Makhmudovazeez\Paymelaravel\Exceptions\PaymeException;
use Makhmudovazeez\Paymelaravel\Models\Order;
use Makhmudovazeez\Paymelaravel\Models\Transaction;

class Payme 
{
    
    public static function run(){
        
        try {

            switch (request('method')) {
                case 'CheckPerformTransaction':
                    self::CheckPerformTransaction();
                    break;
                case 'CheckTransaction':
                    self::CheckTransaction();
                    break;
                case 'CreateTransaction':
                    self::CreateTransaction();
                    break;
                case 'PerformTransaction':
                    self::PerformTransaction();
                    break;
                case 'CancelTransaction':
                    self::CancelTransaction();
                    break;
                case 'ChangePassword':
                    self::ChangePassword();
                    break;
                case 'GetStatement':
                    self::GetStatement();
                    break;
                default:
                    Response::error(
                        PaymeException::ERROR_METHOD_NOT_FOUND,
                        'Method not found.',
                        request('method')
                    );
                    break;
            }

        }catch(PaymeException $exception) {
            $exception->send();
        }

    }

    private function CheckPerformTransaction()
    {
        $order = Order::find(request('id'));

        // validate parameters
        $order->validate(request()->all());

        // todo: Check is there another active or completed transaction for this order
        $transaction = Transaction::find(request('id'));
        if ($transaction && ($transaction->state == Transaction::STATE_CREATED || $transaction->state == Transaction::STATE_COMPLETED)) {
            Response::error(
                PaymeException::ERROR_COULD_NOT_PERFORM,
                'There is other active/completed transaction for this order.'
            );
        }

        // if control is here, then we pass all validations and checks
        // send response, that order is ready to be paid.
        Response::send(['allow' => true]);
    }

    private function CheckTransaction()
    {
        // todo: Find transaction by id
        $transaction = Transaction::find(request('id'));
        if (!$transaction) {
            Response::error(
                PaymeException::ERROR_TRANSACTION_NOT_FOUND,
                'Transaction not found.'
            );
        }

        // todo: Prepare and send found transaction
        Response::send([
            'create_time'  => Format::datetime2timestamp($transaction->create_time),
            'perform_time' => Format::datetime2timestamp($transaction->perform_time),
            'cancel_time'  => Format::datetime2timestamp($transaction->cancel_time),
            'transaction'  => $transaction->id,
            'state'        => $transaction->state,
            'reason'       => isset($transaction->reason) ? 1 * $transaction->reason : null,
        ]);
    }

    private function CreateTransaction()
    {
        $order = Order::find(request('id'));

        // validate parameters
        $order->validate(request()->all());

        // todo: Check, is there any other transaction for this order/service
        $transaction = Transaction::where('id', request('id'))
        ->orWhere('order_id', request('account.order_id'))->first();
        
        if ($transaction) {
            if (($transaction->state == Transaction::STATE_CREATED || $transaction->state == Transaction::STATE_COMPLETED)
                && $transaction->paycom_transaction_id !== request('id')) {
                Response::error(
                    PaymeException::ERROR_INVALID_ACCOUNT,
                    'There is other active/completed transaction for this order.'
                );
            }
        }

        // todo: Find transaction by id
        $transaction = Transaction::where('id', request('id'))
        ->orWhere('order_id', request('account.order_id'))->first();

        if ($transaction) {
            if ($transaction->state != Transaction::STATE_CREATED) { // validate transaction state
                Response::error(
                    PaymeException::ERROR_COULD_NOT_PERFORM,
                    'Transaction found, but is not active.'
                );
            } elseif ($transaction->isExpired()) { // if transaction timed out, cancel it and send error
                $transaction->cancel(Transaction::REASON_CANCELLED_BY_TIMEOUT);
                Response::error(
                    PaymeException::ERROR_COULD_NOT_PERFORM,
                    'Transaction is expired.'
                );
            } else { // if transaction found and active, send it as response
                Response::send([
                    'create_time' => Format::datetime2timestamp($transaction->create_time),
                    'transaction' => $transaction->id,
                    'state'       => $transaction->state,
                    'receivers'   => $transaction->receivers,
                ]);
            }
        } else { // transaction not found, create new one

            // validate new transaction time
            if (Format::timestamp2milliseconds(1 * $this->request->params['time']) - Format::timestamp(true) >= Transaction::TIMEOUT) {
                Response::error(
                    PaymeException::ERROR_INVALID_ACCOUNT,
                    PaymeException::message(
                        'С даты создания транзакции прошло ' . Transaction::TIMEOUT . 'мс',
                        'Tranzaksiya yaratilgan sanadan ' . Transaction::TIMEOUT . 'ms o`tgan',
                        'Since create time of the transaction passed ' . Transaction::TIMEOUT . 'ms'
                    ),
                    'time'
                );
            }

            // create new transaction
            // keep create_time as timestamp, it is necessary in response
            $create_time                        = Format::timestamp(true);
            $transaction->paycom_transaction_id = $this->request->params['id'];
            $transaction->paycom_time           = $this->request->params['time'];
            $transaction->paycom_time_datetime  = Format::timestamp2datetime($this->request->params['time']);
            $transaction->create_time           = Format::timestamp2datetime($create_time);
            $transaction->state                 = Transaction::STATE_CREATED;
            $transaction->amount                = $this->request->amount;
            $transaction->order_id              = $this->request->account('order_id');
            $transaction->save(); // after save $transaction->id will be populated with the newly created transaction's id.

            // send response
            Response::send([
                'create_time' => $create_time,
                'transaction' => $transaction->id,
                'state'       => $transaction->state,
                'receivers'   => null,
            ]);
        }
    }

    private function PerformTransaction()
    {

        $transaction = Transaction::where('id', request('id'))
        ->orWhere('order_id', request('account.order_id'))->first();

        // if transaction not found, send error
        if (!$transaction) {
            Response::error(PaymeException::ERROR_TRANSACTION_NOT_FOUND, 'Transaction not found.');
        }

        switch ($transaction->state) {
            case Transaction::STATE_CREATED: // handle active transaction
                if ($transaction->isExpired()) { // if transaction is expired, then cancel it and send error
                    $transaction->cancel(Transaction::REASON_CANCELLED_BY_TIMEOUT);
                    Response::error(
                        PaymeException::ERROR_COULD_NOT_PERFORM,
                        'Transaction is expired.'
                    );
                } else { // perform active transaction
                    // todo: Mark order/service as completed
                    $params = ['order_id' => $transaction->order_id];
                    $order  = Order::find(request('order_id'));

                    $order->changeState(Order::STATE_PAY_ACCEPTED);

                    // todo: Mark transaction as completed
                    $perform_time              = Format::timestamp(true);
                    $transaction->state        = Transaction::STATE_COMPLETED;
                    $transaction->perform_time = Format::timestamp2datetime($perform_time);
                    $transaction->save();

                    Response::send([
                        'transaction'  => $transaction->id,
                        'perform_time' => $perform_time,
                        'state'        => $transaction->state,
                    ]);
                }
                break;

            case Transaction::STATE_COMPLETED: // handle complete transaction
                // todo: If transaction completed, just return it
                Response::send([
                    'transaction'  => $transaction->id,
                    'perform_time' => Format::datetime2timestamp($transaction->perform_time),
                    'state'        => $transaction->state,
                ]);
                break;

            default:
                // unknown situation
                Response::error(
                    PaymeException::ERROR_COULD_NOT_PERFORM,
                    'Could not perform this operation.'
                );
                break;
        }
    }

    private function CancelTransaction()
    {
        $transaction = Transaction::where('id', request('id'))
        ->orWhere('order_id', request('account.order_id'))->first();

        // if transaction not found, send error
        if (!$transaction) {
            Response::error(PaymeException::ERROR_TRANSACTION_NOT_FOUND, 'Transaction not found.');
        }

        switch ($transaction->state) {
            // if already cancelled, just send it
            case Transaction::STATE_CANCELLED:
            case Transaction::STATE_CANCELLED_AFTER_COMPLETE:
                Response::send([
                    'transaction' => $transaction->id,
                    'cancel_time' => Format::datetime2timestamp($transaction->cancel_time),
                    'state'       => $transaction->state,
                ]);
                break;

            // cancel active transaction
            case Transaction::STATE_CREATED:
                // cancel transaction with given reason
                $transaction->cancel(1 * $this->request->params['reason']);
                // after $found->cancel(), cancel_time and state properties populated with data

                // change order state to cancelled
                $order = new Order($this->request->id);
                $order->find($this->request->params);
                $order->changeState(Order::STATE_CANCELLED);

                // send response
                Response::send([
                    'transaction' => $transaction->id,
                    'cancel_time' => Format::datetime2timestamp($transaction->cancel_time),
                    'state'       => $transaction->state,
                ]);
                break;

            case Transaction::STATE_COMPLETED:
                // find order and check, whether cancelling is possible this order
                $order = Order::find(request('order_id'));

                if ($order->allowCancel()) {
                    // cancel and change state to cancelled
                    $transaction->cancel(1 * $this->request->params['reason']);
                    // after $found->cancel(), cancel_time and state properties populated with data

                    $order->changeState(Order::STATE_CANCELLED);

                    // send response
                    Response::send([
                        'transaction' => $transaction->id,
                        'cancel_time' => Format::datetime2timestamp($transaction->cancel_time),
                        'state'       => $transaction->state,
                    ]);
                } else {
                    // todo: If cancelling after performing transaction is not possible, then return error -31007
                    Response::error(
                        PaymeException::ERROR_COULD_NOT_CANCEL,
                        'Could not cancel transaction. Order is delivered/Service is completed.'
                    );
                }
                break;
        }
    }

    private function ChangePassword()
    {
        // validate, password is specified, otherwise send error
        if (!isset($this->request->params['password']) || !trim($this->request->params['password'])) {
            Response::error(PaymeException::ERROR_INVALID_ACCOUNT, 'New password not specified.', 'password');
        }

        // if current password specified as new, then send error
        if ($this->merchant->config['key'] == $this->request->params['password']) {
            Response::error(PaymeException::ERROR_INSUFFICIENT_PRIVILEGE, 'Insufficient privilege. Incorrect new password.');
        }

        // todo: Implement saving password into data store or file
        // example implementation, that saves new password into file specified in the configuration
        if (!file_put_contents($this->config['keyFile'], $this->request->params['password'])) {
            Response::error(PaymeException::ERROR_INTERNAL_SYSTEM, 'Internal System Error.');
        }

        // if control is here, then password is saved into data store
        // send success response
        Response::send(['success' => true]);
    }

    private function GetStatement()
    {
        // validate 'from'
        if (request('from')) {
            Response::error(PaymeException::ERROR_INVALID_ACCOUNT, 'Incorrect period.', 'from');
        }

        // validate 'to'
        if (request('to')) {
            Response::error(PaymeException::ERROR_INVALID_ACCOUNT, 'Incorrect period.', 'to');
        }

        // validate period
        if (1 * request('from') >= 1 * request('to')) {
            Response::error(PaymeException::ERROR_INVALID_ACCOUNT, 'Incorrect period. (from >= to)', 'from');
        }

        // get list of transactions for specified period
        $transaction  = new Transaction();
        $transactions = $transaction->report($this->request->params['from'], $this->request->params['to']);

        // send results back
        Response::send(['transactions' => $transactions]);
    }

}