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

/**
 * Class CommandManager
 */
class Vipps_Payment_Gateway_Command_CommandManager
{
    /**
     * @var Vipps_Payment_Gateway_Data_PaymentDataObjectFactory
     */
    private $paymentDataObjectFactory;

    /**
     * @var Vipps_Payment_Model_Helper_Pool
     */
    private $commandPool;

    /**
     * @var Vipps_Payment_Helper_Gateway helper
     */
    private $helper;

    /**
     * CommandManager constructor.
     */
    public function __construct()
    {
        $this->helper = Mage::helper('vipps_payment/gateway');
        $this->commandPool = Mage::getModel('vipps_payment/helper_pool');

        $this->commandPool->add('initiate', 'command_initiateCommand');
        $this->commandPool->add('getOrderStatus', 'command_getOrderStatusCommand');
        $this->commandPool->add('capture', 'command_captureCommand');
        $this->commandPool->add('refund', 'command_refundCommand');
        $this->commandPool->add('getPaymentDetails', 'command_getPaymentDetailsCommand');
        $this->commandPool->add('cancel', 'command_cancelCommand');

        $this->paymentDataObjectFactory = $this->helper->getSingleton('data_paymentDataObjectFactory');
    }

    /**
     * @param Mage_Sales_Model_Quote_Payment $payment
     * @param array $arguments
     *
     * @return Vipps_Payment_Gateway_Validator_Result|null
     * @throws Mage_Core_Exception
     * @throws Vipps_Payment_Gateway_Command_CommandException
     */
    public function initiatePayment(Mage_Sales_Model_Quote_Payment $payment, $arguments)
    {
        return $this->executeByCode('initiate', $payment, $arguments);
    }

    /**
     * Executes command by code
     *
     * @param string $commandCode
     * @param Mage_Payment_Model_Info|null $payment
     * @param array $arguments
     * @return Vipps_Payment_Gateway_Validator_Result|null
     * @throws Mage_Core_Exception
     * @throws Vipps_Payment_Gateway_Command_CommandException
     */
    public function executeByCode($commandCode, Mage_Payment_Model_Info $payment = null, array $arguments = [])
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
     * @param string $commandCode
     *
     * @return Vipps_Payment_Gateway_Command_CommandInterface
     * @throws Mage_Core_Exception
     */
    public function get($commandCode)
    {
        $commandClass = $this->commandPool->get($commandCode);
        return $this->helper->getSingleton($commandClass);
    }

    /**
     * @param Mage_Payment_Model_Info $payment
     * @param $amount
     * @return Vipps_Payment_Gateway_Validator_Result|null
     * @throws Mage_Core_Exception
     * @throws Vipps_Payment_Gateway_Command_CommandException
     */
    public function capture(Mage_Payment_Model_Info $payment, $amount)
    {
        return $this->executeByCode('capture', $payment, ['amount' => $amount]);
    }

    /**
     * {@inheritdoc}
     *
     * @param $orderId
     *
     * @return Vipps_Payment_Gateway_Validator_Result
     * @throws Vipps_Payment_Gateway_Command_CommandException
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
     * @return Vipps_Payment_Gateway_Validator_Result|null
     * @throws Vipps_Payment_Gateway_Command_CommandException
     */
    public function getOrderStatus($orderId)
    {
        return $this->executeByCode('getOrderStatus', null, ['orderId' => $orderId]);
    }

    /**
     * {@inheritdoc}
     *
     * @param Mage_Payment_Model_Info $payment
     * @param array $arguments
     *
     * @return Vipps_Payment_Gateway_Validator_Result|mixed|null
     * @throws Mage_Core_Exception
     * @throws Vipps_Payment_Gateway_Command_CommandException
     */
    public function cancel(Mage_Payment_Model_Info $payment, $arguments = [])
    {
        return $this->executeByCode('cancel', $payment, $arguments);
    }

    /**
     * Refund command.
     *
     * @param Mage_Payment_Model_Info $payment
     * @param float $amount
     * @return Vipps_Payment_Gateway_Validator_Result
     * @throws Mage_Core_Exception
     * @throws Vipps_Payment_Gateway_Command_CommandException
     */
    public function refund(Mage_Payment_Model_Info $payment, $amount)
    {
        return $this->executeByCode('refund',
            $payment,
            ['amount' => $amount]
        );
    }

    /**
     * Executes command
     *
     * @param Vipps_Payment_Gateway_Command_CommandInterface $command
     * @param |null $payment.
     * @param array $arguments
     * @return Vipps_Payment_Gateway_Validator_Result|null
     * @throws Vipps_Payment_Gateway_Command_CommandException
     */
    public function execute(Vipps_Payment_Gateway_Command_CommandInterface $command, $payment = null, array $arguments = [])
    {
        $commandSubject = $arguments;
        if ($payment !== null) {
            $commandSubject['payment'] = $this->paymentDataObjectFactory->create($payment);
        }

        return $command->execute($commandSubject);
    }
}
