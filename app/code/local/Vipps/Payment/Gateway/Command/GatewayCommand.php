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
 * Class GatewayCommand
 */
class Vipps_Payment_Gateway_Command_GatewayCommand implements Vipps_Payment_Gateway_Command_CommandInterface
{
    use Vipps_Payment_Model_Helper_Formatter;

    /**
     * @var Vipps_Payment_Gateway_Request_BuilderInterface
     */
    protected $requestBuilder;

    /**
     * @var Vipps_Payment_Gateway_Http_TransferFactory
     */
    protected $transferFactory;

    /**
     * @var Vipps_Payment_Gateway_Http_Client_ClientInterface
     */
    protected $client;

    /**
     * @var \Vipps_Payment_Gateway_Response_HandlerInterface
     */
    protected $handler;

    /**
     * @var Vipps_Payment_Gateway_Validator_ValidatorInterface
     */
    protected $validator;

    /**
     * @var \Vipps_Payment_Model_Adapter_Logger
     */
    protected $logger;

    /**
     * @var \Vipps_Payment_Gateway_Exception_ExceptionFactory
     */
    protected $exceptionFactory;

    /**
     * @var \Vipps_Payment_Model_Adapter_JsonEncoder
     */
    protected $jsonDecoder;

    /**
     * @var \Vipps_Payment_Model_Profiling_Profiler
     */
    protected $profiler;

    /**
     * @var \Vipps_Payment_Gateway_Request_SubjectReader
     */
    protected $subjectReader;

    /**
     * @var Vipps_Payment_Helper_Gateway
     */
    protected $helper;

    /**
     * GatewayCommand constructor.
     *
     * @param Vipps_Payment_Gateway_Request_BuilderInterface $requestBuilder
     * @param \Vipps_Payment_Gateway_Http_TransferFactory $transferFactory
     * @param Vipps_Payment_Gateway_Http_Client_ClientInterface $client
     * @param \Vipps_Payment_Gateway_Response_HandlerInterface|null $handler
     * @param Vipps_Payment_Gateway_Validator_ValidatorInterface|null $validator
     * @throws Mage_Core_Exception
     */
    public function __construct(
        Vipps_Payment_Gateway_Request_BuilderInterface $requestBuilder,
        Vipps_Payment_Gateway_Http_TransferFactory $transferFactory,
        Vipps_Payment_Gateway_Http_Client_ClientInterface $client,
        Vipps_Payment_Gateway_Response_HandlerInterface $handler = null,
        Vipps_Payment_Gateway_Validator_ValidatorInterface $validator = null
    ) {
        $this->helper = Mage::helper('vipps_payment/gateway');
        $this->requestBuilder = $requestBuilder;
        $this->transferFactory = $transferFactory;
        $this->client = $client;
        $this->handler = $handler;
        $this->validator = $validator;

        $this->logger = Mage::getSingleton('vipps_payment/adapter_logger');
        $this->jsonDecoder = Mage::getSingleton('vipps_payment/adapter_jsonEncoder');
        $this->profiler = Mage::getModel('vipps_payment/profiling_profiler');
        $this->exceptionFactory = $this->helper->getSingleton('exception_exceptionFactory');
        $this->subjectReader = $this->helper->getSingleton('request_subjectReader');
    }

    /**
     * {@inheritdoc}
     *
     * @param array $commandSubject
     *
     * @return Vipps_Payment_Gateway_Validator_Result|array|null
     * @throws \Exception
     */
    public function execute(array $commandSubject)
    {
        $transfer = $this->transferFactory->create(
            $this->requestBuilder->build($commandSubject)
        );

        $result = $this->client->placeRequest($transfer);

        /** @var \Zend_Http_Response $response */
        $response = $result['response'];
        $responseBody = $this->jsonDecoder->decode($response->getBody());

        $this->profiler->save($transfer, $response);

        if (!$response->isSuccessful()) {
            $error = $this->extractError($responseBody);
            $orderId = $this->extractOrderId($transfer, $responseBody);
            $errorCode = isset($error['code']) ? $error['code'] : $response->getStatusCode();
            $errorMessage = isset($error['message']) ? $error['message'] : $response->getReasonPhrase();
            $exception = $this->exceptionFactory->create($errorCode, $errorMessage);
            $message = sprintf(
                'Request error. Code: "%s", message: "%s", order id: "%s"',
                $errorCode,
                $errorMessage,
                $orderId
            );
            $this->logger->critical($message);
            throw $exception;
        }

        /** Validating Success response body by specific command validators */
        if ($this->validator !== null) {
            $validationResult = $this->validator->validate(
                array_merge($commandSubject, ['jsonData' => $responseBody])
            );
            if (!$validationResult->isValid()) {
                $this->logValidationFails($validationResult->getFailsDescription());
                throw new Vipps_Payment_Gateway_Command_CommandException(
                    __('Transaction validation failed.')
                );
            }
        }

        /** Handling response after validation is success */
        if ($this->handler) {
            $this->handler->handle($commandSubject, $responseBody);
        }

        return $responseBody;
    }

    /**
     * Method to extract error code and message from response.
     *
     * @param $responseBody
     *
     * @return array
     */
    protected function extractError($responseBody)
    {
        return [
            'code'    => isset($responseBody[0]['errorCode']) ? $responseBody[0]['errorCode'] : null,
            'message' => isset($responseBody[0]['errorMessage']) ? $responseBody[0]['errorMessage'] : null,
        ];
    }

    /**
     * @param Vipps_Payment_Gateway_Http_Transfer $transfer
     * @param array $responseBody
     *
     * @return string|null
     */
    protected function extractOrderId($transfer, $responseBody)
    {
        $orderId = null;
        if (preg_match('/payments(\/([^\/]+)\/([a-z]+))?$/', $transfer->getUri(), $matches)) {
            $orderId = isset($matches[2]) ? $matches[2] : null;
        }
        return isset($orderId) ? $orderId : (isset($transfer->getBody()['transaction']['orderId']) ? $transfer->getBody()['transaction']['orderId'] : (isset($responseBody['orderId']) ? $responseBody['orderId'] : null));
    }

    /**
     * @param array[] $fails
     *
     * @return void
     */
    protected function logValidationFails(array $fails)
    {
        foreach ($fails as $failPhrase) {
            $this->logger->critical((string)$failPhrase);
        }
    }
}
