<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\TheFeedbackCompany\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\App\ProductMetadataInterface;

class General extends AbstractHelper
{

    const MODULE_CODE = 'Magmodules_TheFeedbackCompany';
    const XML_PATH_EXTENSION_ENABLED = 'magmodules_thefeedbackcompany/general/enabled';

    private $moduleList;
    private $metadata;
    private $storeManager;
    private $objectManager;
    private $config;

    /**
     * General constructor.
     *
     * @param Context                  $context
     * @param ObjectManagerInterface   $objectManager
     * @param StoreManagerInterface    $storeManager
     * @param ModuleListInterface      $moduleList
     * @param ProductMetadataInterface $metadata
     * @param Config                   $config
     */
    public function __construct(
        Context $context,
        ObjectManagerInterface $objectManager,
        StoreManagerInterface $storeManager,
        ModuleListInterface $moduleList,
        ProductMetadataInterface $metadata,
        Config $config
    ) {
        $this->objectManager = $objectManager;
        $this->storeManager = $storeManager;
        $this->moduleList = $moduleList;
        $this->metadata = $metadata;
        $this->config = $config;
        parent::__construct($context);
    }

    /**
     * General check if Extension is enabled.
     *
     * @return mixed
     */
    public function getEnabled()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_EXTENSION_ENABLED);
    }

    /**
     * Get Configuration data.
     *
     * @param          $path
     * @param int|null $storeId
     *
     * @return mixed
     * @internal param $scope
     */
    public function getStoreValue($path, $storeId = 0)
    {
        if ($storeId > 0) {
            return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId);
        } else {
            return $this->scopeConfig->getValue($path);
        }
    }

    /**
     * @param $path
     * @param $websiteId
     *
     * @return mixed
     */
    public function getWebsiteValue($path, $websiteId)
    {
        return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_WEBSITE, $websiteId);
    }

    /**
     * Set configuration data function.
     *
     * @param      $value
     * @param      $key
     * @param null $storeId
     */
    public function setConfigData($value, $key, $storeId = null)
    {
        if ($storeId) {
            $this->config->saveConfig($key, $value, 'stores', $storeId);
        } else {
            $this->config->saveConfig($key, $value, 'default', 0);
        }
    }

    /**
     * Create error response array for usage in config (manual import).
     *
     * @param        $msg
     * @param string $status
     *
     * @return array
     */
    public function createResponseError($msg, $status = 'error')
    {
        $response = ['status' => $status, 'msg' => $msg];

        return $response;
    }

    /**
     * Returns current version of the extension.
     *
     * @return mixed
     */
    public function getExtensionVersion()
    {
        $moduleInfo = $this->moduleList->getOne(self::MODULE_CODE);

        return $moduleInfo['setup_version'];
    }

    /**
     * Returns current version of Magento.
     *
     * @return string
     */
    public function getMagentoVersion()
    {
        return $this->metadata->getVersion();
    }
}
