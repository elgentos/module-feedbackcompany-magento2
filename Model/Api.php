<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\TheFeedbackCompany\Model;

use Magmodules\TheFeedbackCompany\Helper\General as GeneralHelper;
use Magmodules\TheFeedbackCompany\Helper\Invitation as InvitationHelper;
use Magmodules\TheFeedbackCompany\Helper\Reviews as ReviewsHelper;
use Magento\Framework\HTTP\Adapter\Curl;
use Magento\Framework\Stdlib\DateTime;
use Magento\Sales\Model\Order;

class Api
{

    const FBC_TOKEN_URL = 'https://beoordelingen.feedbackcompany.nl/api/v1/oauth2/token?client_id=%s&client_secret=%s&grant_type=authorization_code';
    const FBC_REVIEWS_URL = 'https://beoordelingen.feedbackcompany.nl/api/v1/review/summary/';
    const FBC_POST_URL = 'https://connect.feedbackcompany.nl/feedback/';
    const DEFAULT_TIMEOUT = 15;

    /**
     * @var InvitationHelper
     */
    private $inv;

    /**
     * @var ReviewsHelper
     */
    private $rev;

    /**
     * @var Curl
     */
    private $curl;

    /**
     * @var GeneralHelper
     */
    private $general;

    /**
     * @var DateTime
     */
    private $date;

    /**
     * Api constructor.
     *
     * @param InvitationHelper $invHelper
     * @param ReviewsHelper    $revHelper
     * @param GeneralHelper    $generalHelper
     * @param Curl             $curl
     * @param DateTime         $dateTime
     */
    public function __construct(
        InvitationHelper $invHelper,
        ReviewsHelper $revHelper,
        GeneralHelper $generalHelper,
        Curl $curl,
        DateTime $dateTime
    ) {
        $this->inv = $invHelper;
        $this->rev = $revHelper;
        $this->general = $generalHelper;
        $this->curl = $curl;
        $this->date = $dateTime;
    }

    /**
     * Post invitation function.
     *
     * @param \Magento\Sales\Model\Order $order
     *
     * @return bool|string
     */
    public function sendInvitation(Order $order)
    {
        $storeId = $order->getStoreId();

        $config = $this->inv->getConfigData($storeId);
        if (empty($config)) {
            return false;
        }

        if ($order->getStatus() != $config['status']) {
            return false;
        }

        $dateDiff = (time() - $this->date->strToTime($order->getCreatedAt()));
        if ($dateDiff > $config['backlog']) {
            return false;
        }

        $request['action'] = $config['action'];
        $request['Chksum'] = $this->inv->getChecksum($order->getCustomerEmail());
        $request['orderNumber'] = $order->getIncrementId();
        $request['resendIfDouble'] = $config['resend'];
        $request['remindDelay'] = $config['remind_delay'];
        $request['delay'] = $config['delay'];
        $request['aanhef'] = $this->inv->getCustomerName($order);
        $request['user'] = $order->getCustomerEmail();
        $request['connector'] = $config['connector'];

        if ($config['product_reviews']) {
            $productData = $this->inv->getProductData($order->getAllVisibleItems(), $storeId);
            $postData = array_merge($request, $productData);
        } else {
            $postData = $request;
        }

        $result = $this->postInvitation($postData, $config);

        return $result;
    }

