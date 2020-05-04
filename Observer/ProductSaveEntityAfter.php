<?php
namespace Burst\Chronos\Observer;

use Magento\Framework\Event\ObserverInterface;

class ProductSaveEntityAfter implements ObserverInterface
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
        $this->chronos_enabled_product_sync = $this->scopeConfig->getValue( 
            'chronos/chronos_entities/chronos_synchronize_products', 
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
            if ($this->chronos_enabled_product_sync  != false) {
                try {
                    $product= $observer->getProduct();
                    $external_id= $product->getId();
                    $name= $product->getName();
                    $sku= $product->getSku();
                    $price= floatval($product->getPrice()); 
                    $weight= floatval($product->getData('weight'));
                    $status= 1;
                    $company= 1;
                    $source= 1;
                    $json_data =json_encode($product->getData());
                    $data = [
                        'name'=>$name,
                        'external_id'=>$external_id,
                        'sku'=>$sku,
                        'price'=>$price,
                        "service"=> false,
                        'weight'=>$weight,
                        'status'=>$status,
                        'company'=>$company,
                        'source'=>$source,
                        'json_data'=>$json_data,
                        ];
                    $final_json_data= \json_encode($data,true);
                    $this->chronosApi->createOrUpdateProduct($external_id, $final_json_data);
                } catch (exception $e) {
                    $this->logger->addInfo('Chronos ProductSaveEntityAfter Main', ["Error"=>$e->getMessage()]);
                }
            }else{
                $this->logger->addInfo('Chronos ProductSaveEntityAfter Main', ["Error"=>'Product sync disabled']);
            }
        }
    }
}
