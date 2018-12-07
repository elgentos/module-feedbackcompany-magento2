<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\TheFeedbackCompany\Block\Adminhtml\System\Config\Form;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magmodules\TheFeedbackCompany\Helper\Reviews as ReviewsHelper;

class ReviewSummary extends Field
{

    /**
     * @var string
     */
    protected $_template = 'Magmodules_TheFeedbackCompany::system/config/fieldset/summary.phtml';

    /**
     * @var ReviewsHelper
     */
    private $rev;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    private $request;

    /**
     * @param Context       $context
     * @param ReviewsHelper $revHelper
     * @param array         $data
     */
    public function __construct(
        Context $context,
        ReviewsHelper $revHelper,
        array $data = []
    ) {
        $this->rev = $revHelper;
        $this->request = $context->getRequest();
        parent::__construct($context, $data);
    }

    /**
     * @return null
     */
    public function getCacheLifetime()
    {
        return null;
    }

    /**
     * Get review summary data from helper.
     *
     * @return bool
     */
    public function getReviewSummary()
    {
        $summaryData = [];
        $storeId = $this->request->getParam('store');
        $websiteId = $this->request->getParam('website');

        if ($websiteId || $storeId) {
            if ($data = $this->rev->getSummaryData($storeId, $websiteId)) {
                $summaryData[] = $data;
            }
        } else {
            $summaryData = $this->rev->getAllSummaryData();
        }

        return $summaryData;
    }

    /**
     * @param AbstractElement $element
     *
     * @return bool
     */
    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();

        return parent::render($element);
    }

    /**
     * @param AbstractElement $element
     *
     * @return bool
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }
}
