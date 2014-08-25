<?php

include "Mage/Newsletter/controllers/ManageController.php";

class Conlabz_CrConnect_ManageController extends Mage_Newsletter_ManageController {

    const XML_PATH_LOGGED_CONFIRM_EMAIL_TEMPLATE = 'newsletter/subscription/confirm_logged_email_template';

    public function indexAction() {

        $email = Mage::getSingleton('customer/session')->getCustomer()->getEmail();

        $apiKey = trim(Mage::getStoreConfig('crroot/crconnect/api_key'));
        $listID = trim(Mage::getStoreConfig('crroot/crconnect/list_id'));

        if ($apiKey && $listID && $email) {
            try {
                $client = Mage::helper('crconnect')->getSoapClient();
            } catch (Exception $e) {
                Mage::log("CleverReach_CrConnect: Error connecting to Server: " . $e->getMessage());
            }

            try {

                if ($client) {

                    //get CR status
                    $result = $client->receiverGetByEmail($apiKey, $listID, $email);
                    $subscriber = Mage::getModel('newsletter/subscriber')->loadByEmail($email);
                    $subscriber->setEmail($email);
                
                    
                    if ($result->status == "SUCCESS") {
                        //user is active in CR but not here, sync it,
                        if ($result->data->active && !$subscriber->isSubscribed()) {
                            $subscriber->subscribe($email);
                            Mage::log("CleverReach_CrConnect: out of sync. activating user");

                            //user is inactive in CR but not here, sync it,
                        } else if (!$result->data->active && $subscriber->isSubscribed()) {

                            $unsubscribe = true;
                            // Get keys for different user groups
                            if (Mage::getStoreConfig('crroot/crconnect/showgroup') == '1') {
                                $groupKeys = Mage::helper('crconnect')->getKeys();
                                if ($groupId = Mage::getSingleton('customer/session')->getCustomerGroupId()) {
                                    if (isset($groupKeys[$groupId])) {
                                        $result = $client->receiverGetByEmail($apiKey, $groupKeys[$groupId], $email);
                                        if ($result->data->active) {
                                            $unsubscribe = false;
                                        }
                                    }
                                }
                            }
                            if ($unsubscribe) {
                                $collection = $subscriber->unsubscribe();
                            }

                            Mage::log("CleverReach_CrConnect: out of sync. deactivating user");
                        }
                    }
                }
            } catch (Exception $e) {
                Mage::log("CleverReach_CrConnect: Error in SOAP call: " . $e->getMessage());
            }
        }
        parent::indexAction();
    }

    public function saveAction() {
        if (!$this->_validateFormKey()) {
            return $this->_redirect('customer/account/');
        }
        try {

            $subscriber = Mage::getModel("newsletter/subscriber")->loadByEmail(Mage::getSingleton('customer/session')->getCustomer()->getEmail());
            $subscriber->setEmail(Mage::getSingleton('customer/session')->getCustomer()->getEmail());
            
            if ((boolean) $this->getRequest()->getParam('is_subscribed', false)) {

                if (!$subscriber->isSubscribed()) {
                    $status = Mage::getModel("newsletter/subscriber")->subscribe(Mage::getSingleton('customer/session')->getCustomer()->getEmail());
                    if (Mage::getStoreConfig(self::XML_PATH_LOGGED_CONFIRM_EMAIL_TEMPLATE) == 1) {
                        Mage::getSingleton('customer/session')->addSuccess($this->__('Confirmation request has been sent.'));
                    } else {
                        Mage::getSingleton('customer/session')->addSuccess($this->__('Thank you for your subscription.'));
                    }
                }
            } else {
                if ($subscriber->isSubscribed()) {

                    $status = Mage::getModel("crconnect/subscriber")->unsubscribe();
                    Mage::getSingleton('customer/session')->addSuccess($this->__('The subscription has been removed.'));
                }
            }

            $groupId = Mage::getSingleton('customer/session')->getCustomerGroupId();

            if ($groupId > 1) {
                if ((boolean) $this->getRequest()->getParam('is_gsubscribed', false)) {

                    if (!$subscriber->isSubscribed($groupId)) {

                        $status = Mage::getModel("newsletter/subscriber")->subscribe(Mage::getSingleton('customer/session')->getCustomer()->getEmail(), $groupId);
                        if (Mage::getStoreConfig(self::XML_PATH_LOGGED_CONFIRM_EMAIL_TEMPLATE) == 1) {
                            Mage::getSingleton('customer/session')->addSuccess($this->__('Confirmation request has been sent.'));
                        } else {
                            Mage::getSingleton('customer/session')->addSuccess($this->__('Thank you for your subscription.'));
                        }
                    }
                } else {

                    if ($subscriber->isSubscribed($groupId)) {

                        $status = Mage::getModel("crconnect/subscriber")->unsubscribe(false, $groupId);
                        Mage::getSingleton('customer/session')->addSuccess($this->__('The subscription has been removed.'));
                    }
                }
            }
        } catch (Exception $e) {
            Mage::getSingleton('customer/session')->addError($e->getMessage());
            Mage::getSingleton('customer/session')->addError($this->__('An error occurred while saving your subscription.'));
        }
        $this->_redirect('customer/account/');
    }

}
