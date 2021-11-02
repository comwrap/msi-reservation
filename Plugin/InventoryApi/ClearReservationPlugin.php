<?php
declare(strict_types=1);

namespace Comwrap\InventoryReservationCleanUp\Plugin\InventoryApi;

use Comwrap\InventoryReservationCleanUp\Model\InventoryReservationManagement;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;

/**
 * Plugin to clear the magento reservations table by sku whenever we update qty from the ERP integration for that sku
 */
class ClearReservationPlugin
{
    /**
     * @var InventoryReservationManagement
     */
    private $inventoryReservationManagement;

    /**
     * ClearReservationPlugin constructor.
     * @param InventoryReservationManagement $inventoryReservationManagement
     */
    public function __construct(InventoryReservationManagement $inventoryReservationManagement)
    {
        $this->inventoryReservationManagement = $inventoryReservationManagement;
    }

    /**
     * Before plugin for \Magento\InventoryApi\Api\SourceItemsSaveInterface::execute() method
     *
     * @param SourceItemsSaveInterface $subject
     * @param SourceItemInterface[] $sourceItems
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeExecute(SourceItemsSaveInterface $subject, array $sourceItems): array
    {
        foreach ($sourceItems as $sourceItem) {
            /** SourceItemInterface $sourceItem */
            $this->inventoryReservationManagement->cleanReservationsForSku($sourceItem->getSku());
        }
        return [$sourceItems];
    }
}
