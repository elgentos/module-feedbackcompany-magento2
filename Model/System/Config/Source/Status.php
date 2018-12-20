<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\TheFeedbackCompany\Model\System\Config\Source;

use Magento\Framework\Option\ArrayInterface;
use Magento\Sales\Model\Order\Config;

class Status implements ArrayInterface
{

    /**
     * @var Config
     */
    private $orderConfig;

    /**
     * Status constructor.
     *
     * @param Config $orderConfig
     */
    public function __construct(Config $orderConfig)
    {
        $this->orderConfig = $orderConfig;
    }

    /**
     * Get order statuses array
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];
        $statuses = $this->orderConfig->getStatuses();
        $options[] = ['value' => '', 'label' => __('-- Please Select --')];
        foreach ($statuses as $value => $label) {
            $options[] = ['value' => $value, 'label' => $label];
        }

        return $options;
    }
}