    /**
     * @param $request
     * @param $config
     *
     * @return bool|string
     */
    public function postInvitation($request, $config)
    {
        $url = self::FBC_POST_URL . '?' . http_build_query($request);
        try {
            $curl = $this->curl;
            $curl->addOption(CURLOPT_POST, false);
            $curl->addOption(CURLOPT_URL, $url);
            $curl->addOption(CURLOPT_HEADER, true);
            $curl->addOption(CURLOPT_RETURNTRANSFER, 1);
            $curl->addOption(CURLOPT_CONNECTTIMEOUT, self::DEFAULT_TIMEOUT);
            $curl->connect($url);

            $response = $curl->read();
            $responseCode = $curl->getInfo(CURLINFO_HTTP_CODE);
            $headerSize = $curl->getInfo(CURLINFO_HEADER_SIZE);
            $body = substr($response, $headerSize);

            if (!empty($config['debug'])) {
                $debugMsg = 'TheFeedbackCompany - sendInvitation #' . $request['orderNumber'] . ' ';
                if ($responseCode == 500) {
                    $debugMsg .= '(status: ' . $responseCode . ', Request: ' . urldecode($url) . ')';
                } else {
                    $debugMsg .= '(status: ' . $responseCode . ', Body: ' . $body . ', Request: ' . urldecode($url) . ')';
                }
                $this->general->addTolog('Invite', $debugMsg);
            }
            if ($responseCode == 200) {
                return $body;
            }
        } catch (\Exception $e) {
            if (!empty($config['debug'])) {
                $debugMsg = 'TheFeedbackCompany - sendInvitation #' . $request['orderNumber'] . ' ';
                $debugMsg .= '(Error: ' . $e . ', Request: ' . urldecode($url) . ')';
                $this->general->addTolog('Invite', $debugMsg);
            }
        }

        return false;
    }

    /**
     * Get all review summary data.
     *
     * @param $type
     *
     * @return array
     */
    public function getReviews($type)
    {
        $oauthData = $this->rev->getUniqueOauthData();
        $result = [];
        foreach ($oauthData as $key => $data) {
            $reviewData = $this->updateReviewStats($data);
            if ($reviewData['status'] == 'token-error') {
                $data['client_token'] = '';
                $reviewData = $this->updateReviewStats($data);
            }
            $result[$key] = $reviewData;
        }
        $result = $this->rev->saveReviewResult($result, $type);

        return $result;
    }

    /**
     * Curl call for review summay data.
     *
     * @param $data
     *
     * @return array|bool|mixed
     */
    public function updateReviewStats($data)
    {
        if (empty($data['client_token'])) {
            $data['client_token'] = $this->getNewClientToken($data);
            if (empty($data['client_token'])) {
                $msg = __('Could not fetch new client token');
                return $this->general->createResponseError($msg);
            } else {
                $this->rev->setClientToken($data['client_token'], $data['store_id']);
            }
        }
        try {
            $url = self::FBC_REVIEWS_URL;
            $curl = $this->curl;
            $curl->addOption(CURLOPT_URL, $url);
            $curl->addOption(CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $data['client_token']]);
            $curl->addOption(CURLOPT_RETURNTRANSFER, 1);
            $curl->addOption(CURLOPT_SSL_VERIFYPEER, false);
            $curl->connect($url);
            $response = $curl->read();
            $responseCode = $curl->getInfo(CURLINFO_HTTP_CODE);
            $result = json_decode($response, true);

            if (!empty($result['error'])) {
                if ($result['error'] == 'No access') {
                    $this->rev->setClientToken('', $data['store_id']);
                    $msg = __('Could nog fetch new reviews, error: No access');

                    return $this->general->createResponseError($msg, 'token-error');
                }
            }
            if (!empty($result['success'])) {
                $result = [
                    'status'         => 'success',
                    'review_summary' => $result['data'][0]['review_summary'],
                    'shop'           => $result['data'][0]['shop']
                ];

                return $result;
            }

            $msg = __('Could not fetch new reviews, response code: ' . $responseCode);

            return $this->general->createResponseError($msg);
        } catch (\Exception $e) {
            $this->general->addTolog('Update Reviews', $e->getMessage());
            return $this->general->createResponseError($e);
        }
    }

    /**
     * Retrieve new client token.
     *
     * @param $data
     *
     * @return bool
     */
    public function getNewClientToken($data)
    {
        try {
            $url = sprintf(self::FBC_TOKEN_URL, $data['client_id'], $data['client_secret']);
            $curl = $this->curl;
            $curl->addOption(CURLOPT_URL, $url);
            $curl->addOption(CURLOPT_RETURNTRANSFER, 1);
            $curl->addOption(CURLOPT_CONNECTTIMEOUT, self::DEFAULT_TIMEOUT);
            $curl->connect($url);
            $response = json_decode($curl->read());
            if (!$response->error) {
                return $response->access_token;
            }
        } catch (\Exception $e) {
            $this->general->addTolog('ClientToken', $e->getMessage());
        }

        return false;
    }
}
