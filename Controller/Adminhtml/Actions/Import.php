<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\TheFeedbackCompany\Controller\Adminhtml\Actions;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magmodules\TheFeedbackCompany\Helper\Reviews as ReviewsHelper;
use Magmodules\TheFeedbackCompany\Model\Api as ApiModel;

class Import extends Action
{

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var ApiModel
     */
    private $apiModel;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    private $request;

    /**
     * @var ReviewsHelper
     */
    private $rev;

    /**
     * @var TypeListInterface
     */
    private $cacheTypeList;

    /**
     * Import constructor.
     *
     * @param Context           $context
     * @param ApiModel          $apiModel
     * @param TypeListInterface $cacheTypeList
     * @param JsonFactory       $resultJsonFactory
     * @param ReviewsHelper     $revHelper
     */
    public function __construct(
        Context $context,
        ApiModel $apiModel,
        TypeListInterface $cacheTypeList,
        JsonFactory $resultJsonFactory,
        ReviewsHelper $revHelper
    ) {
        $this->rev = $revHelper;
        $this->apiModel = $apiModel;
        $this->cacheTypeList = $cacheTypeList;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->request = $context->getRequest();
        parent::__construct($context);
    }

    /**
     * @return $this
     */
    public function execute()
    {
        $msg = [];
        $imports = $this->apiModel->getReviews('manual');

        if (!empty($imports)) {
            foreach ($imports as $key => $data) {
                if ($data['status'] == 'success') {
                    $return_msg = __(
                        '%1: Score %2/%3 with %4 reviews',
                        $data['name'],
                        $data['score'],
                        $data['score_max'],
                        $data['total_reviews']
                    );
                    $msg[$key] = '<span class="fbc-success-import">' . $return_msg . '</span>';
                }
                if ($data['status'] == 'error') {
                    $return_msg = __('Client ID: %1<br> %2', $key, $data['msg']);
                    $msg[$key] = '<span class="fbc-error-import">' . $return_msg . '</span>';
                }
            }
        } else {
            $return_msg = __('Empty result');
            $msg[] = '<span class="fbc-error-import">' . $return_msg . '</span>';
        }

        $storeId = $this->request->getParam('store');
        $websiteId = $this->request->getParam('website');
        $displayMsg = '';
        if ($storeId || $websiteId) {
            $oauthData = $this->rev->getOauthData($storeId, $websiteId);
            if (!empty($oauthData['client_id'])) {
                if (!empty($msg[$oauthData['client_id']])) {
                    $displayMsg = $msg[$oauthData['client_id']];
                }
            } else {
                $return_msg = __('No updates found for this storeview');
                $displayMsg = '<span class="fbc-error-import">' . $return_msg . '</span>';
            }
        } else {
            $displayMsg = implode($msg);
        }

        $this->cacheTypeList->cleanType('config');

        $result = $this->resultJsonFactory->create();

        return $result->setData(['success' => true, 'msg' => $displayMsg]);
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magmodules_TheFeedbackCompany::config');
    }
}
