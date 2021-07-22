<?php
/**
 * @author Feedoptimise
 * @copyright Copyright (c) 2020 Feedoptimise (http://www.feedoptimise.com)
 * @package Feedoptimise_CatalogExport
 */

namespace Feedoptimise\CatalogExport\Controller\Oauth;

use Feedoptimise\CatalogExport\Helper\Settings;

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

		$request = $this->getRequest()->getParams();
		try{
            if(!isset($request['token']) || !isset($request['client_id'])){
                return $result->setData([
                    'error' => true,
                    'code' => 400,
                    'error_msg' => 'Please specify an token and client_id'
                ]);
            }

            $internalToken = $this->extensionSettings->getTeporaryToken();

            if($internalToken['expired'] < time())
                throw new \Exception('The Token has been expired. Try again.', 400);

            if($request['client_id'] !== Settings::FEEDOPTIMISE_CLIENT_ID){
                throw new \Exception('Incorrect Client ID.', 400);
            }

            if($request['token'] !== $internalToken['token']){
                throw new \Exception('Invalid authentication token.', 400);
            }

            return $result->setData([
                'access_token' => $this->extensionSettings->getGeneralConfig('security_token'),
                'enable' => $this->extensionSettings->getGeneralConfig('enable'),
                'ver' => $this->extensionSettings->getVersion()
            ]);
        }catch (\Throwable $e) {
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

		return $result->setData($data);
	}
}
