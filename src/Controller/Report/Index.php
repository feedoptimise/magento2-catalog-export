<?php

namespace Feedoptimise\CatalogExport\Controller\Report;

use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;

class Index extends \Magento\Framework\App\Action\Action
{
    /**
     * Framework Variables
     * @var \Magento\Framework\App\RequestInterface $requestInterface
     * @var \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $requestInterface;
    protected $resultJsonFactory;
    protected $storeManager;
    protected $currencyHelper;
    /**
     * Extension Variables
     * @var \Feedoptimise\CatalogExport\Helper\Settings $extensionSettings
     * @var \Feedoptimise\CatalogExport\Controller\Stores\Index $storeController
     * @var integer $storeId
     */
    protected $extensionSettings;
    protected $storeController;
    protected $storeId;

    protected $_orderCollectionFactory;

    public function __construct(
        // Framework Params
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\RequestInterface $requestInterface,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        // Extension Params
        \Feedoptimise\CatalogExport\Helper\Settings $extensionSettings,
        \Feedoptimise\CatalogExport\Helper\Currency $currencyHelper,
        \Feedoptimise\CatalogExport\Controller\Stores\Index $storeController,

        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
    )
    {
        // Framework Variables
        $this->requestInterface = $requestInterface;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->storeManager = $storeManager;

        // Extension Variables
        $this->extensionSettings = $extensionSettings;
        $this->currencyHelper = $currencyHelper;
        $this->storeController = $storeController;
        $this->_orderCollectionFactory = $orderCollectionFactory;
        return parent::__construct($context);
    }

    /**
     * View page action
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var array $request */
        $request = $this->requestInterface->getParams();

        try {
            if (isset($request['debug']) && $request['debug'] == 'true') {
                error_reporting(E_ALL);
                ini_set('display_errors', 1);

                register_shutdown_function("feedoptimise_fatal_handler_product");
            }
            /** @var \Magento\Framework\Controller\Result\Json $result */
            $result = $this->resultJsonFactory->create();
            if(($settingsError = $this->extensionSettings->validateSettings($request)) !== true)
            {
                return $result->setData($settingsError);
            }
            else if(($storeError = $this->storeController->checkStore(@$request['store_id'])) !== true)
            {
                return $result->setData($storeError);
            }
            else if(($settingsError = $this->extensionSettings->orderReportEnabled()) !== true)
            {
                return $result->setData($settingsError);
            }

            $fromDate = !empty($request['from'])? $request['from']: date('Y-m-d H:i:s', strtotime('-1 month'));
            $endDate  = !empty($request['to'])? $request['to']: date('Y-m-d H:i:s');
            $limit  = !empty($request['limit'])?$request['limit']: 50;
            $page  = !empty($request['page'])?$request['page']: 0;

            if ($fromDate && $endDate) {
                $orders = $this->getOrdersWithProducts($fromDate, $endDate, $page, $limit);

                return $result->setData([
                    'error' => false,
                    'code'  => 200,
                    'payload'=> $orders
                ]);
            }


        }catch (\Throwable $e) {
            $result = $this->resultJsonFactory->create();

            return $result->setData([
                'error' => true,
                'code' => 500,
                'memory' =>round((memory_get_usage() / 1024) / 1024,2).'M',
                'error_msg' => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            $result = $this->resultJsonFactory->create();

            return $result->setData([
                'error' => true,
                'code' => 500,
                'memory' =>round((memory_get_usage() / 1024) / 1024,2).'M',
                'error_msg' => $e->getMessage()
            ]);
        }
    }

    public function setStoreId($storeId)
    {
        $this->storeController->setStore($storeId);
        $this->storeId = (int)$storeId;
    }

    /**
     * Load paginated orders with all products between two dates.
     *
     * @param string   $fromDate Y-m-d H:i:s (UTC)
     * @param string   $endDate  Y-m-d H:i:s (UTC)
     * @param int      $page     Current page number (1-based)
     * @param int      $limit    Page size (orders per page)
     * @return array
     */
    private function getOrdersWithProducts(
        string $fromDate,
        string $endDate,
        int $page = 1,
        int $limit = 50
    ){
        $orders = $this->_orderCollectionFactory->create()
            ->addFieldToSelect('*')
            ->addFieldToFilter(
                'created_at',
                ['from' => $fromDate, 'to' => $endDate]
            )
            ->setPageSize($limit)
            ->setCurPage($page);

        $data = [];

        foreach ($orders as $order) {
            /** @var \Magento\Sales\Model\Order $order */
            $orderData = [
                'order_id'    => $order->getIncrementId(),
                'created_at'  => $order->getCreatedAt(),
                'status'      => $order->getStatus(),
                'grand_total' => $order->getGrandTotal(),
                'subtotal'    => $order->getSubtotal(),
                'shipping'    => $order->getShippingAmount(),
                'currency'    => $order->getOrderCurrencyCode(),
                'items'       => []
            ];

            foreach ($order->getAllVisibleItems() as $item) {
                /** @var \Magento\Sales\Model\Order\Item $item */

                $orderData['items'][] = [
                    'product_id'  => $item->getProductId(),
                    'sku'         => $item->getSku(),
                    'name'        => $item->getName(),
                    'qty_ordered' => (float) $item->getQtyOrdered(),
                    'qty_returned'=> (float) $item->getQtyReturned(),
                    'price'       => (float) $item->getPrice(),
                    'row_total'   => (float) $item->getRowTotal(),
                ];
            }

            $data[] = $orderData;
        }

        return [
            'page'       => $page,
            'limit'      => $limit,
            'total'      => (int) $orders->getSize(),   // total records across all pages
            'totalPages' => (int) ceil($orders->getSize() / $limit),
            'orders'     => $data,
        ];
    }
}

function feedoptimise_fatal_handler_product()
{
    echo json_encode(error_get_last(), JSON_PRETTY_PRINT);
    die;
}