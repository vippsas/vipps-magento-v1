<?php

namespace Vipps\Payment\Model\Adapter\Command;

use Magento\Framework\Exception\LocalizedException;
use Vipps\Payment\Model\Adapter\CancelFacade;
use Vipps\Payment\Model\Adapter\Order\Cancellation\Config;

/**
 * Restart Vipps Quote processing.
 */
class ManualCancel
{
    /**
     * @var QuoteInterface
     */
    private $vippsQuote;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var CancelFacade
     */
    private $cancelFacade;

    /**
     * Restart constructor.
     * @param QuoteInterface $vippsQuote
     * @param CartRepositoryInterface $cartRepository
     * @param CancelFacade $cancelFacade
     * @param Config $config
     */
    public function __construct(
        QuoteInterface $vippsQuote,
        CartRepositoryInterface $cartRepository,
        CancelFacade $cancelFacade,
        Config $config
    ) {
        $this->vippsQuote = $vippsQuote;
        $this->config = $config;
        $this->cartRepository = $cartRepository;
        $this->cancelFacade = $cancelFacade;
    }

    /**
     * Verify is Quote Processing allowed for restart.
     *
     * @return bool
     */
    public function isAllowed()
    {
        return in_array(
            $this->vippsQuote->getStatus(),
            [\Vipps_Payment_Model_QuoteStatusInterface::STATUS_PLACE_FAILED, \Vipps_Payment_Model_QuoteStatusInterface::STATUS_CANCEL_FAILED],
            true
        );
    }

    /**
     * @throws LocalizedException
     */
    public function execute()
    {
        try {
            $quote = $this->cartRepository->get($this->vippsQuote->getQuoteId());

            $this
                ->cancelFacade
                ->cancel($this->vippsQuote, $quote);
        } catch (\Exception $exception) {
            Mage::throwException(__('Failed to cancel the order. Please contact support team.'));
        }
    }
}
