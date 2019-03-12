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
 * Class ZendClient
 */
class Vipps_Payment_Model_Adapter_ZendClient extends \Zend_Http_Client
{
    /**
     * Internal flag to allow decoding of request body
     *
     * @var bool
     */
    protected $_urlEncodeBody = true;

    /**
     * @param null|\Zend_Uri_Http|string $uri
     * @param null|array $config
     */
    public function __construct($uri = null, $config = null)
    {
        $this->config['useragent'] = self::class;

        parent::__construct($uri, $config);
    }

    /**
     * @return $this
     * @throws \Zend_Http_Client_Exception
     */
    protected function _trySetCurlAdapter()
    {
        if (extension_loaded('curl')) {
            $this->setAdapter(Mage::getModel('vipps_payment/adapter_curl'));
        }
        return $this;
    }

    /**
     * @param null|string $method
     * @return \Zend_Http_Response
     * @throws \Zend_Http_Client_Exception
     */
    public function request($method = null)
    {
        $this->_trySetCurlAdapter();
        return parent::request($method);
    }

    /**
     * Change value of internal flag to disable/enable custom prepare functionality
     *
     * @param bool $flag
     * @return self
     */
    public function setUrlEncodeBody($flag)
    {
        $this->_urlEncodeBody = $flag;
        return $this;
    }

    /**
     * Adding custom functionality to decode data after
     * standard prepare functionality
     *
     * @return string
     * @throws \Zend_Http_Client_Exception
     */
    protected function _prepareBody()
    {
        $body = parent::_prepareBody();

        if (!$this->_urlEncodeBody && $body) {
            $body = urldecode($body);
            $this->setHeaders('Content-length', strlen($body));
        }

        return $body;
    }
}
