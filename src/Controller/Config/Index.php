<?php
/**
 * @author Joe Yates (Feedoptimise)
 * @copyright Copyright (c) 2020 Feedoptimise (http://www.feedoptimise.com)
 * @package Feedoptimise_CatalogExport
 */

namespace Feedoptimise\CatalogExport\Controller\Config;

class Index extends \Magento\Framework\App\Action\Action
{
	/**
	 * @var \Magento\Framework\Controller\Result\JsonFactory
	 */
	protected $resultJsonFactory;
	/**
	 * @var \Feedoptimise\CatalogExport\Helper\Settings
	 */
	protected $extensionSettings;
	/**
	 * @param \Magento\Framework\App\Action\Context $context
	 * @param \Feedoptimise\CatalogExport\Helper\Settings $extensionSettings
	 * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
	 */
	public function __construct(
		\Magento\Framework\App\Action\Context $context,
		\Feedoptimise\CatalogExport\Helper\Settings $extensionSettings,
		\Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
	)
	{
		$this->extensionSettings = $extensionSettings;
		$this->resultJsonFactory = $resultJsonFactory;
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
		$data = [
			'enabled' => $this->extensionSettings->getGeneralConfig('enable'),
			'security_token' => ($this->extensionSettings->getGeneralConfig('security_token') !== null)
		];

		return $result->setData($data);
	}
}
