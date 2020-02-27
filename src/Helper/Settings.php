<?php
/**
 * @author Joe Yates (Feedoptimise)
 * @copyright Copyright (c) 2020 Feedoptimise (http://www.feedoptimise.com)
 * @package Feedoptimise_CatalogExport
 */

namespace Feedoptimise\CatalogExport\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class Settings extends AbstractHelper
{
	const XML_PATH_EXPORT = 'feedoptimise_catalog_export/';

	public function getConfigValue($field, $storeId = null)
	{
		return $this->scopeConfig->getValue(
			$field, ScopeInterface::SCOPE_STORE, $storeId
		);
	}

	public function getGeneralConfig($code, $storeId = null)
	{
		return $this->getConfigValue(self::XML_PATH_EXPORT .'general/'. $code, $storeId);
	}

	public function validateSettings()
	{
		$_POST = json_decode(file_get_contents('php://input'), true);

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
		else if(!isset($_POST['security_token']))
		{
			return [
				'error' => true,
				'code' => 401,
				'error_msg' => 'Please specify a security_token'
			];
		}
		else if($this->getGeneralConfig('security_token') !== $_POST['security_token'])
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
