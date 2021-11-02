<?php
declare(strict_types=1);

namespace Comwrap\InventoryReservationCleanUp\Plugin\InventoryReservationsApi\Plugin;

use Closure;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\InventoryReservationsApi\Model\AppendReservationsInterface;
use Magento\InventoryReservationsApi\Model\ReservationInterface;

/**
 * Plugin that only allows "order_placed" events into the magento reservations table
 */
class RestrictShipmentReservationsPlugin
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @param SerializerInterface $serializer
     */
    public function __construct(
        SerializerInterface $serializer
    ) {
        $this->serializer = $serializer;
    }

    /**
     * Around plugin for \Magento\InventoryReservationsApi\Model\AppendReservationsInterface::execute() method
     *
     * @param AppendReservationsInterface $subject
     * @param Closure $proceed
     * @param ReservationInterface[] $reservations
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundExecute(AppendReservationsInterface $subject, Closure $proceed, array $reservations)
    {
        $reservationToAppend = [];
        foreach ($reservations as $reservation) {

            $metaData = $this->serializer->unserialize($reservation->getMetadata());
            if (is_array($metaData) && $metaData['event_type'] == 'order_placed') {
                $reservationToAppend[] = $reservation;
            }
        }

        if (!empty($reservationToAppend)) {
            $proceed($reservationToAppend);
        }
    }
}
