<?php
namespace Feedoptimise\CatalogExport\Controller\Adminhtml\Connect;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

/**
 * Class Index
 */
class Index extends Action implements HttpGetActionInterface
{
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var \Feedoptimise\CatalogExport\Helper\Settings $extensionSettings
     */
    protected $extensionSettings;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Feedoptimise\CatalogExport\Helper\Settings $extensionSettings
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Feedoptimise\CatalogExport\Helper\Settings $extensionSettings,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
    )
    {
        $this->extensionSettings = $extensionSettings;
        $this->resultJsonFactory = $resultJsonFactory;
        return parent::__construct($context);
    }

    /**
     * Load the page defined in view/adminhtml/layout/exampleadminnewpage_helloworld_index.xml
     *
     * @return Page
     */
    public function execute()
    {
        $token = $this->extensionSettings->setTemporaryToken();

        $url = $this->extensionSettings->getConnectionUrlToFeedoptimise($token, $this->extensionSettings->getSiteUrl());

        return $this->_redirect($url);
    }
}
