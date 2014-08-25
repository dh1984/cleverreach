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
class Conlabz_CrConnect_Helper_Data extends Mage_Core_Helper_Abstract
{
	
	const WSDL_PATH = "http://api.cleverreach.com/soap/interface_v5.1.php?wsdl";
	const API_KEY_CONFIG_PATH = "crroot/crconnect/api_key";
	const LIST_ID_CONFIG_PATH = "crroot/crconnect/list_id";
	const SYNC_ORDERS_CONFIG_PATH = "crroot/crconnect/sync_orders";
	const REACTIVATION_CONFIG_PATH = "crroot/crconnect/sync_orders_status";
	const GROUP_SEPARATION_CONFIG_PATH = "crroot/crconnect/auto_separate";
	
	const IS_SHOW_DEFAULT_SUBSCRIPTION_CONFIG_PATH = "crroot/crconnect/showgroup";
	
	/*
	*  Get Wsdl path
	*
	*  @return string - path
	*/
	public function getWsdl(){
		return self::WSDL_PATH;
	}

	/*
	*  Get Api Key
	*
	*  @return string - api key
	*/
	public function getApiKey(){
		return trim(Mage::getStoreConfig(self::API_KEY_CONFIG_PATH));
	}

	/*
	*  Get Default List Id
	*
	*  @return string - list Id
	*/
	public function getDefaultListId(){
		return trim(Mage::getStoreConfig(self::LIST_ID_CONFIG_PATH));
	}

	
	/*
	* Get Connection to CrConnect
	*/
	public function getSoapClient(){

		try{
			return new SoapClient( $this->getWsdl() , array("trace" => true));
	    }catch(Exception $e){
	    	return false;
	    }
	    
	}
	
	/*
	* Get user groups Keys
	*/
	public function getKeys($groupId = false){
	
		$newsletterConfig = unserialize(Mage::getStoreConfig('crroot/crconnect/groups_keys'));
		$keysArray = array();
	
		// Generate array of groupId=>key
		if (is_array($newsletterConfig)){
			foreach($newsletterConfig as $config){
				$keysArray[$config['magento']] = $config['crconnect'];
			}
		}
        
        
        if ($groupId !== false){
        	if (isset($keysArray[$groupId])){
        		return $keysArray[$groupId];
        	}else{
        		return false;
        	}
        }
        
        $this->_keys = $keysArray;      
		return $keysArray;
			
	}
	
