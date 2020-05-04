<?php
namespace Burst\Chronos\Observer;

use Magento\Framework\Event\ObserverInterface;

class SalesOrderInvoiceSaveAfter implements ObserverInterface
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
        \Burst\Chronos\Helper\ChronosApi $chronosApi
    ) {
        $this->chronosApi = $chronosApi;
        $this->_objectManager = $objectManager;
        $this->logger = $logger;
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
            try {
                //code...
            } catch (xception $e) {
                $this->logger->addInfo('Chronos SalesOrderInvoiceSaveAfter Main', ["Error"=>$e->getMessage()]);
            }
        }
    }
}