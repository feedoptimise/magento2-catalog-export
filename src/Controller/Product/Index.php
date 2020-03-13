<?php
/**
 * @author Joe Yates (Feedoptimise)
 * @copyright Copyright (c) 2020 Feedoptimise (http://www.feedoptimise.com)
 * @package Feedoptimise_CatalogExport
 */

namespace Feedoptimise\CatalogExport\Controller\Product;

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
	 */
	protected $extensionSettings;
	protected $storeController;
	/**
	 * Product Searching Variables
	 * @var \Magento\Catalog\Model\CategoryRepository $categoryRepository
	 * @var \Magento\Catalog\Model\ProductRepository $productRepository
	 * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
	 * @var \Magento\Catalog\Model\Product\Attribute\Source\Status $productStatus
	 * @var \Magento\Catalog\Model\Product\Visibility $productVisibility
	 * @var \Magento\CatalogInventory\Model\Stock\StockItemRepository $stockItemRepository
	 */
	protected $categoryRepository;
	protected $productRepository;
	protected $productCollectionFactory;
	protected $productStatus;
	protected $productVisibility;
	protected $stockItemRepository;

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
	 *
	 * Product Searching Params
	 * @param \Magento\Catalog\Model\CategoryRepository $categoryRepository
	 * @param \Magento\Catalog\Model\ProductRepository $productRepository
	 * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
	 * @param \Magento\Catalog\Model\Product\Attribute\Source\Status $productStatus
	 * @param \Magento\Catalog\Model\Product\Visibility $productVisibility
	 * @param \Magento\CatalogInventory\Model\Stock\StockItemRepository $stockItemRepository
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
		// Product Searching Params
		\Magento\Catalog\Model\CategoryRepository $categoryRepository,
		\Magento\Catalog\Model\ProductRepository $productRepository,
		\Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
		\Magento\Catalog\Model\Product\Attribute\Source\Status $productStatus,
		\Magento\Catalog\Model\Product\Visibility $productVisibility,
		\Magento\CatalogInventory\Model\Stock\StockItemRepository $stockItemRepository
	)
	{
		// Framework Variables
		$this->requestInterface = $requestInterface;
		$this->resultJsonFactory = $resultJsonFactory;
		$this->storeManager = $storeManager;

		// Extension Variables
		$this->extensionSettings = $extensionSettings;
		$this->storeController = $storeController;

		// Product Searching Params
		$this->categoryRepository = $categoryRepository;
		$this->productRepository = $productRepository;
		$this->productCollectionFactory = $productCollectionFactory;
		$this->productStatus = $productStatus;
		$this->productVisibility = $productVisibility;
		$this->stockItemRepository = $stockItemRepository;

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
		else if(!isset($request['entity_id']))
		{
			return $result->setData([
				'error' => true,
				'code' => 400,
				'error_msg' => 'Please specify an entity_id'
			]);
		}
		else
		{
			// set the current store
			$this->storeController->setStore($request['store_id']);

			/** @var \Magento\Framework\DataObject[] $product */
			$product = $this->getProduct($request['entity_id']);

			/** @var array $return */
			$return = [
				'error' => false,
				'code' => 200
			];

			if(!$product)
			{
				$return['error'] = true;
				$return['code'] = 400;
				$return['error_msg'] = 'Product doesn\'t exist with entity_id: '.$request['entity_id'];
			}
			else
			{
				$return['payload'] = [
					'product' => $product
				];
			}

			return $result->setData($return);
		}
	}
	/**
	 * Get product options method
	 * @return array|boolean
	 * @throws \Magento\Framework\Exception\LocalizedException
	 */
	protected function getProductOptions($_product)
	{
		$return = [];
		$_children = $_product->getTypeInstance()->getUsedProducts($_product);
		$configurableAttributes = $_product->getTypeInstance(true)->getConfigurableAttributes($_product);
		foreach($_children as $_child)
		{
			try
			{
				$_childProduct = $this->productRepository->getById($_child->getId());
				$child = $this->getProductData($_childProduct);
			} catch(\Exception $e)
			{
				continue;
			}

			$urlParams = [];
			foreach($configurableAttributes as $attribute)
			{
				$attrValue = $_childProduct->getResource()->getAttribute($attribute->getProductAttribute()->getAttributeCode())->getFrontend();
				$value = $attrValue->getValue($_childProduct);

				foreach($attrValue->getSelectOptions() as $attrOption)
					if($attrOption['label'] == $value)
						$urlParams[] = $attribute["attribute_id"].'='.$attrOption['value'];
			}
			if(count($urlParams))
				// append the pre-selection params to the product url
				$child['url'] = $_product->getProductUrl().'#'.implode('&', $urlParams);

			$return[] = $child;
		}
		return $return;
	}
	/**
	 * Get product grouped options method
	 * @return array|boolean
	 * @throws \Magento\Framework\Exception\LocalizedException
	 */
	protected function getProductGroupedOptions($_product)
	{
		$return = [];
		$_children = $_product->getTypeInstance(true)->getAssociatedProducts($_product);
		foreach($_children as $_child)
		{
			$_childProduct = $this->productRepository->getById($_child->getId());
			$child = $this->getProductData($_childProduct);
			$child['url'] = $_product->getProductUrl();
			$return[] = $child;
		}
		return $return;
	}
	/**
	 * Get product data method
	 * @return array
	 * @throws \Magento\Framework\Exception\LocalizedException
	 */
	protected function getProductData($_product)
	{
		$product = $_product->getData();
		$attributes = $_product->getAttributes();
		foreach($attributes as $attribute)
		{
			if(!isset($product[$attribute->getData('attribute_code')])) continue;
			if ($attribute->usesSource()) {
				$product[$attribute->getData('attribute_code')] = $attribute->getSource()->getOptionText($_product->getData($attribute->getData('attribute_code')));
			}
			else
			{
				$product[$attribute->getData('attribute_code')] = $_product->getData($attribute->getData('attribute_code'));
			}
		}

		$stockItem = $_product->getExtensionAttributes()->getStockItem();
		$product['stock'] = [
			'stock_id' => $stockItem->getData('stock_id'),
			'qty' => $stockItem->getData('qty'),
			'min_sale_qty' => $stockItem->getData('min_sale_qty'),
			'max_sale_qty' => $stockItem->getData('max_sale_qty'),
			'is_in_stock' => $stockItem->getData('is_in_stock'),
			'stock_id' => $stockItem->getData('stock_id'),
			'manage_stock' => $stockItem->getData('manage_stock'),
			'backorders' => $stockItem->getData('backorders')
		];

		$product['url'] = $_product->getProductUrl();
		$product['image'] = $this->storeController->baseImageUrl.$_product->getImage();
		$product['small_image'] = $this->storeController->baseImageUrl.$_product->getData('small_image');
		$product['thumbnail'] = $this->storeController->baseImageUrl.$_product->getData('thumbnail');
		$product['final_price'] = $_product->getFinalPrice();

		// categories
		if ($categoryIds = $_product->getCustomAttribute('category_ids')) {
			foreach ($categoryIds->getValue() as $categoryId) {
				$product['categories'][] = $this->categoryRepository->get($categoryId)->getName();
			}
		}

		// images
		$_media = $_product->getMediaGalleryImages();
		foreach($_media as $_media_item)
		{
			if($_media_item->getData('media_type') === 'image')
			{
				$product['images'][] = $_media_item->getData();
			}
			else if($_media_item->getData('media_type') === 'video')
			{
				$product['videos'][] = $_media_item->getData();
			}
		}
		unset($product['media_gallery']);

		return $product;
	}
	/**
	 * Get product method
	 * @return array|boolean
	 * @throws \Magento\Framework\Exception\LocalizedException
	 */
	public function getProduct($entity_id)
	{
		/** @var \Magento\Catalog\Model\ResourceModel\Product\Collection $collection */
		$collection = $this->productCollectionFactory->create();
		$collection->joinAttribute('status', 'catalog_product/status', 'entity_id', null, 'inner');
		$collection->joinAttribute('visibility', 'catalog_product/visibility', 'entity_id', null, 'inner');
		$collection->addAttributeToFilter('status', ['in' => $this->productStatus->getVisibleStatusIds()]);
		$collection->addAttributeToFilter('visibility', ['in' => $this->productVisibility->getVisibleInSiteIds()]);
		$collection->addAttributeToFilter('entity_id', $entity_id);
		$collection->addMediaGalleryData();
		$collection->addFinalPrice();

		// limit to 1 product
		$collection->setPageSize(1);
		$collection->setCurPage(1);

		foreach($collection->loadData() as $item)
		{
			try
			{
				$_product = $this->productRepository->getById($item->getId());
				$product = $this->getProductData($_product);

				// options/grouped options
				if($_product->getTypeId() === "configurable")
				{
					$product['variants'] = $this->getProductOptions($_product);
				}
				else if($_product->getTypeId() === "grouped")
				{
					$product['variants'] = $this->getProductGroupedOptions($_product);
				}
			} catch(\Exception $e)
			{
				return [
					'error' => true,
					'code' => 500,
					'error_msg' => $e->getMessage()
				];
			}

			return $product;
		}

		return false;
	}
}
