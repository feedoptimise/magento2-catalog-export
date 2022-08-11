<?php

namespace Feedoptimise\CatalogExport\Controller\Stores;

use Feedoptimise\CatalogExport\Controller\AbstractAction;

class GetList extends AbstractAction implements \Magento\Framework\App\Action\HttpPostActionInterface
{
    protected $storeController;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Json\Helper\Data $jsonHelper,

        \Feedoptimise\CatalogExport\Helper\Settings $extensionSettings,
        \Feedoptimise\CatalogExport\Controller\Stores\Index $storeController
    ){
        $this->storeController = $storeController;

        return parent::__construct($context, $resultPageFactory, $jsonHelper, $extensionSettings);
    }

    public function execute()
    {
        try {

            if(($res = $this->validRequest(false)) !== true){
                return $res;
            }

            $stores = $this->storeController->getStores();
            return $this->jsonResponse([
                'error' => false,
                'code' => 200,
                'payload' => [
                    'total' => count($stores),
                    'stores' => $stores
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