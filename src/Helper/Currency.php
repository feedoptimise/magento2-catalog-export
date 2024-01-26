<?php

namespace Feedoptimise\CatalogExport\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

class Currency extends AbstractHelper
{
    protected $_storeManager;
    protected $_currency;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Directory\Model\Currency $currency,
        array $data = []
    )
    {
        $this->_storeManager = $storeManager;
        $this->_currency = $currency;

        parent::__construct($context, $data);
    }

    public function getCurrencyPriceForProduct($product)
    {
        $defaultCurrency = $this->getDefaultCurrencyCode();

        $availableCurrency = $this->getAvailableCurrencyCodes(true);
        $result = [];
        $currencyModel = $this->_currency->load($defaultCurrency);
        $finalePrice = $product->getFinalPrice(1);
        $price = $product->getPrice();
        foreach ($availableCurrency as $currency){
            if($currency == $defaultCurrency){
                $result[] = [
                  'price' => $price,
                  'finale_price' => $finalePrice,
                  'rate' => 1,
                  'code' => $defaultCurrency,
                  'is_default' => 1
                ];
            }else{
                $rate = $currencyModel->getRate($currency);
                $result[] = [
                    'price' => ($rate)?((float)$price * (float)$rate):1,
                    'finale_price' => ($rate)?((float)$finalePrice * (float)$rate):1,
                    'rate' => $rate,
                    'code' => $currency,
                    'is_default' => 0
                ];
            }
        }

        return $result;
    }

    /**
     * Get store base currency code
     *
     * @return string
     */
    public function getBaseCurrencyCode()
    {
        return $this->_storeManager->getStore()->getBaseCurrencyCode();
    }

    /**
     * Get current store currency code
     *
     * @return string
     */
    public function getCurrentCurrencyCode()
    {
        return $this->_storeManager->getStore()->getCurrentCurrencyCode();
    }

    /**
     * Get default store currency code
     *
     * @return string
     */
    public function getDefaultCurrencyCode()
    {
        return $this->_storeManager->getStore()->getDefaultCurrencyCode();
    }

    /**
     * Get allowed store currency codes
     *
     * If base currency is not allowed in current website config scope,
     * then it can be disabled with $skipBaseNotAllowed
     *
     * @param bool $skipBaseNotAllowed
     * @return array
     */
    public function getAvailableCurrencyCodes($skipBaseNotAllowed = false)
    {
        return $this->_storeManager->getStore()->getAvailableCurrencyCodes($skipBaseNotAllowed);
    }

    /**
     * Get array of installed currencies for the scope
     *
     * @return array
     */
    public function getAllowedCurrencies()
    {
        return $this->_storeManager->getStore()->getAllowedCurrencies();
    }

    /**
     * Get current currency rate
     *
     * @return float
     */
    public function getCurrentCurrencyRate()
    {
        return $this->_storeManager->getStore()->getCurrentCurrencyRate();
    }
}
