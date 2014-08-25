<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_CatalogIndex
 * @copyright   Copyright (c) 2009 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


/**
 * Event observer and indexer running application
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Conlabz_CrConnect_Model_Observer extends Mage_Core_Model_Abstract
{

	public function initCr(){
		$session = Mage::getSingleton('adminhtml/session');
		
		$data = Mage::helper('crconnect')->setupList();
		
		if(!$data){
			$session->addError("Could not CrConnect to Cleverreach.");
		}else if($data->status=="ERROR" && $data->statuscode!=50){
			$session->addError("Cleverreach connection Error: ".$data->message);
		}
		
		
	}
	public function configSave(){
	
		$postValues = Mage::app()->getRequest()->getPost();
		if (Mage::app()->getRequest()->getParam('section') == "newsletter" || Mage::app()->getRequest()->getParam('section') == "crroot"){
			$store = Mage::app()->getRequest()->getParam('store');
			
			$isConfirmationEnabled = Mage::getStoreConfig("newsletter/subscription/confirm_logged_email_template", $store);
			if ($isConfirmationEnabled){
				
				$forms = Mage::helper("crconnect")->getForms();
				$formKey = Mage::getStoreConfig('crroot/crconnect/form_id');
				$allow = true;
				if (!$formKey){
					$allow = false;
				}
				
				
				$newsletterConfig = unserialize(Mage::getStoreConfig('crroot/crconnect/groups_keys'));
				if (is_array($newsletterConfig)){
					foreach($newsletterConfig as $config){
						if (!$config['formid']){
							$allow = false;
						}
					}
				}
				
				if (!$allow){
				
					Mage::getSingleton('adminhtml/session')->addError(Mage::helper('catalog')->__('Double Opt-In is enabled, please set Form(s) ID in Cleerreach settings'));
				
				}
				
			}
			
		}
	
	}
	public function check_subscription_status(){
    
    
    }
}
