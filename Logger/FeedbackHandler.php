<?php
/**
 * Copyright © 2017 Feedback Company. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace FeedbackCompany\TheFeedbackCompany\Logger;

use Monolog\Logger;
use Magento\Framework\Logger\Handler\Base;

class FeedbackHandler extends Base
{

    /**
     * @var int
     */
    protected $loggerType = Logger::DEBUG;

    /**
     * @var string
     */
    protected $fileName = '/var/log/feedbackcompany.log';
}
