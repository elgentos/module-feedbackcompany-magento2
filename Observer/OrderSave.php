<?php
/**
 * Copyright © 2017 Feedback Company. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace FeedbackCompany\TheFeedbackCompany\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use FeedbackCompany\TheFeedbackCompany\Model\Api as ApiModel;
use Psr\Log\LoggerInterface;

class OrderSave implements ObserverInterface
{

    /**
     * @var ApiModel
     */
    private $apiModel;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * OrderSave constructor.
     *
     * @param ApiModel        $apiModel
     * @param LoggerInterface $logger
     */
    public function __construct(
        ApiModel $apiModel,
        LoggerInterface $logger
    ) {
        $this->apiModel = $apiModel;
        $this->logger = $logger;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        try {
            $order = $observer->getEvent()->getOrder();
            $this->apiModel->sendInvitation($order);
        } catch (\Exception $e) {
            $this->logger->critical($e);
            $this->logger->debug('exception');
        }
    }
}
