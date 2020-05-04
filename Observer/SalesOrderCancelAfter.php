<?php
namespace Burst\Chronos\Observer;

use Magento\Framework\Event\ObserverInterface;

class SalesOrderCancelAfter implements ObserverInterface
{
    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;
    private $logger;
    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager, 
        \Psr\Log\LoggerInterface $logger,
        \Burst\Chronos\Helper\ChronosApi $chronosApi,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->chronosApi = $chronosApi;
        $this->_objectManager = $objectManager;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->chronos_enabled_order_sync = $this->scopeConfig->getValue( 
            'chronos/chronos_entities/chronos_synchronize_orders', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE 
        );
    }
    /**
     * customer register event handler
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->chronosApi->token != false) {
            if ($this->chronos_enabled_order_sync  != false) {
                try {
                    $order = $observer->getEvent()->getOrder();
                    $external_id = $order->getId();
                    $result=$this->chronosApi->cancelOrder($external_id);
                } catch (xception $e) {
                    $this->logger->addInfo('Chronos SalesOrderCancelAfter Main', ["Error"=>$e->getMessage()]);
                }
            }else{
                $this->logger->addInfo('Chronos SalesOrderCancelAfter Main', ["Error"=>'Order sync disabled']);
            }
        }
    }
}