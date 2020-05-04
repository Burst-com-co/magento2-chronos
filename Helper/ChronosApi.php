<?php
namespace Burst\Chronos\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class ChronosApi extends AbstractHelper{
    /**
     * ChronoApi constructor
     *
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\HTTP\ZendClientFactory $httpClientFactory
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,  
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\HTTP\ZendClientFactory $httpClientFactory
    )
	{
		$this->logger = $logger;
        parent::__construct($context); 
        $this->scopeConfig = $scopeConfig;
        $this->httpClientFactory = $httpClientFactory;

        $this->chronos_enabled = $this->scopeConfig->getValue( 
            'chronos/general/chronos_enabled', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE 
        );
        $this->chronos_url   = 'https://chronos.burst.com.co/api/v1/';
        // $this->chronos_url = $this->scopeConfig->getValue( 
        //     'chronos/chronos_credentials/chronos_url', 
        //     \Magento\Store\Model\ScopeInterface::SCOPE_STORE 
        // );
        $this->chronos_user = $this->scopeConfig->getValue( 
            'chronos/chronos_credentials/chronos_user', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE 
        );
        $this->chronos_password = $this->scopeConfig->getValue( 
            'chronos/chronos_credentials/chronos_password', 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE 
        );
        if ($this->chronos_enabled) {
            if ($this->chronos_url !=null && $this->chronos_user != null && $this->chronos_password!=null) {
                $this->token = $this->getoken();
            }
            else{
                $this->token = false;
            }
        } else{
            $this->token = false;
        }
    }
    /**
     * Function to generate token in Chronos
     *
     * @return string
     */
    private function getoken()
    {
        $data=[
            "username"=> $this->chronos_user,
            "password"=> $this->chronos_password
        ];
        $client = $this->httpClientFactory->create();
        $client->setUri($this->chronos_url.'login/');
        $client->setMethod(\Zend_Http_Client::POST);
        $client->setHeaders(['Content-Type: application/json', 'Accept: application/json']);
        $client->setRawData(json_encode($data));
        try {
            $response = $client->request();
            $body = $response->getBody();
            $string = json_decode(json_encode($body),true);
            $token_data=json_decode($string);
            if (property_exists($token_data,'non_field_errors')) {
                return false;
            }else{
                return $token_data->token;
            }
        } catch (\Zend_Http_Client_Exception $e) {
            $this->logger->addInfo('Chronos Product save helper', ["error"=>$e->getMessage()]);
            return false;
        }
    }
    /**
     * ----------------------------- PRODUCTS ------------------------------------------
     */
    
