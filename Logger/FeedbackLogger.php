<?php
/**
 * Copyright © 2017 Feedback Company. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace FeedbackCompany\TheFeedbackCompany\Logger;

use Monolog\Logger;

class FeedbackLogger extends Logger
{

    /**
     * @param $type
     * @param $data
     */
    public function add($type, $data)
    {
        if (is_array($data)) {
            $this->addInfo($type . ': ' . json_encode($data));
        } elseif (is_object($data)) {
            $this->addInfo($type . ': ' . json_encode($data));
        } else {
            $this->addInfo($type . ': ' . $data);
        }
    }
}
