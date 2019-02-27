<?php

namespace Vipps\Payment\Model\Adapter\Command;

/**
 * Class RestartFactory
 */
class RestartFactory
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
     * @return Restart
     */
    public function create(QuoteInterface $vippsQuote)
    {
        return $this->objectManager->create(Restart::class, ['vippsQuote' => $vippsQuote]); //@codingStandardsIgnoreLine
    }
}