	/*
	* Get user groups Keys
	*/
	public function getForms($groupId = false){
	
		$newsletterConfig = unserialize(Mage::getStoreConfig('crroot/crconnect/groups_keys'));
		$keysArray = array();
	
		// Generate array of groupId=>key
		if (is_array($newsletterConfig)){
			foreach($newsletterConfig as $config){
				$keysArray[$config['magento']] = $config['formid'];
			}
		}
        
        
        if ($groupId !== false){
        	if (isset($keysArray[$groupId])){
        		return $keysArray[$groupId];
        	}else{
        		return false;
        	}
        }
        
        $this->_keys = $keysArray;      
		return $keysArray;
			
	}

	
	/*
	* Get and check if user subscribed to group
	*
	* @param string - user email
	* @param int - group Id
	*
	* @return bool true|false
	*/
	public function getSubscriber($email, $groupId = 0){
	
		$this->getKeys();
		
		// Get general API Key
		$apiKey = Mage::getStoreConfig(self::API_KEY_CONFIG_PATH);
		
		//Get Api keys for group
		$listID = Mage::getStoreConfig(self::LIST_ID_CONFIG_PATH);
		if ($groupId > 0){
			if (isset($this->_keys[$groupId])){
				$listID = $this->_keys[$groupId];
			}
		}
		
		$client = $this->getSoapClient();
	    if ($client){
	    	$return = $client->groupGetDetails($apiKey, $listID);
	    	if($return->status == "SUCCESS"){
	    	
	    		$result = $client->receiverGetByEmail($apiKey, $listID, $email);
	    		if ($result->status == "SUCCESS" && $result->data->active){
	
					return true;
	        		
		    	}else{
			    	
		    		return false;			    	
		    	
	    		}	
	    
	   		}else{
		    
		    	return false;			    	
		    
	    	}
	    }
		                        
  	}
	public function setupList(){
	
		$apiKey = trim(Mage::getStoreConfig(self::API_KEY_CONFIG_PATH));
		$listID = trim(Mage::getStoreConfig(self::LIST_ID_CONFIG_PATH));
		$return = false;

      	$client = $this->getSoapClient();
		if (!$client){
			return false;
		}	

		Mage::log("CleverReach_CrConnect: setting up list $listID !");
		
		if($apiKey && $listID){

        	try {
	        
	    		$return = $client->groupGetDetails($apiKey, $listID);
	        	if($return->status == "SUCCESS"){
	        		$fields = array("firstname" => "firstname",
	        						"lastname" => "lastname",
	        						"street" => "street",
	        						"zip" => "zip",
	        						"city" => "city",
	        						"country" => "country",
	        						"salutation" => "salutation",
	        						"title" => "title",
	        						"company" => "company",
	        						"newsletter" => "newsletter",
	        						"group_id" => "group_id",
	        						"group_name" => "group_name",
	        						"gender" => "gender",
	        						"store" => "store");
	        		foreach($return->data->attributes as $a){
	        			if(in_array($a->key, $fields)){
	        				unset($fields[$a->key]);
	        			}
	        		}
	        		foreach($fields as $f){
	        			$return = $client->groupAttributeAdd($apiKey, $listID, $f, "text", "");
	        		}
	        	}
	        	
// 	        	$return = $client->adduserDefinedField($apiKey, $listID, "newsletter", "");
//         	   	$return = $client->adduserDefinedField($apiKey, $listID, "group_id", "");
//         	   	$return = $client->adduserDefinedField($apiKey, $listID, "group_name", "");
//         	   	$return = $client->adduserDefinedField($apiKey, $listID, "gender", "");
//         	   	$return = $client->adduserDefinedField($apiKey, $listID, "store", "");
        	} catch(Exception $e) {
                Mage::log("CleverReach_CrConnect: Error connecting to Server: ".$e->getMessage());
	        }
        }
        return $return;
	}
	
	public function prepareUserdata($customer, $custom_fields=false, $activate = false){
		
        $name 				= $customer->getFirstname() . " " . $customer->getLastname();
        $newEmail 			= $customer->getEmail();
        $subscribed 		= $customer->getIsSubscribed();
        $shippingAddress 	= false;
        
        if( $shippingAddress = $customer->getDefaultBillingAddress()){
        	$shippingAddress = $shippingAddress->getData();
        }
        
        if($group = Mage::getModel('customer/group')->load($customer->getGroupId())){
        	$group = $group->getData();
        }
        
        
        if($shippingAddress){
        
        	$crReceiver = array (
			  'email' => $newEmail,
			  'source' => 'MAGENTO'
			);
			
			$crReceiver["attributes"] = array(
        		0 => array("key" => "firstname", "value" => @$shippingAddress["firstname"]),
				1 => array("key" => "lastname", "value" => @$shippingAddress["lastname"]),
				2 => array("key" => "street", "value" => @$shippingAddress["street"]),
				3 => array("key" => "zip", "value" => @$shippingAddress["postcode"]),
				4 => array("key" => "city", "value" => @$shippingAddress["city"]),
				5 => array("key" => "country", "value" => @$shippingAddress["country_id"]),
				6 => array("key" => "salutation", "value" => @$shippingAddress["prefix"]),
				7 => array("key" => "title", "value" => @$shippingAddress["suffix"]),
				8 => array("key" => "company", "value" => @$shippingAddress["company"]));
		
		}else{
        	$crReceiver = array (
			  'email' => $newEmail,
        	  'source' => "MAGENTO"
        	);
        	$crReceiver["attributes"] = array(0 => array("key" => 'firstname', "value" => @$customer->getFirstname()),
        									1 => array("key" => 'lastname', "value" => @$customer->getLastname()),
        									2 => array("key" => 'salutation', "value" => @$customer->getPrefix()),
        									3 => array("key" => 'title', "value" => @$customer->getSuffix()));
        }
        
		if($activate){
/* 			$crReceiver['registered'] = time(); */
/* 			$crReceiver['activated'] = time(); */
		}
        
        array_push($crReceiver["attributes"], array("key" => 'group_id', "value" => @$group["customer_group_id"]));
		array_push($crReceiver["attributes"], array("key" => 'group_name', "value" => @$group["customer_group_code"]));
		array_push($crReceiver["attributes"], array("key" => 'gender', "value" => @$customer->getGender()));
		array_push($crReceiver["attributes"], array("key" => 'store', "value" => @Mage::getModel('customer/customer')->load($customer->getId())->getData("created_in")));

        if($custom_fields){
        	foreach($custom_fields as $key => $val){
        		array_push($crReceiver["attributes"], array("key" => $key, "value" => $val));
        	}
        }
        
		return $crReceiver;
	}
	
