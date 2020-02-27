<?php
/**
 * @author Joe Yates (Feedoptimise)
 * @copyright Copyright (c) 2020 Feedoptimise (http://www.feedoptimise.com)
 * @package Feedoptimise_CatalogExport
 */

namespace Feedoptimise\CatalogExport\Controller\Stores;
use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;

class Index extends \Magento\Framework\App\Action\Action implements HttpPostActionInterface
{
	/**
	 * Framework Variables
	 * @var \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
	 * @var \Magento\Store\Model\StoreManagerInterface
	 */
	protected $resultJsonFactory;
	protected $storeManager;
	/**
	 * Extension Variables
	 * @var \Feedoptimise\CatalogExport\Helper\Settings $extensionSettings
	 * @var string $baseImageUrl
	 */
	protected $extensionSettings;
	public $baseImageUrl;
	/**
	 * @param \Magento\Framework\App\Action\Context $context
	 * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
	 * @param \Magento\Store\Model\StoreManagerInterface $storeManager
	 * @param \Feedoptimise\CatalogExport\Helper\Settings $extensionSettings
	 */
	public function __construct(
		\Magento\Framework\App\Action\Context $context,
		\Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
		\Feedoptimise\CatalogExport\Helper\Settings $extensionSettings,
		\Magento\Store\Model\StoreManagerInterface $storeManager
	)
	{
		$this->extensionSettings = $extensionSettings;
		$this->resultJsonFactory = $resultJsonFactory;
		$this->storeManager = $storeManager;
		return parent::__construct($context);
	}
	/**
	 * View page action
	 *
	 * @return \Magento\Framework\Controller\ResultInterface
	 */
	public function execute()
	{
		$result = $this->resultJsonFactory->create();
		if(($settingsError = $this->extensionSettings->validateSettings()) !== true)
		{
			return $result->setData($settingsError);
		}
		else
		{
			$stores = $this->getStores();
			return $result->setData([
				'error' => false,
				'code' => 200,
				'payload' => [
					'total' => count($stores),
					'stores' => $stores
				]
			]);
		}
	}
	/**
	 * Get stores method
	 *
	 * @return \Magento\Framework\Controller\ResultInterface
	 */
	private function getStores()
	{
		/** @var array $storeManagerDataList */
		$storeManagerDataList = $this->storeManager->getStores();
		/** @var array $options */
		$options = [];

		foreach ($storeManagerDataList as $key => $value) {
			$options[] = [
				'id' => $value->getStoreId(),
				'code' => $value->getCode(),
				'name' => $value->getFrontendName(),
				'homepage' => $value->getBaseUrl()
			];
		}

		return $options;
	}
	/**
	 * Set store method
	 *
	 * @return array|boolean
	 */
	public function setStore($storeId)
	{
		// set the current store
		$this->storeManager->setCurrentStore($storeId);

		// set the store base image url
		$store = $this->storeManager->getStore();
		$this->baseImageUrl = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product';

		return true;
	}
	/**
	 * Get stores method
	 *
	 * @return array|boolean
	 */
	public function checkStore()
	{
		/** @var string $storeId */
		$storeId = @$_POST['store_id'];

		if(!$storeId)
		{
			return [
				'error' => true,
				'code' => 400,
				'error_msg' => 'Please specify a store_id'
			];
		}
		else
		{
			/** @var \Magento\Store\Api\Data\StoreInterface[] $storeManagerDataList */
			$storeManagerDataList = $this->storeManager->getStores();

			foreach ($storeManagerDataList as $key => $value)
				if((int)$storeId === (int)$value->getStoreId()) return true;

			return [
				'error' => true,
				'code' => 400,
				'error_msg' => 'Store doesn\'t exist with id: '.$storeId
			];
		}
	}
}
