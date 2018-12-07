<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\TheFeedbackCompany\Helper;

use Magmodules\TheFeedbackCompany\Helper\General as GeneralHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\App\Cache\TypeListInterface;

class Reviews extends AbstractHelper
{

    const XML_PATH_REVIEWS_ENABLED = 'magmodules_thefeedbackcompany/reviews/enabled';
    const XML_PATH_REVIEWS_CLIENT_ID = 'magmodules_thefeedbackcompany/api/client_id';
    const XML_PATH_REVIEWS_CLIENT_SECRET = 'magmodules_thefeedbackcompany/api/client_secret';
    const XML_PATH_REVIEWS_CLIENT_TOKEN = 'magmodules_thefeedbackcompany/api/client_token';
    const XML_PATH_REVIEWS_RESULT = 'magmodules_thefeedbackcompany/reviews/result';
    const XML_PATH_REVIEWS_LAST_IMPORT = 'magmodules_thefeedbackcompany/reviews/last_import';
    const REVIEWS_URL = 'https://beoordelingen.feedbackcompany.nl/api/v1/review/all/';

    /**
     * @var DateTime
     */
    private $datetime;

    /**
     * @var TimezoneInterface
     */
    private $timezone;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var General
     */
    private $general;

    /**
     * @var TypeListInterface
     */
    private $cacheTypeList;

    /**
     * Reviews constructor.
     *
     * @param Context               $context
     * @param StoreManagerInterface $storeManager
     * @param DateTime              $datetime
     * @param TimezoneInterface     $timezone
     * @param General               $generalHelper
     * @param TypeListInterface     $cacheTypeList
     *
     * @internal param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        DateTime $datetime,
        TimezoneInterface $timezone,
        GeneralHelper $generalHelper,
        TypeListInterface $cacheTypeList
    ) {
        $this->datetime = $datetime;
        $this->timezone = $timezone;
        $this->storeManager = $storeManager;
        $this->general = $generalHelper;
        $this->cacheTypeList = $cacheTypeList;
        parent::__construct($context);
    }

    /**
     * Returns array of unique oauth data.
     *
     * @return array
     */
    public function getUniqueOauthData()
    {
        $oauthData = [];
        $stores = $this->storeManager->getStores();
        foreach ($stores as $store) {
            if ($oauth = $this->getOauthData($store->getId())) {
                $oauthData[$oauth['client_id']] = $oauth;
            }
        }

        return $oauthData;
    }

    /**
     * @param int  $storeId
     * @param null $websiteId
     *
     * @return array
     */
    public function getOauthData($storeId = 0, $websiteId = null)
    {
        $oauthData = [];

        if ($websiteId) {
            $enabled = $this->general->getWebsiteValue(self::XML_PATH_REVIEWS_ENABLED, $websiteId);
            $clientId = $this->general->getWebsiteValue(self::XML_PATH_REVIEWS_CLIENT_ID, $websiteId);
            $clientSecret = $this->general->getWebsiteValue(self::XML_PATH_REVIEWS_CLIENT_SECRET, $websiteId);
            $clientToken = $this->general->getWebsiteValue(self::XML_PATH_REVIEWS_CLIENT_TOKEN, $websiteId);
        } else {
            $enabled = $this->general->getStoreValue(self::XML_PATH_REVIEWS_ENABLED, $storeId);
            $clientId = $this->general->getStoreValue(self::XML_PATH_REVIEWS_CLIENT_ID, $storeId);
            $clientSecret = $this->general->getStoreValue(self::XML_PATH_REVIEWS_CLIENT_SECRET, $storeId);
            $clientToken = $this->general->getStoreValue(self::XML_PATH_REVIEWS_CLIENT_TOKEN, $storeId);
        }

        if ($enabled && $clientId && $clientSecret) {
            $oauthData = [
                'store_id'      => $storeId,
                'client_id'     => $clientId,
                'client_secret' => $clientSecret,
                'client_token'  => $clientToken
            ];
        }

        return $oauthData;
    }

    /**
     * Saves review result data a array by client_id.
     *
     * @param        $result
     * @param string $type
     *
     * @return array
     */
    public function saveReviewResult($result, $type = 'cron')
    {
        $fbcData = [];
        foreach ($result as $key => $row) {
            $status = $row['status'];
            if ($status == 'success') {
                $fbcData[$key]['status'] = $status;
                $fbcData[$key]['type'] = $type;
                $fbcData[$key]['name'] = $row['shop']['name'];
                $fbcData[$key]['link'] = $row['shop']['review_url'];
                $fbcData[$key]['total_reviews'] = $row['review_summary']['total_merchant_reviews'];
                $fbcData[$key]['score'] = number_format((float)$row['review_summary']['merchant_score'], 1, '.', '');
                $fbcData[$key]['score_max'] = $row['review_summary']['max_score'];
                $fbcData[$key]['percentage'] = ($row['review_summary']['merchant_score'] * 10) . '%';
            } else {
                $fbcData[$key]['status'] = $status;
                $fbcData[$key]['msg'] = $row['msg'];
            }
        }
        $updateMsg = $this->datetime->gmtDate() . ' (' . $type . ').';
        $this->general->setConfigData(json_encode($fbcData), self::XML_PATH_REVIEWS_RESULT);
        $this->general->setConfigData($updateMsg, self::XML_PATH_REVIEWS_LAST_IMPORT);

        return $fbcData;
    }

    /**
     * Unset all Client Tokens.
     * Function is called on before save in config, when client_id is changed.
     * All Client Tokens will be reset.
     */
    public function resetAllClientTokens()
    {
        $stores = $this->storeManager->getStores();
        foreach ($stores as $store) {
            $this->setClientToken('', $store->getId(), false);
        }
        $this->cacheTypeList->cleanType('config');
    }

    /**
     * Save client token to config.
     *
     * @param      $token
     * @param int  $storeId
     * @param bool $flushCache
     */
    public function setClientToken($token, $storeId, $flushCache = true)
    {
        $this->general->setConfigData($token, self::XML_PATH_REVIEWS_CLIENT_TOKEN, $storeId);
        if ($flushCache) {
            $this->cacheTypeList->cleanType('config');
        }
    }

    /**
     * Summay data getter for block usage.
     *
     * @param int  $storeId
     * @param null $websiteId
     *
     * @return mixed
     */
    public function getSummaryData($storeId = 0, $websiteId = null)
    {
        $data = $this->getAllSummaryData();
        if ($websiteId) {
            $clientId = $this->general->getWebsiteValue(self::XML_PATH_REVIEWS_CLIENT_ID, $websiteId);
        } else {
            $clientId = $this->general->getStoreValue(self::XML_PATH_REVIEWS_CLIENT_ID, $storeId);
        }

        if (!empty($clientId)) {
            if (!empty($data[$clientId]['status'])) {
                if ($data[$clientId]['status'] == 'success') {
                    return $data[$clientId];
                }
            }
        }

        return false;
    }

    /**
     * Array of all stored summay data.
     *
     * @return mixed
     */
    public function getAllSummaryData()
    {
        return json_decode($this->general->getStoreValue(self::XML_PATH_REVIEWS_RESULT), true);
    }

    /**
     * Last importdate.
     *
     * @return mixed
     */
    public function getLastImported()
    {
        $lastImported = $this->general->getStoreValue(self::XML_PATH_REVIEWS_LAST_IMPORT);

        return $lastImported;
    }
}
