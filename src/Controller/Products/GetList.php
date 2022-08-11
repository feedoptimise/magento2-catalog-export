<?php

namespace Feedoptimise\CatalogExport\Controller\Products;

use Feedoptimise\CatalogExport\Controller\AbstractAction;

class GetList extends AbstractAction implements \Magento\Framework\App\Action\HttpPostActionInterface
{
    /**
     * Extension Variables
     * @var \Feedoptimise\CatalogExport\Controller\Stores\Index $storeController
     * @var \Feedoptimise\CatalogExport\Controller\Products\Index $productsController
     */
    protected $storeController;
    protected $productsController;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Json\Helper\Data $jsonHelper,

        \Feedoptimise\CatalogExport\Helper\Settings $extensionSettings,
        \Feedoptimise\CatalogExport\Controller\Stores\Index $storeController,
        \Feedoptimise\CatalogExport\Controller\Products\Index $productsController
    ){
        $this->storeController = $storeController;
        $this->productsController = $productsController;

        return parent::__construct($context, $resultPageFactory, $jsonHelper, $extensionSettings);
    }

    public function execute()
    {
        try {

            if(($res = $this->validRequest()) !== true){
                return $res;
            }

            /** @var array $request */
            $request = $this->getRequestParams();

            $currentStoreId = $this->storeController->getCurrentStoreId();
            // set the current store
            $this->productsController->setStoreId($request['store_id']);

            $this->productsController->setLoadFromCache(@$request['load_from_cache']);

            /** @var \Magento\Framework\DataObject[] $products */
            $products = $this->productsController->getProducts($request);

            $this->productsController->setStoreId($currentStoreId);
            return $this->jsonResponse([
                'error' => false,
                'code' => 200,
                'payload' => [
                    'total' => (!isset($request['no_total']) || !$request['no_total']) ? $this->productsController->getProductCount($request) : null,
                    'returned_total' => count($products),
                    'memory' =>round((memory_get_usage() / 1024) / 1024,2).'M',
                    'pagination' => [
                        'limit' => $this->productsController->getLimit(@$request['limit']),
                        'page' => (isset($request['page']) ? (int)$request['page'] : 1)
                    ],
                    'products' => $products
                ]
            ]);

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