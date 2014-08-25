<?php

class Conlabz_CrConnect_Block_Adminhtml_Newsletter_Subscriber extends Mage_Adminhtml_Block_Newsletter_Subscriber
{

	protected $_successStatus = "SUCCESS";
	
	public function __construct(){
		$this->setTemplate('newsletter/subscriber/listcleverreach.phtml');
	}
	
	public function getSubscribersListData(){
	
		$apiKey = Mage::helper("crconnect")->getApiKey();
		$listID = Mage::helper("crconnect")->getDefaultListId();
		$client = Mage::helper('crconnect')->getSoapClient();
		
		$errorList = array();
		$listInformation = array();
		
		if($apiKey && $listID && $client){

			try {
		
				$tmp = $client->clientGetDetails($apiKey);
				if($tmp->status == $this->_successStatus){
					$listInformation['client'] = $tmp->data;
				}else{
					$errorList[] = Mage::helper("crconnect")->__("Your API key seems to be invalid. Please check it!");
				}
		
				$tmp = $client->groupGetStats($apiKey, $listID);
				if($tmp->status == $this->_successStatus){
					$listInformation['list'] = $tmp->data;
				}else{
					$errorList[] = Mage::helper("crconnect")->__("Your list ID seem to be wrong. Please correct it!");
				}
		
				$tmp = $client->groupGetDetails($apiKey, $listID);
				if($tmp->status == $this->_successStatus){
					$listInformation['list']->name = $tmp->data->name;
				}else{
					$errorList[] = Mage::helper("crconnect")->__("Your list ID seem to be wrong. Please correct it!");
				}
			
				$subscribers = Mage::getResourceModel('newsletter/subscriber_collection')
								->showStoreInfo()
								->showCustomerInfo()
								->useOnlySubscribed()
								->getData();
	
				$subscribers = count($subscribers);
				$listInformation['subscribers'] = $subscribers;	
			
			} catch(Exception $e) {
				$errorList[] = $e->getMessage();
			}

		}else{
			$errorList[] = Mage::helper("crconnect")->__("Please configure your Cleverreach settings.");
		}
		
		return array('error'=>$errorList, 'info'=>$listInformation);
	}
}
