<?php
declare(strict_types=1);

namespace Comwrap\InventoryReservationCleanUp\Model;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Serialize\SerializerInterface;

/**
 * Process inventory_reservation
 */
class InventoryReservationManagement
{
    public const INVENTORY_RESERVATION_TABLE = 'inventory_reservation';
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;
    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * ClearReservationPlugin constructor.
     * @param ResourceConnection $resourceConnection
     * @param SerializerInterface $serializer
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        SerializerInterface $serializer
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->serializer = $serializer;
    }

    /**
     * Is reservation for the item exist
     *
     * @param string $sku
     * @return bool
     */
    public function isReservationExist(string $sku): bool
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName(self::INVENTORY_RESERVATION_TABLE);
        $select = $connection->select()
            ->from(
                $tableName,
                ['reservation_id', 'stock_id', 'quantity', 'metadata']
            )
            ->where('sku = :sku');

        $reservations = $connection->fetchAssoc($select, [':sku' => $sku]);

        if ($reservations) {
            return true;
        }

        return false;
    }

    /**
     * Clean reservation by SKU
     *
     * @param string $sku
     * @return int
     */
    public function cleanReservationsForSku(string $sku): int
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName(self::INVENTORY_RESERVATION_TABLE);

        return $connection->delete($tableName, ['sku = ?' => $sku]);
    }
}
