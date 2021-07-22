<?php
/**
 * @author Joe Yates (Feedoptimise)
 * @copyright Copyright (c) 2020 Feedoptimise (http://www.feedoptimise.com)
 * @package Feedoptimise_CatalogExport
 */

namespace Feedoptimise\CatalogExport\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Cache\Type\Config as CacheTypeConfig;
use Magento\Store\Model\ScopeInterface;

class Settings extends AbstractHelper
{
	const XML_PATH_EXPORT = 'feedoptimise_catalog_export/';
	const MODULE_NAME = 'Feedoptimise_CatalogExport';
	const FEEDOPTIMISE_TEMPORARY_TOKEN_FLAG_CODE = 'feedoptimise_temporary_token_flag';

	const FEEDOPTIMISE_HOST = 'https://app.feedoptimise.com';
	const FEEDOPTIMISE_CONNECT_URL = '/feeds/oauth/magento';

	const FEEDOPTIMISE_CLIENT_ID = 'af89ea10-800f-4f03-803c-ef936e29d6ec';

	protected $_moduleList;
	protected $_configWriter;
	protected $_flagManager;
	protected $_storeManager;

	public function __construct(
		\Magento\Framework\App\Helper\Context $context,
		\Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Framework\FlagManager $flagManager,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    )
	{
        $this->_storeManager = $storeManager;
		$this->_moduleList = $moduleList;
		$this->_flagManager = $flagManager;
        parent::__construct($context);
	}

	public function getVersion()
	{
		return $this->_moduleList
			->getOne(self::MODULE_NAME)['setup_version'];
	}

	public function getConfigValue($field, $storeId = null)
	{
		return $this->scopeConfig->getValue(
			$field, ScopeConfigInterface::SCOPE_TYPE_DEFAULT, (int)$storeId
		);
	}

	public function getGeneralConfig($code, $storeId = null)
	{
		return $this->getConfigValue(self::XML_PATH_EXPORT .'general/'. $code, $storeId);
	}

	public function setTemporaryToken()
    {
        $token = uniqid();
        $token = sha1($token.'_'.date('Y-m-d_H:i:s'));
        $data = [
            'token' => $token,
            'expired' => strtotime('+15 minutes')
        ];
        $this->_flagManager->saveFlag(self::FEEDOPTIMISE_TEMPORARY_TOKEN_FLAG_CODE, json_encode($data));
        return $token;
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getTeporaryToken()
    {
        $token = $this->_flagManager->getFlagData(self::FEEDOPTIMISE_TEMPORARY_TOKEN_FLAG_CODE);
        $token = json_decode($token, true);
        if(empty($token))
            throw new \Exception('No Token has ben set.', 400);

        return $token;
    }

    public function getConnectionUrlToFeedoptimise($token, $siteUrl)
    {
        return self::FEEDOPTIMISE_HOST . self::FEEDOPTIMISE_CONNECT_URL . '?token=' . urlencode($token) . '&site_url=' . urlencode($siteUrl);
    }

    public function getSiteUrl()
    {
        return $this->_storeManager->getStore()->getBaseUrl();
    }

	public function validateSettings($request = [])
	{
		if(!$this->getGeneralConfig('enable'))
		{
			return [
				'error' => true,
				'code' => 400,
				'error_msg' => 'Extension is disabled'
			];
		}
		else if(!$this->getGeneralConfig('security_token'))
		{
			return [
				'error' => true,
				'code' => 400,
				'error_msg' => 'Security token is empty (extension config)'
			];
		}
		else if(!isset($request['security_token']))
		{
			return [
				'error' => true,
				'code' => 401,
				'error_msg' => 'Please specify a security_token'
			];
		}
		else if($this->getGeneralConfig('security_token') !== $request['security_token'])
		{
			return [
				'error' => true,
				'code' => 401,
				'error_msg' => 'Security token does not match'
			];
		}
		else
		{
			return true;
		}
	}
}
