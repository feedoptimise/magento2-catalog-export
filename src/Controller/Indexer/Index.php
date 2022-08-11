<?php

namespace Feedoptimise\CatalogExport\Controller\Indexer;

use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;

class Index extends \Magento\Framework\App\Action\Action
{
    /** @var \Magento\Framework\App\RequestInterface $requestInterface */
    protected $requestInterface;
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;
    /**
     * @var \Feedoptimise\CatalogExport\Helper\Settings
     */
    protected $extensionSettings;
    /**
     * @var \Magento\Indexer\Model\IndexerFactory
     */
    protected $indexFactory;
    /**
     * @var \Magento\Indexer\Model\Indexer\CollectionFactory
     */
    protected $indexCollection;

    /**
     * Cache types list
     *
     * @var \Magento\Framework\App\Cache\TypeListInterface
     */
    private $cacheTypeList;

    /**
     *
     */

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Feedoptimise\CatalogExport\Helper\Settings $extensionSettings
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\RequestInterface $requestInterface,
        \Feedoptimise\CatalogExport\Helper\Settings $extensionSettings,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Indexer\Model\IndexerFactory $indexFactory,
        \Magento\Indexer\Model\Indexer\CollectionFactory $indexCollection
    )
    {
        $this->requestInterface = $requestInterface;
        $this->extensionSettings = $extensionSettings;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->indexFactory = $indexFactory;
        $this->indexCollection = $indexCollection;
        $this->cacheTypeList = $cacheTypeList;
        return parent::__construct($context);
    }
    /**
     * View page action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        try {
            /** @var array $request */
            $request = $this->requestInterface->getParams();

            $result = $this->resultJsonFactory->create();
            if(($settingsError = $this->extensionSettings->validateSettings($request)) !== true)
            {
                return $result->setData($settingsError);
            }

            $data = $this->getInfo();

            return $result->setData([
                'error' => false,
                'code' => 200,
                'payload' => $data
            ]);
        } catch (\Throwable $e) {
            $result = $this->resultJsonFactory->create();

            $result->setData([
                'error' => true,
                'code' => 500,
                'error_msg' => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            $result = $this->resultJsonFactory->create();

            $result->setData([
                'error' => true,
                'code' => 500,
                'error_msg' => $e->getMessage()
            ]);
        }
    }

    public function getInfo()
    {
        $indexerCollection = $this->indexCollection->create();
        $indexStatus = [];
        foreach ($indexerCollection as $index)
        {
            $indexStatus[] = [
                'name' => $index->getTitle(),
                'status' => $index->getStatus()
            ];
        }

        $cache = [];
        $innvalidate = $this->cacheTypeList->getInvalidated();
        foreach ($this->cacheTypeList->getTypes() as $type) {
            $cache[] = [
                'name' => $type['cache_type'],
                'enabled' => $type['status'],
                'is_valid' => (int)(!isset($innvalidate[$type['id']]))
            ];
        }

        return [
            'indexers' => $indexStatus,
            'cache' => $cache
        ];
    }
}