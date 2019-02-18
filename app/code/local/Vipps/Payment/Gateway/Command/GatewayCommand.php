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

use Vipps\Payment\Gateway\Http\Client\ClientInterface;
use Vipps\Payment\Gateway\Exception\ExceptionFactory;
use Vipps\Payment\Gateway\Exception\VippsException;
use Vipps\Payment\Gateway\Http\Transfer;
use Vipps\Payment\Gateway\Request\SubjectReader;
use Vipps\Payment\Gateway\Response\HandlerInterface;
use Vipps\Payment\Gateway\Validator\ValidatorInterface;
use Vipps\Payment\Lib\Formatter;
use Vipps\Payment\Model\Adapter\Adapter\JsonEncoder;


use Vipps\Payment\Gateway\Http\TransferFactory;
use Vipps\Payment\Gateway\Request\BuilderInterface;
use Vipps\Payment\Model\Adapter\Profiling\Profiler;

/**
 * Class GatewayCommand
 * @api
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class GatewayCommand implements CommandInterface
{
    use Formatter;

    /**
     * @var BuilderInterface
     */
    protected $requestBuilder;

    /**
     * @var TransferFactory
     */
    protected $transferFactory;

    /**
     * @var ClientInterface
     */
    protected $client;

    /**
     * @var HandlerInterface
     */
    protected $handler;

    /**
     * @var ValidatorInterface
     */
    protected $validator;

    /**
     * @var \Vipps_Payment_Model_Logger
     */
    protected $logger;

    /**
     * @var ExceptionFactory
     */
    protected $exceptionFactory;

    /**
     * @var JsonEncoder
     */
    protected $jsonDecoder;

    /**
     * @var Profiler
     */
    protected $profiler;

    protected $subjectReader;

    /**
     * GatewayCommand constructor.
     *
     * @param BuilderInterface $requestBuilder
     * @param TransferFactory $transferFactory
     * @param ClientInterface $client
     * @param Profiler $profiler
     * @param HandlerInterface|null $handler
     * @param ValidatorInterface|null $validator
     */
    public function __construct(
        BuilderInterface $requestBuilder,
        TransferFactory $transferFactory,
        ClientInterface $client,
        HandlerInterface $handler = null,
        ValidatorInterface $validator = null
    ) {
        $this->requestBuilder = $requestBuilder;
        $this->transferFactory = $transferFactory;
        $this->client = $client;
        $this->handler = $handler;
        $this->validator = $validator;

        $this->logger = \Mage::getSingleton('vipps_payment/logger');
        $this->exceptionFactory = new \Vipps\Payment\Model\Adapter\ExceptionFactory();
        $this->jsonDecoder = new JsonEncoder();
        $this->subjectReader = new SubjectReader();
        $this->profiler = new Profiler();
    }

    /**
     * {@inheritdoc}
     *
     * @param array $commandSubject
     *
     * @return ResultInterface|array|null
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
        $responseBody = $this->jsonDecoder->decode($response->getContent());

        $this->profiler->save($transfer, $response);

        if (!$response->isSuccess()) {
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
                throw new CommandException(
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
     * @param Transfer $transfer
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
        return $orderId ?? ($transfer->getBody()['transaction']['orderId'] ?? (isset($responseBody['orderId']) ? $responseBody['orderId'] : null));
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
