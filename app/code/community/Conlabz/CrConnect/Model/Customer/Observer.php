<?php
/**
 * Conlabz GmbH
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com and you will be sent a copy immediately.
 *
 * @category   CleverReach
 * @package    Conlabz_CrConnect
 * @copyright  Copyright (c) 2012 Conlabz GmbH (http://conlabz.de)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class SubscriberCustomField
{
    function SubscriberCustomField($k, $v)
    {
        $this->Key = $k;
        $this->Value = $v;
    }
}

class Conlabz_CrConnect_Model_Customer_Observer
{
	public function session_init($observer)
    {   
    	
    	$mailingId = Mage::getSingleton('core/app')->getRequest()->getParam('crmailing');
		$cookie = Mage::getSingleton('core/cookie');
		if ($mailingId){
			$cookie->set('crmailing', $mailingId ,time()+3600*24*14,'/');
		}
    	$customerId = Mage::getSingleton('core/app')->getRequest()->getParam('crcustomer');
		$cookie = Mage::getSingleton('core/cookie');
		if ($customerId){
			$cookie->set('crcustomer', $customerId ,time()+3600*24*14,'/');
		}
    
    }
	
    public function customer_deleted($observer)
    {
    
        $event = $observer->getEvent();
        $customer = $event->getCustomer();
		$email = $customer->getEmail();
		$groupId = $customer->getGroupId();
		
		$status = Mage::getModel("crconnect/subscriber")->unsubscribe();
		$status = Mage::getModel("crconnect/subscriber")->unsubscribe(false, $groupId);
            		
    }
    
}