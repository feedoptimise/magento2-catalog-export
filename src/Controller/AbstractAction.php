<?php

namespace Feedoptimise\CatalogExport\Controller;

use \Magento\Framework\App\Action\Action;
use \Magento\Framework\App\Action\Context;
use \Magento\Framework\View\Result\PageFactory;
use \Magento\Framework\Json\Helper\Data;
use \Feedoptimise\CatalogExport\Helper\Settings;

Abstract class AbstractAction extends Action
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;
    /**
     * @var Data
     */
    protected $jsonHelper;
    /**
     * @var Settings
     */
    protected $extensionSettings;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param Data $jsonHelper
     * @param Settings $extensionSettings
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Data $jsonHelper,

        Settings $extensionSettings
    ){
        parent::__construct($context);

        $this->resultPageFactory = $resultPageFactory;
        $this->jsonHelper = $jsonHelper;
        $this->extensionSettings = $extensionSettings;
    }

    /**
     * @return array
     */
    abstract protected function getRequestParams();

    public function validRequest($requireStore = true)
    {
        $request = $this->getRequestParams();

        if(isset($request['debug']) && $request['debug'] == 'true')
        {
            error_reporting(E_ALL);
            ini_set('display_errors', 1);

            register_shutdown_function( "feedoptimise_fatal_handler_products" );
        }

        if(($settingsError = $this->extensionSettings->validateSettings($request)) !== true)
        {
            return $this->jsonResponse($settingsError);
        }
        else if($requireStore && ($storeError = $this->extensionSettings->checkStore(@$request['store_id'])) !== true)
        {
            return $this->jsonResponse($storeError);
        }

        return true;
    }

    /**
     * Create json response
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function jsonResponse($response = '')
    {
        return $this->getResponse()->representJson(
            $this->jsonHelper->jsonEncode($response)
        );
    }
}