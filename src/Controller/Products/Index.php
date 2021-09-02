<?php
/**
 * @author Feedoptimise
 * @copyright Copyright (c) 2020 Feedoptimise (http://www.feedoptimise.com)
 * @package Feedoptimise_CatalogExport
 */

namespace Feedoptimise\CatalogExport\Controller\Products;

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
	/**
	 * Extension Variables
	 * @var \Feedoptimise\CatalogExport\Helper\Settings $extensionSettings
	 * @var \Feedoptimise\CatalogExport\Controller\Stores\Index $storeController
	 * @var \Feedoptimise\CatalogExport\Controller\Product\Index $productController
	 * @var integer $storeId
	 */
	protected $extensionSettings;
	protected $storeController;
	protected $productController;
	protected $storeId;
	/**
	 * Product Searching Variables
	 * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
	 * @var \Magento\Catalog\Model\Product\Attribute\Source\Status $productStatus
	 * @var \Magento\Catalog\Model\Product\Visibility $productVisibility
	 */
	protected $productCollectionFactory;
	protected $productStatus;
	protected $productVisibility;

	/**
	 * Framework Params
	 * @param \Magento\Framework\App\Action\Context $context
	 * @param \Magento\Framework\App\RequestInterface $requestInterface
	 * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
	 * @param \Magento\Store\Model\StoreManagerInterface $storeManager
	 *
	 * Extension Params
	 * @param \Feedoptimise\CatalogExport\Helper\Settings $extensionSettings
	 * @param \Feedoptimise\CatalogExport\Controller\Stores\Index $storeController
	 * @param \Feedoptimise\CatalogExport\Controller\Product\Index $productController
	 *
	 * Product Searching Params
	 * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
	 * @param \Magento\Catalog\Model\Product\Attribute\Source\Status $productStatus
	 * @param \Magento\Catalog\Model\Product\Visibility $productVisibility
	 * @throws \Magento\Framework\Exception\LocalizedException
	 */
	public function __construct(
		// Framework Params
		\Magento\Framework\App\Action\Context $context,
		\Magento\Framework\App\RequestInterface $requestInterface,
		\Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
		\Magento\Store\Model\StoreManagerInterface $storeManager,
		// Extension Params
		\Feedoptimise\CatalogExport\Helper\Settings $extensionSettings,
		\Feedoptimise\CatalogExport\Controller\Stores\Index $storeController,
		\Feedoptimise\CatalogExport\Controller\Product\Index $productController,
		// Product Searching Params
		\Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
		\Magento\Catalog\Model\Product\Attribute\Source\Status $productStatus,
		\Magento\Catalog\Model\Product\Visibility $productVisibility
	)
	{
		// Framework Variables
		$this->requestInterface = $requestInterface;
		$this->resultJsonFactory = $resultJsonFactory;
		$this->storeManager = $storeManager;

		// Extension Variables
		$this->extensionSettings = $extensionSettings;
		$this->storeController = $storeController;
		$this->productController = $productController;

		// Product Searching Params
		$this->productCollectionFactory = $productCollectionFactory;
		$this->productStatus = $productStatus;
		$this->productVisibility = $productVisibility;

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
			if(isset($request['debug']) && $request['debug'] == 'true')
			{
				error_reporting(E_ALL);
				ini_set('display_errors', 1);

				register_shutdown_function( "feedoptimise_fatal_handler_products" );
			}
			if(isset($request['max_execution_time']))
			{
				ini_set('max_execution_time', (int)$request['max_execution_time']);
			}
			if(isset($request['memory_limit']))
			{
				ini_set('memory_limit', ((int)$request['memory_limit']).'M');
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
			else
			{
				// set the current store
				$this->storeController->setStore($request['store_id']);
				$this->storeId = (int)$request['store_id'];

				/** @var \Magento\Framework\DataObject[] $products */
				$products = $this->getProducts($request);

				return $result->setData([
					'error' => false,
					'code' => 200,
					'payload' => [
						'total' => (!isset($request['no_total']) || !$request['no_total']) ? $this->getProductCount() : null,
						'returned_total' => count($products),
						'memory' =>round((memory_get_usage() / 1024) / 1024,2).'M',
						'pagination' => [
							'limit' => (isset($request['limit']) ? (int)$request['limit'] : 50),
							'page' => (isset($request['page']) ? (int)$request['page'] : 1)
						],
						'products' => $products
					]
				]);
			}
		}
		catch (\Throwable $e) {
			$result = $this->resultJsonFactory->create();

			$result->setData([
				'error' => true,
				'code' => 500,
				'memory' =>round((memory_get_usage() / 1024) / 1024,2).'M',
				'error_msg' => $e->getMessage()
			]);
		} catch (\Exception $e) {
			$result = $this->resultJsonFactory->create();

			$result->setData([
				'error' => true,
				'code' => 500,
				'memory' =>round((memory_get_usage() / 1024) / 1024,2).'M',
				'error_msg' => $e->getMessage()
			]);
		}

	}

	/**
	 * Get product count method
	 * @return int
	 * @throws \Magento\Framework\Exception\LocalizedException
	 */
	protected function getProductCount()
	{
		/** @var \Magento\Catalog\Model\ResourceModel\Product\Collection $collection */
		$collection = $this->productCollectionFactory->create();
		$collection->addStoreFilter($this->storeId);


		$request = $this->requestInterface->getParams();
		if(!isset($request['status_all']) || !$request['status_all'])
		{
				$collection->joinAttribute('status', 'catalog_product/status', 'entity_id', null, 'inner');
				$collection->addAttributeToFilter('status', ['in' => $this->productStatus->getVisibleStatusIds()]);
		}

		if(!isset($request['visibility_all']) || !$request['visibility_all'])
		{
			$collection->joinAttribute('visibility', 'catalog_product/visibility', 'entity_id', null, 'inner');
			$collection->addAttributeToFilter('visibility', ['in' => $this->productVisibility->getVisibleInSiteIds()]);
		}

		return $collection->count();
	}
	/**
	 * Get products method
	 * @return \Magento\Framework\DataObject[]
	 * @throws \Magento\Framework\Exception\LocalizedException
	 */
	protected function getProducts($request)
	{
		/** @var \Magento\Catalog\Model\ResourceModel\Product\Collection $collection */
		$collection = $this->productCollectionFactory->create();
        $collection->setFlag('has_stock_status_filter', true);
		$collection->addStoreFilter($this->storeId);

		if(!isset($request['status_all']) || !$request['status_all'])
		{
			$collection->joinAttribute('status', 'catalog_product/status', 'entity_id', null, 'inner');
			$collection->addAttributeToFilter('status', ['in' => $this->productStatus->getVisibleStatusIds()]);
		}

		if(!isset($request['visibility_all']) || !$request['visibility_all'])
		{
			$collection->joinAttribute('visibility', 'catalog_product/visibility', 'entity_id', null, 'inner');
			$collection->addAttributeToFilter('visibility', ['in' => $this->productVisibility->getVisibleInSiteIds()]);
		}

		$collection->setPageSize((isset($request['limit']) ? (int)$request['limit'] : 50));
		$collection->setCurPage((isset($request['page']) ? (int)$request['page'] : 1));

		/** @var \Magento\Framework\DataObject[] $products */
		$products = [];

		foreach($collection->loadData() as $item)
			// use the product controller to extract each product
			$products[] = $this->productController->getProduct($item->getId(), false);

		return $products;
	}
}

function feedoptimise_fatal_handler_products()
{
	echo json_encode(error_get_last(), JSON_PRETTY_PRINT);
	die;
}