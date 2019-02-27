<?php

namespace Vipps\Payment\Model\Adapter\Command;

/**
 * Class RestartFactory
 */
class ManualCancelFactory
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * RestartFactory constructor.
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @param QuoteInterface $vippsQuote
     * @return ManualCancel
     */
    public function create(QuoteInterface $vippsQuote)
    {
        return $this->objectManager->create(ManualCancel::class, ['vippsQuote' => $vippsQuote]); //@codingStandardsIgnoreLine
    }
}
