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
 * Class RefundCommand
 */
class Vipps_Payment_Gateway_Command_GetOrderStatusCommand extends Vipps_Payment_Gateway_Command_GatewayCommand
{
    public function __construct()
    {
        parent::__construct(
            new Vipps_Payment_Gateway_Request_BuilderComposite_VippsGetOrderStatusRequest(),
            new Vipps_Payment_Gateway_Http_TransferFactory('GET', '/ecomm/v2/payments/:orderId/status', ['orderId' => 'orderId']),
            new Vipps_Payment_Gateway_Http_Client_Curl(),
            null,
            new Vipps_Payment_Gateway_Validator_Composite_VippsGetOrderStatusValidator()
        );
    }
}
