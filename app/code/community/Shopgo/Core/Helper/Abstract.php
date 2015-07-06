<?php
/**
 * ShopGo
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category    Shopgo
 * @package     Shopgo_Core
 * @copyright   Copyright (c) 2014 Shopgo. (http://www.shopgo.me)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


/**
 * Abstract helper class
 *
 * @category    Shopgo
 * @package     Shopgo_Core
 * @author      Ammar <ammar@shopgo.me>
 */
abstract class Shopgo_Core_Helper_Abstract extends Mage_Core_Helper_Abstract
{
    /**
     * Log file name
     *
     * @var string
     */
    protected $_logFile = 'shopgo.log';

    /**
     * Email fail message
     *
     * @var string
     */
    protected $_emailFailMessage = 'Could not send email';

    /**
     * Send email
     *
     * @param string $to
     * @param int|string $templateId
     * @param array $params
     * @param string|array $sender
     * @param string $name
     * @param int|null $storeId
     * @return bool
     */
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
            $this->log($this->_emailFailMessage);
            $result = false;
        }

        $translate->setTranslateInline(true);

        return $result;
    }

    /**
     * Set user messages
     *
     * @param string|array $message
     * @param string $type
     * @param string $sessionPath
     * @return bool
     */
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

    /**
     * Generate log
     *
     * @param string|array $logs
     * @param string $type
     * @param string $file
     * @return bool
     */
    public function log($logs, $type = 'system', $file = '')
    {
        if (!Mage::getStoreConfig('dev/log/active')
            || empty($logs)) {
            return;
        }

        if (empty($file)) {
            $file = $this->_logFile;
        }

        switch ($type) {
            case 'exception':
                if (!is_array($logs)) {
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

    /**
     * Generate system log
     *
     * @param string|array $logs
     * @param string $file
     * @return null
     */
    private function _systemLog($logs, $file)
    {
        if (is_string($logs)) {
            $logs = array(array('message' => $logs));
        }

        foreach ($logs as $log) {
            if (!isset($log['message'])) {
                continue;
            }

            $message = $log['message'];

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

    /**
     * Check whether Advanced Ifconfig module is enabled or not
     *
     * @return bool
     */
    public function isAdvIfconfigEnabled()
    {
        return Mage::helper('core')->isModuleEnabled('Shopgo_AdvIfconfig');
    }
}
