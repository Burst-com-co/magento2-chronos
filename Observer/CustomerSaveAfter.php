<?php
namespace Burst\Chronos\Observer;

use Magento\Framework\Event\ObserverInterface;

class CustomerSaveAfter implements ObserverInterface
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
        $this->chronos_enabled_customer_sync = $this->scopeConfig->getValue( 
            'chronos/chronos_entities/chronos_synchronize_customers', 
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
        if ($this->chronosApi->token  != false) {
            if ($this->chronos_enabled_customer_sync  != false) {
                try {
                    $event = $observer->getEvent();
                    $customer = $event->getCustomer();
                    $external_id = $customer->getId();
                    $email = $customer->getEmail();
                    $firstname = $customer->getFirstname();
                    $lastname = $customer->getLastname();
                    $document_number = $customer->getData('cedula');
                    $company= 1;
                    $source= 1;
                    $json_data =json_encode($customer->getData());
                    $data = [
                        'external_id'=>$external_id,
                        'email'=>$email,
                        'firstname'=>$firstname,
                        "lastname"=> $lastname,
                        'company'=>$company,
                        'source'=>$source,
                        // 'document_number'=>(int)$document_number,
                        'json_data'=>$json_data,
                    ];
                    $final_json_data= \json_encode($data,true);
                    $this->chronosApi->createOrUpdateCustomer($external_id, $final_json_data);
                } catch (exception $e) {
                    $this->logger->addInfo('Chronos CustomerSaveAfter Main', ["Error"=>$e->getMessage()]);
                }
            }else{
                $this->logger->addInfo('Chronos CustomerSaveAfter Main', ["Error"=>'Customer sync disabled']);
            }
        }     
    }
}