<?php

namespace Feedoptimise\CatalogExport\Model\Source;

class SourceFactory
{
    protected $moduleManager;
    protected $objectManager;

    public function __construct(
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->moduleManager = $moduleManager;
        $this->objectManager = $objectManager;
    }

    public function create(array $data = array())
    {
        if ($this->isMISEnabled()) {
            if(!isset($data['type']) || $data['type'] == 'GetSourceItemsBySku')
                $instanceName =  '\Magento\Inventory\Model\SourceItem\Command\GetSourceItemsBySku';
            elseif ($data['type'] == 'SourceRepositoryInterface')
                $instanceName =  '\Magento\InventoryApi\Api\SourceRepositoryInterface';
            return $this->objectManager->create($instanceName, $data);
        }
        return false;
    }

    public function isMISEnabled()
    {
        return (
            $this->moduleManager->isEnabled('Magento_InventoryApi')
            && $this->moduleManager->isEnabled('Magento_Inventory')
        );
    }
}