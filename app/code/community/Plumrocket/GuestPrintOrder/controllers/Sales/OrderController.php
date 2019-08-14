<?php
/**
 * Plumrocket Inc.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the End-user License Agreement
 * that is available through the world-wide-web at this URL:
 * http://wiki.plumrocket.net/wiki/EULA
 * If you are unable to obtain it through the world-wide-web, please
 * send an email to support@plumrocket.com so we can send you a copy immediately.
 *
 * @package     Plumrocket_Guest_Print_Order
 * @copyright   Copyright (c) 2013 Plumrocket Inc. (http://www.plumrocket.com)
 * @license     http://wiki.plumrocket.net/wiki/EULA  End-user License Agreement
 */
?>

<?php

require_once(Mage::getModuleDir('controllers', 'Mage_Sales').DS.'OrderController.php');

class Plumrocket_GuestPrintOrder_Sales_OrderController extends Mage_Sales_OrderController
{

	public function preDispatch()
    {
        
        $action = $this->getRequest()->getActionName();
        if ($action == 'print'){
			return $this;
		}
		
        parent::preDispatch();
        $loginUrl = Mage::helper('customer')->getLoginUrl();

        if (!Mage::getSingleton('customer/session')->authenticate($this, $loginUrl)) {
            $this->setFlag('', self::FLAG_NO_DISPATCH, true);
        }
    }

	public function printAction()
    {
		if (Mage::helper('guestprintorder')->moduleEnabled()){
			if (!$this->_loadPrintValidOrder()) {
				return;
			}
		} else {
			if (!$this->_loadValidOrder()) {
				return;
			}
		}
        $this->loadLayout('print');
        $this->renderLayout();
    }
    
    protected function _loadPrintValidOrder($orderId = null)
    {
        if (null === $orderId) {
            $orderId = (int) $this->getRequest()->getParam('order_id');
        }
        if (!$orderId) {
            $this->_forward('noRoute');
            return false;
        }

        $order = Mage::getModel('sales/order')->load($orderId);

        if ($this->_canPrintOrder($order)) {
            Mage::register('current_order', $order);
            return true;
        } else {
            $this->_redirect('*/*/history');
        }
        return false;
    }
    
    
    protected function _canPrintOrder($order)
    {
        $availableStates = Mage::getSingleton('sales/order_config')->getVisibleOnFrontStates();
        
        if ($order->getId() && in_array($order->getState(), $availableStates, $strict = true)){
			if ($order->getCustomerId()){
				$customerId = Mage::getSingleton('customer/session')->getCustomerId();
				
				return ($order->getCustomerId() == $customerId);
				
			} else {
				$remoteIP	= Mage::helper('core/http')->getRemoteAddr();
				$time		= Mage::getModel('core/date')->timestamp() - 86400;

				return ($order->getRemoteIP() == $remoteIP && $order->getCreatedAt() > date('Y-m-d H:i:s', $time));
			}
		}

        return false;
    }
}
