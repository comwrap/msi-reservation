<?php
declare(strict_types=1);

namespace Comwrap\InventoryReservationCleanUp\Observer;

use Comwrap\InventoryReservationCleanUp\Model\InventoryReservationManagement;
use Exception;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Validation\ValidationException;
use Magento\Sales\Model\Order\Shipment;
use Magento\InventorySourceDeductionApi\Model\GetSourceItemBySourceCodeAndSku;
use Magento\InventoryCatalogApi\Model\IsSingleSourceModeInterface;
use Magento\InventoryCatalogApi\Api\DefaultSourceProviderInterface;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Psr\Log\LoggerInterface;

/**
 * Compensate source quantity if stock was updated by external API call before
 */
class SalesOrderShipmentSaveBefore implements ObserverInterface
{
    /**
     * @var GetSourceItemBySourceCodeAndSku
     */
    protected $getSourceItemBySourceCodeAndSku;

    /**
     * @var IsSingleSourceModeInterface
     */
    protected $isSingleSourceMode;

    /**
     * @var DefaultSourceProviderInterface
     */
    protected $defaultSourceProvider;

    /**
     * @var SourceItemsSaveInterface
     */
    protected $sourceItemsSave;

    /**
     * @var InventoryReservationManagement
     */
    private $inventoryReservationManagement;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * SalesOrderShipmentSaveBefore constructor.
     * @param GetSourceItemBySourceCodeAndSku $getSourceItemBySourceCodeAndSku
     * @param IsSingleSourceModeInterface $isSingleSourceMode
     * @param DefaultSourceProviderInterface $defaultSourceProvider
     * @param SourceItemsSaveInterface $sourceItemsSave
     * @param InventoryReservationManagement $inventoryReservationManagement
     * @param LoggerInterface $logger
     */
    public function __construct(
        GetSourceItemBySourceCodeAndSku $getSourceItemBySourceCodeAndSku,
        IsSingleSourceModeInterface $isSingleSourceMode,
        DefaultSourceProviderInterface $defaultSourceProvider,
        SourceItemsSaveInterface $sourceItemsSave,
        InventoryReservationManagement $inventoryReservationManagement,
        LoggerInterface $logger
    ) {
        $this->getSourceItemBySourceCodeAndSku = $getSourceItemBySourceCodeAndSku;
        $this->isSingleSourceMode = $isSingleSourceMode;
        $this->defaultSourceProvider = $defaultSourceProvider;
        $this->sourceItemsSave = $sourceItemsSave;
        $this->inventoryReservationManagement = $inventoryReservationManagement;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function execute(Observer $observer)
    {
        /**
         * @var Shipment $shipment
         */
        $shipment = $observer->getEvent()->getData('shipment');

        if ($shipment) {
            $shipmentItems = $shipment->getItems();

            if (is_array($shipmentItems) && count($shipmentItems) > 0) {
                $sourceCode = '';

                if (!empty($shipment->getExtensionAttributes())
                    && !empty($shipment->getExtensionAttributes()->getSourceCode())) {
                    $sourceCode = $shipment->getExtensionAttributes()->getSourceCode();
                } elseif ($this->isSingleSourceMode->execute()) {
                    $sourceCode = $this->defaultSourceProvider->getCode();
                }

                foreach ($shipmentItems as $shipmentItem) {
                    $sourceItem = null;
                    $sku = $shipmentItem->getSku();

                    try {
                        $sourceItem = $this->getSourceItemBySourceCodeAndSku->execute(
                            $sourceCode,
                            $sku
                        );
                    } catch (Exception $e) {
                        $this->logger->error($e->getLogMessage());
                    }

                    if ($sourceItem && !$this->inventoryReservationManagement->isReservationExist($sku)) {
                        $compensationQty = (float)$sourceItem->getQuantity() + (float)$shipmentItem->getQty();
                        $sourceItem->setQuantity($compensationQty);
                        try {
                            $this->sourceItemsSave->execute([$sourceItem]);
                        } catch (CouldNotSaveException | InputException | ValidationException $e) {
                            $this->logger->error($e->getLogMessage());
                        }
                    }
                }
            }
        }
    }
}
