<?php

namespace Feedoptimise\CatalogExport\Controller\Product;

use Feedoptimise\CatalogExport\Controller\AbstractAction;

class ById extends AbstractAction implements \Magento\Framework\App\Action\HttpPostActionInterface
{
    protected $storeController;
    protected $productController;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Json\Helper\Data $jsonHelper,

        \Feedoptimise\CatalogExport\Helper\Settings $extensionSettings,
        \Feedoptimise\CatalogExport\Controller\Product\Index $productController,
        \Feedoptimise\CatalogExport\Controller\Stores\Index $storeController
    ){
        $this->productController = $productController;
        $this->storeController = $storeController;

        return parent::__construct($context, $resultPageFactory, $jsonHelper, $extensionSettings);
    }

    public function execute()
    {
        try {

            if(($res = $this->validRequest()) !== true){
                return $res;
            }

            $request = $this->getRequestParams();

            if(!isset($request['entity_id']))
            {
                return $this->jsonResponse([
                    'error' => true,
                    'code' => 400,
                    'error_msg' => 'Please specify an entity_id'
                ]);
            }

            $currentStoreId = $this->storeController->getCurrentStoreId();
            // set the current store
            $this->productController->setStoreId($request['store_id']);

            $this->productController->setLoadFromCache(@$request['load_from_cache']);

            /** @var \Magento\Framework\DataObject[] $product */
            $product = $this->productController->getProduct($request['entity_id']);

            $this->productController->setStoreId($currentStoreId);

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

            return $this->jsonResponse($return);

        }
        catch (\Throwable $e) {
            return $this->jsonResponse([
                'error' => true,
                'code' => 500,
                'memory' =>round((memory_get_usage() / 1024) / 1024,2).'M',
                'error_msg' => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse([
                'error' => true,
                'code' => 500,
                'memory' =>round((memory_get_usage() / 1024) / 1024,2).'M',
                'error_msg' => $e->getMessage()
            ]);
        }

    }

    protected function getRequestParams()
    {
        return $this->getRequest()->getPostValue();
    }
}