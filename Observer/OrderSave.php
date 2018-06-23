<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\TheFeedbackCompany\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magmodules\TheFeedbackCompany\Model\Api as ApiModel;

class OrderSave implements ObserverInterface
{

    protected $apiModel;

    /**
     * OrderSave constructor.
     *
     * @param ApiModel $apiModel
     */
    public function __construct(
        ApiModel $apiModel
    ) {
        $this->apiModel = $apiModel;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $this->apiModel->sendInvitation($order);
    }
}
