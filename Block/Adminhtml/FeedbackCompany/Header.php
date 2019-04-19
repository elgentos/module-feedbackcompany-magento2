<?php
/**
 * Copyright © 2017 Feedback Company. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace FeedbackCompany\TheFeedbackCompany\Block\Adminhtml\FeedbackCompany;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use FeedbackCompany\TheFeedbackCompany\Helper\General as GeneralHelper;
use Magento\Backend\Block\Template\Context;

class Header extends Field
{

    const MODULE_SUPPORT_LINK = 'https://feedbackcompany.wixanswers.com/en/article/magento-2';
    const MODULE_CONTACT_LINK = 'https://www.feedbackcompany.com';

    /**
     * @var GeneralHelper
     */
    private $general;

    /**
     * @var string
     */
    protected $_template = 'FeedbackCompany_TheFeedbackCompany::system/config/fieldset/header.phtml';

    /**
     * Header constructor.
     *
     * @param Context       $context
     * @param GeneralHelper $general
     */
    public function __construct(
        Context $context,
        GeneralHelper $general
    ) {
        $this->general = $general;
        parent::__construct($context);
    }

    /**
     * @param AbstractElement $element
     *
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $element->addClass('feedbackcompany');

        return $this->toHtml();
    }

    /**
     * Image with extension and magento version.
     *
     * @return string
     */
    public function getImage()
    {
        $extVersion = $this->general->getExtensionVersion();
        $magVersion = $this->general->getMagentoVersion();

        return;
    }

    /**
     * Contact link for extension.
     *
     * @return string
     */
    public function getContactLink()
    {
        return self::MODULE_CONTACT_LINK;
    }

    /**
     * Support link for extension.
     *
     * @return string
     */
    public function getSupportLink()
    {
        return self::MODULE_SUPPORT_LINK;
    }
}
