<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\TheFeedbackCompany\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\UrlInterface;
use Magmodules\TheFeedbackCompany\Helper\General as GeneralHelper;

class Invitation extends AbstractHelper
{

    const POST_ACTION = 'sendInvitation';
    const XML_PATH_INVITATION_ENABLED = 'magmodules_thefeedbackcompany/invitation/enabled';
    const XML_PATH_INVITATION_CONNECTOR = 'magmodules_thefeedbackcompany/invitation/connector';
    const XML_PATH_INVITATION_STATUS = 'magmodules_thefeedbackcompany/invitation/status';
    const XML_PATH_INVITATION_DELAY = 'magmodules_thefeedbackcompany/invitation/delay';
    const XML_PATH_INVITATION_REMIND_DELAY = 'magmodules_thefeedbackcompany/invitation/remind_delay';
    const XML_PATH_INVITATION_BACKLOG = 'magmodules_thefeedbackcompany/invitation/backlog';
    const XML_PATH_INVITATION_RESEND = 'magmodules_thefeedbackcompany/invitation/resend';
    const XML_PATH_INVITATION_PREVIEWS = 'magmodules_thefeedbackcompany/invitation/product_reviews';
    const XML_PATH_INVITATION_DEBUG = 'magmodules_thefeedbackcompany/invitation/debug';

    /**
     * @var ProductFactory
     */
    private $productFactory;

    /**
     * @var General
     */
    private $general;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * Invitation constructor.
     *
     * @param Context               $context
     * @param ProductFactory        $productFactory
     * @param StoreManagerInterface $storeManager
     * @param General               $generalHelper
     */
    public function __construct(
        Context $context,
        ProductFactory $productFactory,
        StoreManagerInterface $storeManager,
        GeneralHelper $generalHelper
    ) {
        $this->productFactory = $productFactory;
        $this->general = $generalHelper;
        $this->storeManager = $storeManager;
        parent::__construct($context);
    }

    /**
     * Create array of invitation config data.
     *
     * @param $storeId
     *
     * @return array|bool
     */
    public function getConfigData($storeId)
    {
        if ($this->getEnabledInvitation($storeId)) {
            $config = [];
            $config['connector'] = $this->general->getStoreValue(self::XML_PATH_INVITATION_CONNECTOR, $storeId);
            $config['status'] = $this->general->getStoreValue(self::XML_PATH_INVITATION_STATUS, $storeId);
            $config['delay'] = $this->general->getStoreValue(self::XML_PATH_INVITATION_DELAY, $storeId);
            $config['remind_delay'] = $this->general->getStoreValue(self::XML_PATH_INVITATION_REMIND_DELAY, $storeId);
            $config['resend'] = $this->general->getStoreValue(self::XML_PATH_INVITATION_RESEND, $storeId);
            $config['backlog'] = ($this->general->getStoreValue(self::XML_PATH_INVITATION_BACKLOG, $storeId) * 86400);
            $config['product_reviews'] = $this->general->getStoreValue(self::XML_PATH_INVITATION_PREVIEWS, $storeId);
            $config['debug'] = $this->general->getStoreValue(self::XML_PATH_INVITATION_DEBUG, $storeId);
            $config['action'] = self::POST_ACTION;
            if (empty($config['backlog'])) {
                $config['backlog'] = (30 * 86400);
            }

            return $config;
        }

        return false;
    }

    /**
     * Check if Invitation is enabled on store level.
     *
     * @param $storeId
     *
     * @return bool|mixed
     */
    public function getEnabledInvitation($storeId)
    {
        if ($this->getEnabled()) {
            return $this->general->getStoreValue(self::XML_PATH_INVITATION_ENABLED, $storeId);
        }

        return true;
    }

    /**
     * Check if extension is enabled.
     *
     * @return mixed
     */
    public function getEnabled()
    {
        return $this->general->getEnabled();
    }

    /**
     * Create checksum of email string.
     *
     * @param $email
     *
     * @return int
     */
    public function getChecksum($email)
    {
        $checkSum = 0;
        $emailLenght = strlen($email);
        for ($i = 0; $emailLenght > $i; $i++) {
            $checkSum += ord($email[$i]);
        }

        return $checkSum;
    }

    /**
     * Create product data array.
     *
     * @param $products
     * @param $storeId
     *
     * @return array
     * @internal param $product_reviews
     */
    public function getProductData($products, $storeId)
    {
        $i = 1;
        $productData = [];
        foreach ($products as $item) {
            $this->storeManager->setCurrentStore($storeId);
            /** @var \Magento\Catalog\Model\Product $product */
            $product = $this->productFactory->create()->setStoreId($storeId)->load($item->getProductId());
            $productData['filtercode'][] = trim($product->getSku());
            if ($product->getStatus() == '1') {
                $productData['product_url[' . $i . ']'] = $product->getProductUrl();
                $productData['product_text[' . $i . ']'] = $item->getName();
                $productData['product_ids[' . $i . ']'] = 'SKU=' . $product->getSku();
                $productData['product_photo[' . $i . ']'] = $this->getProductImage($product, $storeId);
                $i++;
            }
        }

        return $productData;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @param                                $storeId
     *
     * @return string
     */
    public function getProductImage($product, $storeId)
    {
        $url = $this->storeManager->getStore($storeId)->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
        $image = $product->getImage();
        if ($image && $image != 'no_selection') {
            return $url . 'catalog/product' . $image;
        }

        return '';
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     *
     * @return mixed
     */
    public function getCustomerName($order)
    {
        if ($order->getCustomerId()) {
            return $order->getCustomerName();
        }

        $firstname = $order->getBillingAddress()->getFirstname();
        $middlename = $order->getBillingAddress()->getMiddlename();
        $lastname = $order->getBillingAddress()->getLastname();

        if (!empty($middlename)) {
            return $firstname . ' ' . $middlename . ' ' . $lastname;
        } else {
            return $firstname . ' ' . $lastname;
        }
    }
}
