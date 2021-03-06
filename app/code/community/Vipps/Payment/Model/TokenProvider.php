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
 * Class TokenProvider
 */
class Vipps_Payment_Model_TokenProvider
{
    /**
     * Variable to reserve time for request duration.
     *
     * @var int
     */
    private static $reservedValidTime = 60;

    /**
     * @var string
     */
    private static $endpointUrl = '/accessToken/get';

    /**
     * @var \Vipps_Payment_Model_Adapter_ResourceConnectionProvider
     */
    private $resourceConnection;

    /**
     * @var Vipps_Payment_Gateway_Config_Config
     */
    private $config;

    /**
     * @var Vipps_Payment_Model_UrlResolver
     */
    private $urlResolver;

    /**
     * @var \Vipps_Payment_Model_Adapter_JsonEncoder
     */
    private $serializer;

    /**
     * @var \Vipps_Payment_Model_Adapter_Logger
     */
    private $logger;

    /**
     * @var \Vipps_Payment_Model_Adapter_ScopeResolver
     */
    private $scopeResolver;

    /**
     * @var array
     */
    private $jwtRecord = [];

    /**
     * TokenProvider constructor.
     */
    public function __construct()
    {
        $this->resourceConnection = Mage::getSingleton('vipps_payment/adapter_resourceConnectionProvider');
        $this->config = Mage::helper('vipps_payment/gateway')->getSingleton('config_config');
        $this->serializer = Mage::getSingleton('vipps_payment/adapter_jsonEncoder');
        $this->logger = Mage::getSingleton('vipps_payment/adapter_logger');
        $this->urlResolver = Mage::getSingleton('vipps_payment/urlResolver');
        $this->scopeResolver = Mage::getModel('vipps_payment/adapter_scopeResolver');
    }

    /**
     * {@inheritdoc}
     *
     * @return mixed|string
     * @throws Mage_Core_Exception
     */
    public function get()
    {
        $this->loadTokenRecord();
        if (!$this->isValidToken()) {
            $this->regenerate();
        }
        return $this->jwtRecord['access_token'];
    }

    /**
     * Method to load latest token record from storage.
     *
     * @return array
     * @throws Mage_Core_Exception
     */
    private function loadTokenRecord()
    {
        if (!$this->jwtRecord) {
            $connection = $this->resourceConnection->getConnection();
            /** @var \Zend_Db_Select $select */
            $select = $connection->select(); //@codingStandardsIgnoreLine
            $select->from($this->resourceConnection->getTableName('vipps_payment_jwt'))//@codingStandardsIgnoreLine
            ->where('scope = ?', $this->getScopeType())// @codingStandardsIgnoreLine
            ->where('scope_id = ?', $this->getScopeId())//@codingStandardsIgnoreLine
            ->limit(1)//@codingStandardsIgnoreLine
            ->order("token_id DESC"); //@codingStandardsIgnoreLine
            $this->jwtRecord = $connection->fetchRow($select) ?: []; //@codingStandardsIgnoreLine
        }
        return $this->jwtRecord;
    }

    /**
     * Return current scope type.
     *
     * @return string
     */
    private function getScopeType()
    {
        return $this->scopeResolver->getScope()->getScopeType();
    }

    /**
     * Return current scope Id.
     *
     * @return int
     * @throws Mage_Core_Exception
     */
    private function getScopeId()
    {
        return $this->scopeResolver->getScope()->getId();
    }

    /**
     * Method to check token validation time.
     *
     * @return bool
     */
    private function isValidToken()
    {
        if (!empty($this->jwtRecord) &&
            ($this->jwtRecord['expires_on'] > time() + self::$reservedValidTime)
        ) {
            return true;
        }
        return false;
    }

    /**
     * Method to regenerate access token from Vipps and save it to storage.
     *
     */
    public function regenerate()
    {
        $jwt = $this->readJwt();
        $this->refreshJwt($jwt);
    }

    /**
     * Method to authenticate into Vipps API to retrieve access token(Json Web Token).
     *
     * @return array
     * @throws Vipps_Payment_Gateway_Exception_AuthenticationException
     */
    private function readJwt()
    {
        /** Configuring headers for Vipps authentication method */
        $headers = [
            Vipps_Payment_Gateway_Http_Client_Curl::HEADER_PARAM_CLIENT_ID        => $this->config->getValue('client_id'),
            Vipps_Payment_Gateway_Http_Client_Curl::HEADER_PARAM_CLIENT_SECRET    => $this->config->getValue('client_secret'),
            Vipps_Payment_Gateway_Http_Client_Curl::HEADER_PARAM_SUBSCRIPTION_KEY => $this->config->getValue('subscription_key1'),
        ];
        /** @var Vipps_Payment_Model_Adapter_ZendClient $client */
        $client = new Vipps_Payment_Model_Adapter_ZendClient();
        try {
            $client->setConfig(['strict' => false]);
            $client->setUri($this->urlResolver->getUrl(self::$endpointUrl));
            $client->setMethod(Vipps_Payment_Model_Adapter_ZendClient::POST);
            $client->setHeaders($headers);

            /** Making request to Vipps
             * @var $response \Zend_Http_Response
             */
            $response = $client->request();
            $jwt = $this->serializer->unserialize($response->getBody());
            if (!$response->isSuccessful()) {
                throw new \Exception($response->getBody()); //@codingStandardsIgnoreLine
            }
            if (!$this->isJwtValid($jwt)) {
                throw new \Exception('Not valid JWT data returned from Vipps. Response: ' . $response); //@codingStandardsIgnoreLine
            }
            $this->logger->debug('Token fetched from Vipps');
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            throw new Vipps_Payment_Gateway_Exception_AuthenticationException(
                __('Can\'t retrieve access token from Vipps.')
            );
        }
        return $jwt;
    }

    /**
     * Method to validate JWT token.
     *
     * @param $jwt
     *
     * @return bool
     */
    private function isJwtValid($jwt)
    {
        $requiredKeys = [
            'token_type', 'expires_in', 'ext_expires_in', 'not_before', 'resource', 'access_token'
        ];
        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $jwt)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Method to update token in storage if token_id was specified
     * and insert a new one in another case.
     *
     * @param $jwt
     * @throws Mage_Core_Exception
     */
    private function refreshJwt($jwt)
    {
        $connection = $this->resourceConnection->getConnection();
        try {
            /** Merge jwtRecord to save token_id for updating record in db */
            $this->jwtRecord = array_merge($this->jwtRecord, $jwt);
            if (isset($this->jwtRecord['token_id'])) {
                $connection->update(
                    $this->resourceConnection->getTableName('vipps_payment_jwt'),
                    $this->jwtRecord,
                    'token_id = ' . $this->jwtRecord['token_id']
                );
            } else {
                $this->jwtRecord['scope'] = $this->getScopeType();
                $this->jwtRecord['scope_id'] = $this->getScopeId();
                $connection->insert( //@codingStandardsIgnoreLine
                    $this->resourceConnection->getTableName('vipps_payment_jwt'),
                    $this->jwtRecord
                );
            }
            $this->logger->debug(__('Refreshed Jwt data.'));
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            Mage::throwException(__('Can\'t save jwt data to database.' . $e->getMessage()));
        }
    }
}
