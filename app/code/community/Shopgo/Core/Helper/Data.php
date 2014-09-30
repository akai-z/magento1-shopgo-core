<?php

class Shopgo_Core_Helper_Data extends Shopgo_Core_Helper_Abstract
{
    protected $_shopgoLogFile = 'shopgo.log';
    protected $_shopgoEmailFailMessage = 'Could not send email';

    public function sendEmail($to, $templateId, $params = array(), $sender = 'general', $name = null, $storeId = null)
    {
        $mailTemplate = Mage::getModel('core/email_template');
        $translate = Mage::getSingleton('core/translate');
        $result = true;

        if (empty($storeView)) {
            $storeId = Mage::app()->getStore()->getId();
        }

        if (isset($params['subject'])) {
            $mailTemplate->setTemplateSubject($params['subject']);
        }

        $mailTemplate->sendTransactional(
            $templateId,
            $sender,
            $to,
            $name,
            $params,
            $storeId
        );

        if (!$mailTemplate->getSentSuccess()) {
            $this->log($this->_shopgoEmailFailMessage);
            $result = false;
        }

        $translate->setTranslateInline(true);

        return $result;
    }

    public function userMessage($message, $type, $sessionPath = 'core/session')
    {
        try {
            $session = Mage::getSingleton($sessionPath);

            if (is_array($message)) {
                if (!isset($message['text'])) {
                    return false;
                }

                if (isset($message['translate'])) {
                    $message = $this->__($message['text']);
                }
            }

            switch ($type) {
                case 'error':
                    $session->addError($message);
                    break;
                case 'success':
                    $session->addSuccess($message);
                    break;
                case 'notice':
                    $session->addNotice($message);
                    break;
            }
        } catch (Exception $e) {
            $this->log($e, 'exception');
            return false;
        }

        return true;
    }

    public function log($logs, $type = 'system', $file = '')
    {
        if (empty($logs)) {
            return;
        }

        if (empty($file)) {
            $file = $this->_shopgoLogFile;
        }

        switch ($type) {
            case 'exception':
                if (gettype($logs) != 'array') {
                    $logs = array($logs);
                }

                foreach ($logs as $log) {
                    if (!$log instanceof Exception) {
                        continue;
                    }

                    Mage::logException($log);
                }
                break;
            default:
                $this->_systemLog($logs, $file);
        }
    }

    private function _systemLog($logs, $file)
    {
        if (gettype($logs) == 'string') {
            $logs = array(array('message' => $logs));
        }

        foreach ($logs as $log) {
            if (!isset($log['message'])) {
                continue;
            }

            $message = gettype($log['message']) == 'array'
                ? print_r($log['message'], true)
                : $log['message'];

            $level = isset($log['level'])
                ? $log['level'] : null;

            if (!empty($log['file'])) {
                $file = $log['file'];
            }

            if (false === strpos($file, '.log')) {
                $file .= '.log';
            }

            $forceLog = isset($log['forceLog'])
                ? $log['forceLog'] : false;

            Mage::log($message, $level, $file, $forceLog);
        }
    }
}
