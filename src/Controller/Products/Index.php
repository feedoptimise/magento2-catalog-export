<?php
/**
 * @author Joe Yates (Feedoptimise)
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
			//$this->storeController->setStore($request['store_id']);
			$this->storeId = (int)$request['store_id'];

			/** @var \Magento\Framework\DataObject[] $products */
			$products = $this->getProducts($request);

			return $result->setData([
				'error' => false,
				'code' => 200,
				'payload' => [
					'total' => $this->getProductCount(),
					'returned_total' => count($products),
					'pagination' => [
						'limit' => (isset($request['limit']) ? (int)$request['limit'] : 50),
						'page' => (isset($request['page']) ? (int)$request['page'] : 1)
					],
					'products' => $products
				]
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
		$collection->joinAttribute('status', 'catalog_product/status', 'entity_id', null, 'inner');
		$collection->joinAttribute('visibility', 'catalog_product/visibility', 'entity_id', null, 'inner');
		$collection->addAttributeToFilter('status', ['in' => $this->productStatus->getVisibleStatusIds()]);
		$collection->addAttributeToFilter('visibility', ['in' => $this->productVisibility->getVisibleInSiteIds()]);
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
		$collection->addStoreFilter($this->storeId);
		$collection->joinAttribute('status', 'catalog_product/status', 'entity_id', null, 'inner');
		$collection->joinAttribute('visibility', 'catalog_product/visibility', 'entity_id', null, 'inner');
		$collection->addAttributeToFilter('status', ['in' => $this->productStatus->getVisibleStatusIds()]);
		$collection->addAttributeToFilter('visibility', ['in' => $this->productVisibility->getVisibleInSiteIds()]);

		$collection->setPageSize((isset($request['limit']) ? (int)$request['limit'] : 50));
		$collection->setCurPage((isset($request['page']) ? (int)$request['page'] : 1));

		/** @var \Magento\Framework\DataObject[] $products */
		$products = [];

		foreach($collection->loadData() as $item)
			// use the product controller to extract each product
			$products[] = $this->productController->getProduct($item->getId(), true);

		return $products;
	}
}
