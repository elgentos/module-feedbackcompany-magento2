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

class ImportButton extends Field
{

    /**
     * @var ReviewsHelper
     */
    private $rev;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    private $request;

    /**
     * @var string
     */
    protected $_template = 'Magmodules_TheFeedbackCompany::system/config/button/button.phtml';

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
     * @param AbstractElement $element
     *
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();

        return parent::render($element);
    }

    /**
     * @param AbstractElement $element
     *
     * @return string
     */
    public function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }

    /**
     * Returns url for review import.
     *
     * @return string
     */
    public function getAjaxUrl()
    {
        $storeId = $this->request->getParam('store', 0);
        $websiteId = $this->request->getParam('website');
        if (!empty($websiteId)) {
            return $this->getUrl('thefeedbackcompany/actions/import/website/' . $websiteId);
        } else {
            return $this->getUrl('thefeedbackcompany/actions/import/store/' . $storeId);
        }
    }

    /**
     * Last importdate to display as comment msg under button.
     *
     * @return mixed
     */
    public function getLastImported()
    {
        return $this->rev->getLastImported();
    }

    /**
     * @return mixed
     */
    public function getButtonHtml()
    {
        if (!$this->checkOauthData()) {
            $button_data = ['id' => 'import_button', 'label' => __('Manually import summary'), 'class' => 'disabled'];
        } else {
            $button_data = ['id' => 'import_button', 'label' => __('Manually import summary')];
        }

        $button = $this->getLayout()->createBlock('Magento\Backend\Block\Widget\Button')->setData($button_data);

        return $button->toHtml();
    }

    /**
     * Checks if store/website view has all oauth data.
     *
     * @return bool
     */
    public function checkOauthData()
    {
        $storeId = $this->request->getParam('store');
        $websiteId = $this->request->getParam('website');

        if ($oauthData = $this->rev->getOauthData($storeId, $websiteId)) {
            return true;
        }

        return false;
    }
}
