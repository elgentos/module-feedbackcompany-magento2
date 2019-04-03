<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace FeedbackCompany\Logger;

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
