<?php
/**
 * @author Feedoptimise
 * @copyright Copyright (c) 2020 Feedoptimise (http://www.feedoptimise.com)
 * @package Feedoptimise_CatalogExport
 */

namespace Feedoptimise\CatalogExport\Controller\Stores;

class Index extends \Magento\Framework\App\Action\Action
{
	/**
	 * Framework Variables
	 * @var \Magento\Framework\App\RequestInterface $requestInterface
	 * @var \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
	 * @var \Magento\Store\Model\StoreManagerInterface
	 * @var \Magento\Directory\Model\Currency $currencyModel
	 */
	protected $requestInterface;
	protected $resultJsonFactory;
	protected $storeManager;
	protected $currencyModel;
	/**
	 * Extension Variables
	 * @var \Feedoptimise\CatalogExport\Helper\Settings $extensionSettings
	 * @var string $baseImageUrl
	 */
	protected $extensionSettings;
	public $baseImageUrl;
	/**
	 * @param \Magento\Framework\App\Action\Context $context
	 * @param \Magento\Framework\App\RequestInterface $requestInterface
	 * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
	 * @param \Magento\Store\Model\StoreManagerInterface $storeManager
	 * @param \Feedoptimise\CatalogExport\Helper\Settings $extensionSettings
	 */
	public function __construct(
		\Magento\Framework\App\Action\Context $context,
		\Magento\Framework\App\RequestInterface $requestInterface,
		\Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
		\Feedoptimise\CatalogExport\Helper\Settings $extensionSettings,
		\Magento\Store\Model\StoreManagerInterface $storeManager,
		\Magento\Directory\Model\Currency $currencyModel
	)
	{
		$this->requestInterface = $requestInterface;
		$this->extensionSettings = $extensionSettings;
		$this->resultJsonFactory = $resultJsonFactory;
		$this->storeManager = $storeManager;
		$this->currencyModel = $currencyModel;

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

			$request = $this->requestInterface->getParams();
			if(@$request['debug'] == 'true')
			{
				error_reporting(E_ALL);
				ini_set('display_errors', 1);
				register_shutdown_function( "feedoptimise_fatal_handler_stores" );
			}
			if(@$request['phpinfo'] == 'true')
			{
				phpinfo();
				die;
			}
			if(@$request['meminfo'] == 'true')
			{
				$result = $this->resultJsonFactory->create();
				$meminfo = array();
				if(file_exists("/proc/meminfo"))
				{
					$data = explode("\n", file_get_contents("/proc/meminfo"));
					foreach ($data as $line) {
						list($key, $val) = explode(":", $line);
						$meminfo[$key] = trim($val);
					}
				}
				return $result->setData([
					'error' => false,
					'code' => 200,
					'payload' => [
						'meminfo' => $meminfo,
					]
				]);
			}

			$result = $this->resultJsonFactory->create();
			if(($settingsError = $this->extensionSettings->validateSettings($this->requestInterface->getParams())) !== true)
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
		catch (\Throwable $e) {
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
	/**
	 * Get stores method
	 *
	 * @return array
	 */
	private function getStores()
	{
		/** @var array $storeManagerDataList */
		$storeManagerDataList = $this->storeManager->getStores();
		/** @var array $options */
		$options = [];

		foreach ($storeManagerDataList as $key => $value) {
			$currencies = $this->getStoreCurrencies($value->getStoreId());
			$options[] = [
				'id' => $value->getStoreId(),
				'store_code' => $value->getCode(),
				'name' => $value->getFrontendName(),
				'homepage' => $value->getBaseUrl(),
				'currencies' => $currencies
			];
		}

		return $options;
	}
	/**
	 * Get store currencies method
	 *
	 * @return array
	 */
	private function getStoreCurrencies($storeId)
	{
		$this->storeManager->setCurrentStore($storeId);
		$baseCurrency = $this->storeManager->getStore()->getBaseCurrencyCode();
		$allCurrencies = $this->storeManager->getStore()->getAvailableCurrencyCodes(true);

		$return = [
			'base_currency' => $this->storeManager->getStore()->getBaseCurrencyCode(),
			'rates' => []
		];

		foreach($allCurrencies as $currency)
			$return['rates'][$currency] = $this->storeManager->getStore()->getBaseCurrency()->getRate($currency);

		return $return;
	}
	/**
	 * Set store method
	 *
	 * @return array|boolean
	 */
	public function setStore($storeId)
	{
		// set the current store
        $storeId = (int) $storeId;
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
	public function checkStore($storeId)
	{
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


function feedoptimise_fatal_handler_stores()
{
	echo json_encode(error_get_last(), JSON_PRETTY_PRINT);
	die;
}