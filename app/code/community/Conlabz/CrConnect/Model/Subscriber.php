<?php
class Conlabz_CrConnect_Model_Subscriber extends Mage_Core_Model_Abstract{

	private $_client;
	private $_apiKey;
	private $_listID;
	private $_keys;
	
	public function init(){
	
      	if ($this->_client = Mage::helper('crconnect')->getSoapClient()){
      	
	      	$this->_apiKey = trim(Mage::getStoreConfig('crroot/crconnect/api_key'));
    	    $this->_listID = trim(Mage::getStoreConfig('crroot/crconnect/list_id'));
			$this->_formID = trim(Mage::getStoreConfig('crroot/crconnect/form_id'));
			$this->_keys = Mage::helper('crconnect')->getKeys();
			$this->_formKeys = Mage::helper('crconnect')->getForms();

       		return true;
       	}
		return false;
		
	}
	public function formsSendActivationMail($customer = false, $groupId = 0){
	
		if ($this->init()){
			
			if (!$customer){
				$customer = Mage::getSingleton('customer/session')->getCustomer();
			}
			
			$crReceiver = Mage::helper('crconnect')->prepareUserdata($customer, array('newsletter' => 1), false);

			$crReceiver["deactivated"] = 1;
			$addResult = $this->receiverAdd($crReceiver, $groupId);
			$doidata = array(
				 "user_ip" => $_SERVER['REMOTE_ADDR'],
   			     "user_agent" => $_SERVER['HTTP_USER_AGENT'],
        		 "referer" => Mage::getUrl("/"),
                 "postdata" => "", //just an example. any txt format will do.
                 "info" => "",
            );

			$formId = $this->_formID;
			if ($key = Mage::helper("crconnect")->getForms($groupId)){
				$formId = $key;
			}

			$result = $this->_client->formsSendActivationMail($this->_apiKey, $formId, $customer->getEmail(), $doidata);
 			
 			if ($result->status == "SUCCESS"){
 				return true;
 			}
 			
 			return false;
			
		}
	
	}
	public function subscribe($customer = false, $groupId = 0){
	
		if (!$this->init()){
			return false;
		}

		if (!$customer){
			$customer = Mage::getSingleton('customer/session')->getCustomer();
		}
		$crReceiver = Mage::helper('crconnect')->prepareUserdata($customer, array('newsletter' => 1), false);
		$addResult = $this->receiverAdd($crReceiver, $groupId);
		if ($addResult->status == "SUCCESS"){
 			return true;
 		}else{
 			$addResult = $this->receiverSetActive($customer->getEmail(), $groupId);
		}	
	
		return false;
	
	}
	public function unsubscribe($customer = false, $groupId = 0){
	
		if (!$this->init()){
			return false;
		}

		if (!$customer){
			$customer = Mage::getSingleton('customer/session')->getCustomer();
		}
		$removeResult = $this->receiverSetInactive($customer->getEmail(), $groupId);
		if ($removeResult->status == "SUCCESS"){
 			return true;
 		}	
	
		return false;
	
	}
	public function receiverAdd($customerData, $groupId = 0){
	
		$listKey = $this->_listID;
		if ($key = Mage::helper("crconnect")->getKeys($groupId)){
			$listKey = $key;
		}
		
		return $this->_client->receiverAdd($this->_apiKey, $listKey, $customerData);
	
	}
	public function receiverSetInactive($email, $groupId = 0){
	
		$listKey = $this->_listID;
		if ($key = Mage::helper("crconnect")->getKeys($groupId)){
			$listKey = $key;
		}
		
		return $this->_client->receiverSetInactive($this->_apiKey, $listKey, $email);
	
	}
	public function receiverSetActive($email, $groupId = 0){
	
		$listKey = $this->_listID;
		if ($key = Mage::helper("crconnect")->getKeys($groupId)){
			$listKey = $key;
		}
		
		return $this->_client->receiverSetActive($this->_apiKey, $listKey, $email);
	
	}

}