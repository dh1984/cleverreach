<?php

class Conlabz_CrConnect_Model_Newsletter_Subscriber extends Mage_Newsletter_Model_Subscriber {

    const XML_PATH_LOGGED_CONFIRM_EMAIL_TEMPLATE = 'newsletter/subscription/confirm_logged_email_template';

    /**
     * Subscribes by email
     *
     * @param string $email
     * @throws Exception
     * @return int
     */
    public function subscribe($email, $groupId = 0) {

        if (Mage::helper("customer")->isLoggedIn()) {
            $customerSession = Mage::getSingleton('customer/session');
            $customer = $customerSession->getCustomer();
        } else {
            $customer = Mage::getModel("customer/customer")->setWebsiteId(Mage::app()->getWebsite()->getId())->loadByEmail($email);
            $customer->setEmail($email);
        }

        if (!$this->getId()) {
            $this->setSubscriberConfirmCode($this->randomSequence());
        }

        if (!$this->isSubscribed($groupId)) {
            if (Mage::getStoreConfig(self::XML_PATH_LOGGED_CONFIRM_EMAIL_TEMPLATE) == 1) {
                Mage::getModel("crconnect/subscriber")->formsSendActivationMail($customer, $groupId);
            } else {
                Mage::getModel("crconnect/subscriber")->subscribe($customer, $groupId);
            }
            return true;
        } else {

            return false;
        }
    }

    public function sendCleverReachConfirmationRequestEmail() {

        Mage::getModel("crconnect/subscriber")->formsSendActivationMail();
    }

    public function loadByCustomer(Mage_Customer_Model_Customer $customer) {
        $data = $this->getResource()->loadByCustomer($customer);
        $this->addData($data);
        if (!empty($data) && $customer->getId() && !$this->getCustomerId()) {
            $this->setCustomerId($customer->getId());
            //$this->setSubscriberConfirmCode($this->randomSequence());
            if ($this->getStatus() == self::STATUS_NOT_ACTIVE) {
                $this->setStatus($customer->getIsSubscribed() ? self::STATUS_SUBSCRIBED : self::STATUS_UNSUBSCRIBED);
            }
            $this->save();
        }
        return $this;
    }

    /*
     * Check If customer is subscribed
     */

    public function isSubscribed($groupId = 0) {

        return Mage::helper("crconnect")->getSubscriber($this->getEmail(), $groupId);
    }

}
