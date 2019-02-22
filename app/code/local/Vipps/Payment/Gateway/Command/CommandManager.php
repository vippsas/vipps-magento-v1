<?php
/**
 * Copyright 2019 Vipps
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated
 *    documentation files (the "Software"), to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software,
 *  and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED
 * TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NON INFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF
 * CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 */

namespace Vipps\Payment\Gateway\Command;

use Vipps\Payment\Gateway\Data\PaymentDataObjectFactory;
use Vipps\Payment\Model\Helper\Pool;

/**
 * Class CommandManager
 * @package Vipps\Payment\Model
 */
class CommandManager
{
    /**
     * @var PaymentDataObjectFactory
     */
    private $paymentDataObjectFactory;

    /**
     * @var Pool
     */
    private $commandPool;

    /**
     * CommandManager constructor.
     */
    public function __construct()
    {
        $this->commandPool = new Pool();

        $this->commandPool->add('initiate', InitiateCommand::class);
        $this->commandPool->add('getOrderStatus', GetOrderStatusCommand::class);
        $this->commandPool->add('capture', CaptureCommand::class);
        $this->commandPool->add('refund', RefundCommand::class);
        $this->commandPool->add('getPaymentDetails', GetPaymentDetailsCommand::class);
        $this->commandPool->add('cancel', CancelCommand::class);

        $this->paymentDataObjectFactory = new PaymentDataObjectFactory();
    }

    /**
     * @param \Mage_Payment_Model_Info $payment
     * @param array $arguments
     *
     * @return ResultInterface|null
     * @throws CommandException
     * @throws NotFoundException
     */
    public function initiatePayment(\Mage_Sales_Model_Quote_Payment $payment, $arguments)
    {
        return $this->executeByCode('initiate', $payment, $arguments);
    }

    /**
     * Executes command by code
     *
     * @param string $commandCode
     * @param \Mage_Payment_Model_Info|null $payment
     * @param array $arguments
     * @return Result|null
     * @throws NotFoundException
     * @throws CommandException
     * @since 100.1.0
     */
    public function executeByCode($commandCode, \Mage_Payment_Model_Info $payment = null, array $arguments = [])
    {
        $commandSubject = $arguments;
        if ($payment !== null) {
            $commandSubject['payment'] = $this->paymentDataObjectFactory->create($payment);
        }

        return $this
            ->get($commandCode)
            ->execute($commandSubject);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $commandCode
     *
     * @return CommandInterface
     * @throws NotFoundException
     */
    public function get($commandCode)
    {
        $commandClass = $this->commandPool->get($commandCode);

        return new $commandClass();
    }

    /**
     * {@inheritdoc}
     *
     * @param $orderId
     *
     * @return ResultInterface|null
     * @throws CommandException
     * @throws NotFoundException
     * @throws \Exception
     */
    public function getPaymentDetails($arguments = [])
    {
        return $this->executeByCode('getPaymentDetails', null, $arguments);
    }

    /**
     * {@inheritdoc}
     *
     * @param $orderId
     *
     * @return ResultInterface|null
     * @throws CommandException
     * @throws NotFoundException
     */
    public function getOrderStatus($orderId)
    {
        return $this->executeByCode('getOrderStatus', null, ['orderId' => $orderId]);
    }

    /**
     * {@inheritdoc}
     *
     * @param \Mage_Payment_Model_Info $payment
     * @param array $arguments
     *
     * @return ResultInterface|mixed|null
     * @throws CommandException
     * @throws NotFoundException
     */
    public function cancel(\Mage_Payment_Model_Info $payment, $arguments = [])
    {
        return $this->executeByCode('cancel', $payment, $arguments);
    }

    /**
     * Refund command.
     *
     * @param \Mage_Payment_Model_Info $payment
     * @param float $amount
     * @return Result
     */
    public function refund(\Mage_Payment_Model_Info $payment, $amount)
    {
        return $this->executeByCode(
            'refund',
            ['amount' => $amount, 'payment' => $payment]
        );
    }

    /**
     * Executes command
     *
     * @param CommandInterface $command
     * @param |null $payment @todo: specify interface.
     * @param array $arguments
     * @return Result|null
     * @throws CommandException
     * @since 100.1.0
     */
    public function execute(CommandInterface $command, $payment = null, array $arguments = [])
    {
        $commandSubject = $arguments;
        if ($payment !== null) {
            $commandSubject['payment'] = $this->paymentDataObjectFactory->create($payment);
        }

        return $command->execute($commandSubject);
    }
}