	/*
	 *  Sync Shop with CleverReach
	 */
	
	public function syncData(){
	
	
		$apiKey = trim(Mage::getStoreConfig(self::API_KEY_CONFIG_PATH));
		$listID = trim(Mage::getStoreConfig(self::LIST_ID_CONFIG_PATH));
		        
		$syncOrders = trim(Mage::getStoreConfig(self::SYNC_ORDERS_CONFIG_PATH));
		$syncOrderStatus = trim(Mage::getStoreConfig(self::REACTIVATION_CONFIG_PATH));
		$synced_users = 0;
		
		$groupKeys = $this->getKeys();
		
		$subscribers = Mage::getResourceModel('newsletter/subscriber_collection')
								->showStoreInfo()
								->showCustomerInfo()
								->useOnlySubscribed()
								->getData();
								
		$batch = false;
		
		$i=0;
		if($subscribers) foreach($subscribers as $subscriber){

			$userGroup = 0;
			
			$tmp["email"] = "";
			if(!$tmp["email"]){
				
				if (Mage::getStoreConfig(self::GROUP_SEPARATION_CONFIG_PATH)){
					if ($subscriber["subscriber_email"]){
						$systemCustomer = Mage::getModel("customer/customer")->setWebsiteId($subscriber['website_id'])->loadByEmail($subscriber["subscriber_email"]);	
						if ($systemCustomer->getId()){
							$userGroup = $systemCustomer->getGroupId();
						}
					}
				}
				$tmp["email"] = $subscriber["subscriber_email"];
				$tmp["source"] = "MAGENTO";
				$tmp["attributes"] = array(0 => array("key" => "firstname", "value" => @$subscriber["customer_firstname"]),
											1 => array("key" => "lastname", "value" => @$subscriber["customer_lastname"]),
											2 => array("key" => "newsletter", "value" => "1")
											);	
			
			}
			if($tmp["email"]){
				$batch[$subscriber["store_id"]][$userGroup][floor($i++/25)][] = $tmp; //max 25 per batch
			}
			
		}

		// ------------------------------

		try {
		
			set_time_limit(0);
			$client = $this->getSoapClient();
			
			if (!$client){
				return false;
			}
			
	    	$tmp = $client->clientGetDetails($apiKey);
			
			if($tmp->status == "SUCCESS"){
				$client_data = $tmp->data;
			}else{
				return false;
			}
				
			// send subscribers batch to CleverReach	
			if($batch){
				
				foreach($batch as $storeId => $groupBatch){
					
					$apiKey = trim(Mage::getStoreConfig(self::API_KEY_CONFIG_PATH, $storeId));
						
					// send for each group	
					foreach ($groupBatch as $groupId => $batchS){

						$listID = trim(Mage::getStoreConfig(self::LIST_ID_CONFIG_PATH, $storeId));
						if (isset($groupKeys[$groupId])){
							$listID = $groupKeys[$groupId];
						}
						   	
						foreach($batchS as $part){
					
							$tmp = $client->receiverAddBatch($apiKey, $listID, $part);
							if($tmp->status == "SUCCESS"){
								
								$synced_users += count($part);
							
							}else{
								
								return false;
							
							}
						}
					
					}
					
				}
				
			}
			
			//--------------------------------------------------------------
			
			$orderCollection = Mage::getModel('sales/order_item')->getCollection()->getAllIds();
		
			//Optional filters you might want to use - more available operations in method _getConditionSql in Varien_Data_Collection_Db. 
			//$orders->addFieldToFilter('total_paid',Array('gt'=>0)); //Amount paid larger than 0
			//$orders->addFieldToFilter('status',Array('eq'=>"complete"));  //Status is "processing"
			
			if($orderCollection) foreach($orderCollection as $lastOrderId) {

		        if ($lastOrderId){

		            $order = Mage::getModel('sales/order')->load($lastOrderId);
		            $customer = Mage::getModel('customer/customer')->load($order->getCustomerId());
	               	$email = $order->getCustomerEmail();
	               	
	               	if($email){
	             	
	             		if($customer->getEmail()){
	             			
	             			if(Mage::getModel('newsletter/subscriber')->loadByCustomer($customer)->isSubscribed()){
	             				
	             				$options = array("newsletter" => 1);
	             				$active = true;
	             			
	             			}else{
	             				
	             				$options = array("newsletter" => 0);
	             				$active = false;
	             			
	             			}
	             			
	               			$crReceiver = Mage::helper('crconnect')->prepareUserdata($customer, $options, true);
	             			$crReceiver['group_id'] = $customer->getGroupId();
	             		
	             		}else{
	               		
		             		$shippingAddress = $order->getBillingAddress()->getData();
		             		$deactivated = 0;
		             		if($shippingAddress){
		             		
						        $crReceiver = array (
								  'email' => $email,
								  //'registered' => strtotime($shippingAddress["created_at"]),
								  //'activated' => strtotime(@$shippingAddress["created_at"]),
								  'source' => 'MAGENTO',
								  'attributes' => array(0 => array("key" => "firstname", "value" => @$shippingAddress["firstname"]),
														1 => array("key" => "lastname", "value" => @$shippingAddress["lastname"]),
														2 => array("key" => "street", "value" => @$shippingAddress["street"]),
												        3 => array("key" => "zip", "value" => @$shippingAddress["zip"]),
												        4 => array("key" => "city", "value" => @$shippingAddress["city"]),
												        5 => array("key" => "country", "value" => @$shippingAddress["country"]),
												        6 => array("key" => "salutation", "value" => @$shippingAddress["salutation"]),
												        7 => array("key" => "title", "value" => @$shippingAddress["title"]),
								        				8 => array("key" => "company", "value" => @$shippingAddress["company"]))
								);
			             		if($group = Mage::getModel('customer/group')->load($customer->getGroupId())){
			             		
						        	$group = $group->getData();
						
						        	$crReceiver['group_id'] = $customer->getGroupId();
	             		
						        	$crReceiver["attributes"][9] = array("key" => 'group_id', "value" => @$group["customer_group_id"]);
									$crReceiver["attributes"][10] = array("key" => 'group_name', "value" => @$group["customer_group_code"]);
									$crReceiver["attributes"][11] = array("key" => 'gender', "value" => @$customer->getGender());
									$crReceiver["attributes"][12] = array("key" => 'store', "value" => @Mage::getModel('customer/customer')->load($customer->getId())->getData("created_in"));
						        
						        }
					        }
	             		
	             		}
	               	}
		        }

				$syncOrders = trim(Mage::getStoreConfig(self::SYNC_ORDERS_CONFIG_PATH, $order->getData('store_id')));
				$syncOrderStatus = trim(Mage::getStoreConfig(self::REACTIVATION_CONFIG_PATH, $order->getData('store_id')));
		
		        if($email and $lastOrderId and $syncOrders){
		        	
		        	if($crReceiver){
		        	
		                try {
			                
		                	$tmp = $crReceiver;
		                	$addTxt="keeping status";
								
							//if new users should be activated by default. do it
		                	if($syncOrderStatus){
		                	
		                		$tmp["deactivated"] = 0;
								$addTxt = "forced active"; 
							
							}
								
							$apiKey = trim(Mage::getStoreConfig('crroot/crconnect/api_key', $order->getData('store_id')));
							
							$listID = trim(Mage::getStoreConfig(self::LIST_ID_CONFIG_PATH, $order->getData('store_id')));
							if (Mage::getStoreConfig(self::GROUP_SEPARATION_CONFIG_PATH)){
								if (isset($crReceiver['group_id']) && isset($groupKeys[$crReceiver['group_id']])){
									$listID = $groupKeys[$crReceiver['group_id']];
								}
		       				}
		       				
							$return = $client->receiverAdd($apiKey, $listID, $tmp);
							if($return->status=="SUCCESS"){				
							
								Mage::log("CleverReach_CrConnect: subscribed ($addTxt) - ".$crReceiver["email"]);
								$synced_users++;
							
							}else{		
							
								if($return->statuscode=="50"){ //seems to exists allready, try update
									$return = $client->receiverUpdate($apiKey, $listID, $tmp);
									if(!$return->status=="SUCCESS"){				
										Mage::log("CleverReach_CrConnect: order insert error - ".$return->message);
									}else{
										Mage::log("CleverReach_CrConnect: resubscribed ($addTxt) - ".$crReceiver["email"]);
									}
								}else{
									Mage::log("CleverReach_CrConnect: error - ".$return->message);
								}
							
							}
		                } catch(Exception $e) {
			                    Mage::log("CleverReach_CrConnect: Error in SOAP call: ".$e->getMessage());
		                }
		            }
		            
		            /* ########################### */
		            
		        	$items = $order->getAllItems();
		        	if($items)foreach ($items as $item){
		        		$tmpItem = array();
		        		$tmpItem["order_id"] = $lastOrderId;
		        		$tmpItem["product"] = $item->getName();
		        		$tmpItem["product_id"] = $item->getSku();
		        		$tmpItem["price"] = $item->getPrice();
		        		$tmpItem["amount"] = (integer)$item->getQtyOrdered();
		        		$tmpItem["purchase_date"] = strtotime($order->getCreatedAt());
		        		$tmpItem["source"] = "MAGENTO Order";
		    			Mage::log($tmpItem);
		        		$tmp = $client->receiverAddOrder($apiKey, $listID, $crReceiver["email"], $tmpItem);
			            if($tmp->status!="SUCCESS"){						
							Mage::log("CleverReach_CrConnect: Error - ($email)".$tmp->message);
			               
			            }else{
			            	Mage::log("CleverReach_CrConnect: submitted: ".$tmpItem["order_id"]." - ".$tmpItem["product"]);
			            }
		        	}
		        }
				//--------------------------------------------------------------
				
				
			}
			return $synced_users;
			} catch(Exception $e) {
				var_dump($e->getMessage());
			}
			return $synced_users;
		
	}
	public function isShowDefaultGroup(){
	
		return Mage::getStoreConfig(self::IS_SHOW_DEFAULT_SUBSCRIPTION_CONFIG_PATH);
	
	}
	
}