    /**
     * This function validates whether the product exists o not, 
     * if it exists it updates it, otherwise it creates it 
     *
     * @param string $product_id
     * @param json $json_data
     * @return product_data or false
     */
    public function createOrUpdateProduct($product_id, $json_data)
    {
	        $product = $this->getProductById($product_id);
            if (property_exists($product,'detail')) {
                # To create
                $product_data = $this->createProduct($json_data);
            } else {
                # To update
                $product_data = $this->updateProduct($product_id,$json_data);
            }
    }
    /**
     * This function search a product by specifc external_id
     *
     * @param string $product_id
     * @return product_data or salse
     */
    public function getProductById($product_id)
    {
        $client = $this->httpClientFactory->create();
        $client->setUri($this->chronos_url."products/$product_id/detail/");
        $client->setMethod(\Zend_Http_Client::GET);
        $client->setHeaders([
            'Content-Type: application/json', 
            'Accept: application/json',
            "Authorization: Token $this->token"
        ]);
        try {
            $response = $client->request();
            $body = $response->getBody();
            $string = json_decode(json_encode($body),true);
            $product_data=json_decode($string);
            
            return $product_data;
        } catch (\Zend_Http_Client_Exception $e) {
            $this->logger->addInfo('Chronos Product save helper', ["Get product error"=>$e->getMessage()]);
            return false;
        }
    }
    /**
     * This function create a nre product in Chronos
     *
     * @param json $json_data
     * @return product_data or false
     */
    public function createProduct($json_data)
    {
        $client = $this->httpClientFactory->create();
        $client->setUri($this->chronos_url.'products/create/');
        $client->setMethod(\Zend_Http_Client::POST);
        $client->setHeaders([
            'Content-Type: application/json', 
            'Accept: application/json',
            "Authorization: Token $this->token"
        ]);
        $client->setRawData($json_data);
        try {
            $response = $client->request();
            $body = $response->getBody();
            $string = json_decode(json_encode($body),true);
            $product_data=json_decode($string);
            return $product_data;
        } catch (\Zend_Http_Client_Exception $e) {
            $this->logger->addInfo('Chronos Product create helper', ["error"=>$e->getMessage()]);
            return false;
        }
    }
    /**
     * This function update an specifc product in Chronos
     *
     * @param string $product_id
     * @param json $json_data
     * @return product_data or false
     */
    public function updateProduct($product_id, $json_data)
    {
        $client = $this->httpClientFactory->create();
        $client->setUri($this->chronos_url."products/$product_id/update/");
        $client->setMethod(\Zend_Http_Client::PUT);
        $client->setHeaders([
            'Content-Type: application/json', 
            'Accept: application/json',
            "Authorization: Token $this->token"
        ]);
        $client->setRawData($json_data);
        try {
            $response = $client->request();
            $body = $response->getBody();
            $string = json_decode(json_encode($body),true);
            $product_data=json_decode($string);
            return $product_data;
        } catch (\Zend_Http_Client_Exception $e) {
            $this->logger->addInfo('Chronos Product update helper', ["error"=>$e->getMessage()]);
            return false;
        }
    }
    /**
     * ----------------------------- CUSTOMERS ------------------------------------------
     */
    /**
     * This function validates whether the customer exists o not, 
     * if it exists it updates it, otherwise it creates it 
     *
     * @param string $customer_id
     * @param json $json_data
     * @return customer_data or false
     */
    public function createOrUpdateCustomer($customer_id, $json_data)
    {
        $customer = $this->getCustomerById($customer_id);
        if (property_exists($customer,'detail')) {
            # To create
            $customer_data = $this->createCustomer($json_data);
        } else {
            # To update
            $customer_data = $this->updateCustomer($customer_id,$json_data);
        }
    }
    /**
     * This function search a customer by specifc external_id
     *
     * @param string $customer_id
     * @return customer_data or salse
     */
    public function getCustomerById($customer_id)
    {
        $client = $this->httpClientFactory->create();
        $client->setUri($this->chronos_url."customers/$customer_id/detail/");
        $client->setMethod(\Zend_Http_Client::GET);
        $client->setHeaders([
            'Content-Type: application/json', 
            'Accept: application/json',
            "Authorization: Token $this->token"
        ]);
        try {
            $response = $client->request();
            $body = $response->getBody();
            $string = json_decode(json_encode($body),true);
            $customer_data=json_decode($string);
            
            return $customer_data;
        } catch (\Zend_Http_Client_Exception $e) {
            $this->logger->addInfo('Chronos customer save helper', ["Get customer error"=>$e->getMessage()]);
            return false;
        }
    }
    /**
     * This function create a nre customer in Chronos
     *
     * @param json $json_data
     * @return customer_data or false
     */
    public function createCustomer($json_data)
    {
        $client = $this->httpClientFactory->create();
        $client->setUri($this->chronos_url.'customers/create/');
        $client->setMethod(\Zend_Http_Client::POST);
        $client->setHeaders([
            'Content-Type: application/json', 
            'Accept: application/json',
            "Authorization: Token $this->token"
        ]);
        $client->setRawData($json_data);
        try {
            $response = $client->request();
            $body = $response->getBody();
            $string = json_decode(json_encode($body),true);
            $customer_data=json_decode($string);
            return $customer_data;
        } catch (\Zend_Http_Client_Exception $e) {
            $this->logger->addInfo('Chronos customer create helper', ["error"=>$e->getMessage()]);
            return false;
        }
    }
    /**
     * This function update an specifc customer in Chronos
     *
     * @param string $customer_id
     * @param json $json_data
     * @return customer_data or false
     */
    public function updateCustomer($customer_id, $json_data)
    {
        $client = $this->httpClientFactory->create();
        $client->setUri($this->chronos_url."customers/$customer_id/update/");
        $client->setMethod(\Zend_Http_Client::PUT);
        $client->setHeaders([
            'Content-Type: application/json', 
            'Accept: application/json',
            "Authorization: Token $this->token"
        ]);
        $client->setRawData($json_data);
        try {
            $response = $client->request();
            $body = $response->getBody();
            $string = json_decode(json_encode($body),true);
            $customer_data=json_decode($string);
            return $customer_data;
        } catch (\Zend_Http_Client_Exception $e) {
            $this->logger->addInfo('Chronos customer update helper', ["error"=>$e->getMessage()]);
            return false;
        }
    }
    /**
     * ----------------------------- ORDERS ------------------------------------------
     */
    /**
     * This function validates whether the order exists o not, 
     * if it exists it updates it, otherwise it creates it 
     *
     * @param string $order_id
     * @param json $json_data
     * @return order_data or false
     */
    public function createOrUpdateOrder($order_id, $json_data, $products)
    {
        $order = $this->getOrderById($order_id);
        if (property_exists($order,'detail')) {
            # To create
            $order_data = $this->createOrder($json_data);
            $x=0;
            foreach ($products as $product) {
                $chronos_product = $this->getProductById($product['product_id']);
                unset($product['product_id']);
                $product['order']=$order_data->id;
                $product['product']=$chronos_product->id;
                $json_data_order_product=json_encode($product);
                $this->createOrderProduct($json_data_order_product);
                
            }
        } else {
            # To update
            $order_data = $this->updateOrder($order_id,$json_data);
        }
    }
    /**
     * This function search a order by specifc external_id
     *
     * @param string $order_id
     * @return order_data or salse
     */
    public function getOrderById($order_id)
    {
        $client = $this->httpClientFactory->create();
        $client->setUri($this->chronos_url."orders/$order_id/detail/");
        $client->setMethod(\Zend_Http_Client::GET);
        $client->setHeaders([
            'Content-Type: application/json', 
            'Accept: application/json',
            "Authorization: Token $this->token"
        ]);
        try {
            $response = $client->request();
            $body = $response->getBody();
            $string = json_decode(json_encode($body),true);
            $order_data=json_decode($string);
            
            return $order_data;
        } catch (\Zend_Http_Client_Exception $e) {
            $this->logger->addInfo('Chronos order save helper', ["Get order error"=>$e->getMessage()]);
            return false;
        }
    }
    /**
     * This function create a new order in Chronos
     *
     * @param json $json_data
     * @return order_data or false
     */
    public function createOrder($json_data)
    {
        $client = $this->httpClientFactory->create();
        $client->setUri($this->chronos_url.'orders/create/');
        $client->setMethod(\Zend_Http_Client::POST);
        $client->setHeaders([
            'Content-Type: application/json', 
            'Accept: application/json',
            "Authorization: Token $this->token"
        ]);
        $client->setRawData($json_data);
        try {
            $response = $client->request();
            $body = $response->getBody();
            $string = json_decode(json_encode($body),true);
            $order_data=json_decode($string);
            return $order_data;
        } catch (\Zend_Http_Client_Exception $e) {
            $this->logger->addInfo('Chronos order create helper', ["error"=>$e->getMessage()]);
            return false;
        }
    }
    /**
     * This function update an specifc order in Chronos
     *
     * @param string $order_id
     * @param json $json_data
     * @return order_data or false
     */
    public function updateOrder($order_id, $json_data)
    {
        $client = $this->httpClientFactory->create();
        $client->setUri($this->chronos_url."orders/$order_id/update/");
        $client->setMethod(\Zend_Http_Client::PUT);
        $client->setHeaders([
            'Content-Type: application/json', 
            'Accept: application/json',
            "Authorization: Token $this->token"
        ]);
        $client->setRawData($json_data);
        try {
            $response = $client->request();
            $body = $response->getBody();
            $string = json_decode(json_encode($body),true);
            $order_data=json_decode($string);
            return $order_data;
        } catch (\Zend_Http_Client_Exception $e) {
            $this->logger->addInfo('Chronos order update helper', ["error"=>$e->getMessage()]);
            return false;
        }
    }
    /**
     * This function update an specifc order in Chronos
     *
     * @param string $order_id
     * @param json $json_data
     * @return order_data or false
     */
    public function cancelOrder($external_id)
    {
        $data = array(
            'canceled' => true
        );
        $url = "https://chronos.burst.com.co/api/v1/orders/$external_id/update/";
        $curl = curl_init($url);
        $json_data=json_encode($data);
        curl_setopt($curl, CURLOPT_URL, $url);
        // Set options necessary for request.
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Authorization: Token 926c272af5f4ac15eb773502632312822af1e30c', 'Content-Type: application/json', 'Content-Length: ' . strlen($json_data)));
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
      
        curl_setopt($curl, CURLOPT_POSTFIELDS, $json_data);
      
        // Send request
        $response = curl_exec($curl);
        return $response;
    }
    /**
     * This function create a new order product in Chronos
     *
     * @param json $json_data
     * @return order_data or false
     */
    public function createOrderProduct($json_data)
    {
        $client = $this->httpClientFactory->create();
        $client->setUri($this->chronos_url.'order_products/create/');
        $client->setMethod(\Zend_Http_Client::POST);
        $client->setHeaders([
            'Content-Type: application/json', 
            'Accept: application/json',
            "Authorization: Token $this->token"
        ]);
        $client->setRawData($json_data);
        try {
            $response = $client->request();
            $body = $response->getBody();
            $string = json_decode(json_encode($body),true);
            $order_product_data=json_decode($string);
            return $order_product_data;
        } catch (\Zend_Http_Client_Exception $e) {
            $this->logger->addInfo('Chronos order create helper', ["error"=>$e->getMessage()]);
            return false;
        }
    }
}
