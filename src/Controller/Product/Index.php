<?php
/**
 * @author Feedoptimise
 * @copyright Copyright (c) 2020 Feedoptimise (http://www.feedoptimise.com)
 * @package Feedoptimise_CatalogExport
 */

namespace Feedoptimise\CatalogExport\Controller\Product;

use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;

class Index extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface
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
     * @var integer $storeId
     */
    protected $extensionSettings;
    protected $storeController;
    protected $storeId;
    /**
     * Product Searching Variables
     * @var \Magento\Catalog\Model\CategoryRepository $categoryRepository
     * @var \Magento\Catalog\Model\ResourceModel\Category\Tree $categoryTree
     * @var \Magento\Catalog\Model\ProductRepository $productRepository
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @var \Magento\Catalog\Model\Product\Attribute\Source\Status $productStatus
     * @var \Magento\Catalog\Model\Product\Visibility $productVisibility
     */
    protected $categoryRepository;
    protected $categoryTree;
    protected $productRepository;
    protected $productCollectionFactory;
    protected $productStatus;
    protected $productVisibility;

    /** @var string  automatic | off | on  */
    protected $loadFromCache = 'automatic';

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
        \Magento\Catalog\Model\ResourceModel\Category\Tree $categoryTree,
        \Magento\Catalog\Model\ProductRepository $productRepository,
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

        // Product Searching Params
        $this->categoryRepository = $categoryRepository;
        $this->categoryTree = $categoryTree;
        $this->productRepository = $productRepository;
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

                register_shutdown_function( "feedoptimise_fatal_handler_product" );
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
                $this->setStoreId($request['store_id']);

                $this->setLoadFromCache(@$request['load_from_cache']);

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
                        'memory' =>round((memory_get_usage() / 1024) / 1024,2).'M',
                        'product' => $product
                    ];
                }

                return $result->setData($return);
            }
        }
        catch (\Throwable $e) {
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

    public function setLoadFromCache($option)
    {
        if(!empty($option) && in_array($option, ['automatic', 'off', 'on']))
            $this->loadFromCache = $option;
    }

    /**
     * Get product options method
     * @return array|boolean
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getProductOptions($_product)
    {
        $return = [];
        $attrArray = [];

        $_children = $_product->getTypeInstance()
            ->getUsedProductCollection($_product)
            ->setFlag('has_stock_status_filter', true)
            ->loadData();
        $configurableAttributes = $_product->getTypeInstance(true)->getConfigurableAttributes($_product);
        $usedConfigurableAttributes = $_product->getTypeInstance(true)->getConfigurableAttributesAsArray($_product);

        foreach($usedConfigurableAttributes as $confAttr)
            foreach($confAttr['values'] as $confAttrValue)
                $attrArray[$confAttr['attribute_code']][$confAttrValue['label']] = $confAttrValue['value_index'];

        foreach($_children as $_child)
        {
            try
            {
                $_childProduct = $this->getProductById($_child->getId());
                $child = $this->getProductData($_childProduct);
            } catch(\Exception $e)
            {
                continue;
            }

            $urlParams = [];
            $child['attributes'] = [];
            foreach($configurableAttributes as $attribute)
            {
                $attrValue = $_childProduct->getResource()->getAttribute($attribute->getProductAttribute()->getAttributeCode())->getFrontend();
                $attrCode  = $attribute->getProductAttribute()->getAttributeCode();
                $attrLabel = $attribute->getProductAttribute()->getStoreLabel();
                $value = $attrValue->getValue($_childProduct);

                if(array_key_exists($attrCode, $attrArray))
                {
                    foreach ($attrArray[$attrCode] as $kArr => $vArr)
                    {
                        if ((string)$kArr === (string)$value)
                        {
                            $child['attributes'][] = [
                                'attr_id' => $attribute["attribute_id"],
                                'code' => $attrCode,
                                'label' => $attrLabel,
                                'value' => $value,
                                'opt_id' => $vArr
                            ];

                            $urlParams[] = $attribute["attribute_id"] . '=' . $vArr;
                        }
                    }
                }
            }

            if(count($urlParams))
                // append the pre-selection params to the product url
                $child['url'] = $_product->getProductUrl().'#'.implode('&', $urlParams);

            $return[] = $child;
            $_childProduct->clearInstance();
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
        $_children = $_product->getTypeInstance(true)->getChildrenIds($_product->getId());
        if(!empty($_children)){
            $_children = array_shift($_children);
            foreach($_children as $_childId)
            {
                $_childProduct = $this->getProductById($_childId);
                $child = $this->getProductData($_childProduct);
                $child['url'] = $_product->getProductUrl();
                $return[] = $child;
                $_childProduct->clearInstance();
            }
        }

        return $return;
    }

    public function getProductById($id)
    {
        if($this->loadFromCache == 'automatic'){
            $product = $this->productRepository->getById($id, false, $this->storeId);
            if(empty((array)$product->getData())){
                $product = $this->productRepository->getById($id, false, $this->storeId, true);
            }
            return $product;
        }elseif ($this->loadFromCache == 'off')
            return $this->productRepository->getById($id, false, $this->storeId, true);
        elseif ($this->loadFromCache == 'on')
            return $this->productRepository->getById($id, false, $this->storeId);
    }

    /**
     * Get product grouped options method
     * @return array|boolean
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getProductBundleOptions($_product)
    {
        $return = [];
        $_children = $_product->getTypeInstance(true)->getChildrenIds($_product->getId());
        if(!empty($_children)){
            foreach ($_children as $childArray){
                foreach($childArray as $_childId)
                {
                    $_childProduct = $this->getProductById($_childId);
                    $child = $this->getProductData($_childProduct);
                    $child['url'] = $_product->getProductUrl();
                    $return[] = $child;
                    $_childProduct->clearInstance();
                }
            }
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
        foreach ($attributes as $attribute)
        {
            if (!isset($product[$attribute->getData('attribute_code')]) || $attribute->getData('attribute_code') === 'quantity_and_stock_status') continue;
            if ($attribute->usesSource())
            {
                $product[$attribute->getData('attribute_code')] = $attribute->getSource()->getOptionText($_product->getData($attribute->getData('attribute_code')));
            }
            else
            {
                $product[$attribute->getData('attribute_code')] = $_product->getData($attribute->getData('attribute_code'));
            }
        }

        try
        {
            $product['options'] = [];
            $_options = @$_product->getOptions();
            if (is_array($_options) || $_options instanceof Traversable)
            {
                foreach ($_options as $o)
                {
                    $_option = $o->getData();
                    $_option['values'] = [];
                    foreach ($o->getValues() as $value)
                    {
                        $_option['values'][] = $value->getData();
                    }
                    $product['options'][] = $_option;
                }
            }
        } catch (\Exception $e)
        {}
        catch (\Throwable $e)
        {}

        try
        {
            $stockItem = $_product->getExtensionAttributes()->getStockItem();
            $product['stock'] = [
                'qty' => $stockItem->getData('qty'),
                'min_sale_qty' => $stockItem->getData('min_sale_qty'),
                'max_sale_qty' => $stockItem->getData('max_sale_qty'),
                'is_in_stock' => $stockItem->getData('is_in_stock'),
                'stock_id' => $stockItem->getData('stock_id'),
                'manage_stock' => $stockItem->getData('manage_stock'),
                'backorders' => $stockItem->getData('backorders')
            ];
        } catch (\Exception $e)
        {}
        catch (\Throwable $e)
        {}

        $product['url'] = $_product->getProductUrl();
        $product['image'] = $this->storeController->baseImageUrl.$_product->getImage();
        $product['small_image'] = $this->storeController->baseImageUrl.$_product->getData('small_image');
        $product['thumbnail'] = $this->storeController->baseImageUrl.$_product->getData('thumbnail');
        $product['final_price'] = $_product->getFinalPrice(1);

        $request = $this->requestInterface->getParams();

        if(isset($request['category_ver']) &&  $request['category_ver'] =='2')
        {
            if ($categoryIds = $_product->getCategoryIds())
            {
                //types and taxonomy
                $categories = array();
                $level = -1;
                $catPath = array();
                $categoryIds = $_product->getCategoryIds();
                $typesPath = [];
                if (count($categoryIds) > 0)
                {
                    foreach ($categoryIds as $categoryId)
                    {
                        try{
                            $cat = $this->categoryRepository->get($categoryId);

                            $typesPath[] = $cat->getPathIds();
                            if ($cat->getLevel() > $level)
                            {
                                $level = $cat->getLevel();
                                $catPath = $cat->getPathIds();
                            }
                            $cat->clearInstance();
                        } catch(\Exception $e){}
                    }
                    unset($categoryIds);
                }

                if (count($catPath))
                {
                    foreach ($catPath as $categoryId)
                    {
                        $cat2 = $this->categoryRepository->get($categoryId);

                        if ($cat2->getLevel() > 1 && $cat2->getName() != '')
                        {
                            $categories[] = $cat2->getName();
                        }
                        $cat2->clearInstance();
                    }
                }
                $product['category'] = implode(' > ', $categories);

                $types = array();
                if (count($typesPath))
                {
                    foreach ($typesPath as $catPath)
                    {
                        $categories = array();
                        if (count($catPath))
                        {
                            foreach ($catPath as $categoryId)
                            {
                                $cat2 = $this->categoryRepository->get($categoryId);

                                if ($cat2->getLevel() > 1 && $cat2->getName() != '')
                                {
                                    $categories[] = $cat2->getName();
                                }
                                $cat2->clearInstance();
                            }
                        }
                        $categories = implode(' > ', $categories);
                        if ($categories)
                            $types[] = $categories;
                    }
                }

                $product['categories'] = $types;
            }
        }
        else
        {
            // categories
            if ($categoryIds = $_product->getCategoryIds())
            {
                $product['categories'] = [];
                $_category_ids = [];
                $_categoryPaths = [];
                $_categoryNames = [];
                $_categoryPathNames = [];

                // get the category paths
                foreach ($categoryIds as $categoryId)
                {
                    try {
                        $category = $this->categoryRepository->get($categoryId);
                        $_categoryNames[$category->getId()] = $category->getName();
                        $_categoryPaths[] = $category->getPath();
                        $category->clearInstance();
                    } catch(\Exception $e){}
                }

                // convert category id's to category names
                foreach($_categoryPaths as $categoryPath)
                {
                    $splitPath = explode("/", $categoryPath);
                    $_pathNames = [];
                    $category_ids = [];
                    foreach($splitPath as $categoryId)
                        if (isset($_categoryNames[$categoryId]))
                        {
                            $category_ids[] = $categoryId;
                            $_pathNames[] = $_categoryNames[$categoryId];
                        }
                    $category_ids = implode(" > ", $category_ids);
                    $_categoryPathNames[$category_ids] = implode(" > ", $_pathNames);
                }

                // remove any duplicates
                foreach($_categoryPathNames as $index => $pathNames)
                {
                    $splitPath = explode(" > ", $pathNames);
                    array_pop($splitPath);
                    if(count($splitPath) > 0)
                    {
                        $catIndex = array_search(implode(' > ', $splitPath), $_categoryPathNames);
                        if ($catIndex !== -1)
                            unset($_categoryPathNames[$catIndex]);
                    }
                }

                $product['category_ids'] = array_keys($_categoryPathNames);
                $product['categories'] = array_values($_categoryPathNames);
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
        if(
            (empty($product['image']) || strpos($product['image'],'productno_selection') !== false)
            && !empty($product['images'])
        ){
            $product['image'] = $product['images'][0];
        }

        unset($product['media_gallery']);

        return $product;
    }
    /**
     * Get product method
     * @return array|boolean
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getProduct($entity_id, $query = true)
    {
        if($query)
        {
            /** @var \Magento\Catalog\Model\ResourceModel\Product\Collection $collection */
            $collection = $this->productCollectionFactory->create();
            $collection->setFlag('has_stock_status_filter', true);
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

            $collection->addAttributeToFilter('entity_id', $entity_id);
            $collection->addMediaGalleryData();
            $collection->addFinalPrice();

            // limit to 1 product
            $collection->setPageSize(1);
            $collection->setCurPage(1);

            foreach ($collection->loadData() as $item)
            {
                try
                {
                    $_product = $this->getProductById($item->getId());
                    $product = $this->getProductData($_product);

                    // options/grouped options
                    if ($_product->getTypeId() === "configurable")
                    {
                        $product['variants'] = $this->getProductOptions($_product);
                    }
                    else if ($_product->getTypeId() === "grouped")
                    {
                        $product['variants'] = $this->getProductBundleOptions($_product);
                    }
                    else if ($_product->getTypeId() === "bundle")
                    {
                        $product['variants'] = $this->getProductBundleOptions($_product);
                    }

                    $_product->clearInstance();
                } catch (\Exception $e)
                {
                    return [
                        'error' => true,
                        'code' => 500,
                        'error_msg' => $e->getMessage()
                    ];
                }

                return $product;
            }
        }
        else
        {
            try
            {
                $_product = $this->getProductById($entity_id);
                $product = $this->getProductData($_product);

                // options/grouped options
                if ($_product->getTypeId() === "configurable")
                {
                    $product['variants'] = $this->getProductOptions($_product);
                }
                else if ($_product->getTypeId() === "grouped")
                {
                    $product['variants'] = $this->getProductBundleOptions($_product);
                }
                else if ($_product->getTypeId() === "bundle")
                {
                    $product['variants'] = $this->getProductBundleOptions($_product);
                }

                $_product->clearInstance();
            } catch (\Exception $e)
            {
                return [
                    'error' => true,
                    'code' => 500,
                    'memory' =>round((memory_get_usage() / 1024) / 1024,2).'M',
                    'error_msg' => $e->getMessage()
                ];
            }

            return $product;
        }

        return false;
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}


function feedoptimise_fatal_handler_product()
{
    echo json_encode(error_get_last(), JSON_PRETTY_PRINT);
    die;
}
