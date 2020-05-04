<?php
namespace Burst\Chronos\Observer;

use Magento\Framework\Event\ObserverInterface;

class SalesPlaceAfter implements ObserverInterface
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
                    $creation_datetime= $order->getCreatedAt();
                    $canceled= false;
                    $json_data =json_encode($order->getData());
                    $status= 1;
                    $company= 1;
                    $customer= $order->getCustomerId();
                    $source= 1;
                    
                    $data = [
                        'external_id'=>$external_id,
                        'creation_datetime'=>$creation_datetime,
                        'canceled'=>$canceled,
                        "json_data"=> $json_data,
                        'status'=>$status,
                        'company'=>$company,
                        'customer'=>$customer,
                        'source'=>$source,
                    ];
                    $products=[];
                    foreach($order->getAllItems() as $item){
                        $product_data=$item->getData();
                        $price=$item->getPrice();
                        $product_id=$item->getProductId();
                        $tax_percent=$item->getTaxPercent();
                        $tax_amount=$item->getTaxAmount();
                        $discount_percent=$item->getDiscountPercent();
                        $discount_amount=$item->getDiscountAmount();
                        $qty=$item->getQtyOrdered();
                        $products[]=[
                            'price'=>$price ,
                            'tax_percent'=>$tax_percent ,
                            'tax_amount'=>$tax_amount,
                            'discount_percent'=>$discount_percent,
                            'discount_amount'=>$discount_amount ,
                            'qty'=>$qty,
                            'product_id'=>$product_id
                        ];
                    }
                    $final_json_data= \json_encode($data,true);
                    // $this->chronosApi->createOrUpdateOrder($external_id, $final_json_data, $products);
                } catch (xception $e) {
                    $this->logger->addInfo('Chronos SalesPlaceAfterObserver Main', ["Error"=>$e->getMessage()]);
                }
            }else{
                $this->logger->addInfo('Chronos SalesPlaceAfterObserver Main', ["Error"=>'Order sync disabled']);
            }
        }
    }
}