<?php
class Conlabz_Crconnect_Adminhtml_CrconnectController extends Mage_Adminhtml_Controller_Action {

	/*
	 * Init controller action
	 */
    protected function _initAction() {


    }
    /*
     * Accounts list action
     */
    public function synchronizeAction() {

	    $syncedUsers = Mage::helper('crconnect')->syncData();
	    $result = array();
	    
	    if ($syncedUsers !== false){
	    	$result['error'] = false;
		    $result['message'] = Mage::helper('crconnect')->__("Synchronization successfull. %s users were transmitted.", $syncedUsers);
	    }else{
		    $result['error'] = true;
		    $result['message'] = Mage::helper('crconnect')->__("Error occured while synchronization process. Please try again later");
	    }
	    
	    $this->getResponse()->setBody(json_encode($result));
	    
    }
    
    protected function _isAllowed()
	{
    	return true;
	}
    
}