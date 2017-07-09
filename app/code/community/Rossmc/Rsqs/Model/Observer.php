<?php
/**
 * @category    Rossmc
 * @package     Rossmc_Rsqs
 * @version     1.0.0
 */
class Rossmc_Rsqs_Model_Observer {

    /**
     * @param Varien_Event_Observer $observer
     */
    public function stripUrl($observer)
    {
        if ($this->_canStripUrl()) {
            $url = Mage::helper('core/url')->getCurrentUrl();
            if ((strpos($url, '?___store=')) || (strpos($url, '?___from_store='))) {
                $url = substr($url, 0, strpos($url, '?'));
                $observer->getEvent()->getControllerAction()->getResponse()->setRedirect($url);
            }
        }
    }

    /**
     * @param Varien_Event_Observer $observer
     */
    public function replaceUrl($observer)
    {
        $block = $observer->getBlock();
        if ($block instanceof Mage_Page_Block_Switch && $this->_canStripUrl()) {
            $transport = $observer->getTransport();
            $html = $transport->getHtml();
            $html = $this->_removeStoreQueryString($html);
            $transport->setHtml($html);
        }
    }

    /**
     * @return bool
     */
    protected function _canStripUrl()
    {
        return !Mage::app()->getStore()->isAdmin()
        && Mage::helper('rossmc_rsqs')->removeStoreQueryString();
    }

    protected function _removeStoreQueryString($html)
    {
        // Remove ___store and __from_store from urls.
        // Check if only ___store is placed, or if only ___from_store is placed, or if both params are placed.
        // Params which don't belong to "store params" aren't changed
        $changed = preg_replace_callback(
            '/"(https?:\/\/.*)(?:\?)(?:___store=[\d\w]+|(?:&amp;)?___from_store=[\d\w]+)+((?:[^?=#"]*)=?(?:[^#"]*))"/',
            function($matches) {
                $queryString = '';
                if (isset($matches[2]) && $matches[2]) {
                    $queryString = '?' . htmlspecialchars(ltrim(htmlspecialchars_decode($matches[2]), '&'));
                }

                return '"' . $matches[1] . $queryString . '"';
            },
            $html
        );

        return $changed;
    }
}