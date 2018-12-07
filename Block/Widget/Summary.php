<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\TheFeedbackCompany\Block\Widget;

use Magento\Framework\View\Element\Template;
use Magento\Widget\Block\BlockInterface;
use Magento\Backend\Block\Template\Context;
use Magmodules\TheFeedbackCompany\Helper\Reviews as ReviewsHelper;

class Summary extends Template implements BlockInterface
{

    protected $rev;

    /**
     * Summary constructor.
     * @param Context $context
     * @param ReviewsHelper $revHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        ReviewsHelper $revHelper,
        array $data = []
    ) {
        $this->rev = $revHelper;
        parent::__construct($context, $data);
    }

    /**
     * constructor
     */
    protected function _construct()
    {
        $template = $this->getData('template');
        parent::_construct();
        $this->setTemplate($template);
    }

    /**
     * Rich Snippets check.
     *
     * @return mixed
     */
    public function getRichSnippets()
    {
        return $this->getData('rich_snippets');
    }

    /**
     * Get summary data from review helper by storeId.
     *
     * @return array
     */
    public function getSummaryData()
    {
        $storeId = $this->_storeManager->getStore()->getId();
        $summary_data = $this->rev->getSummaryData($storeId);

        return $summary_data;
    }
}
