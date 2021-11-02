# Module to align MSI reservation logic

We are noticing an issue where the default behaviour of MSI (no sources or stock set up) is that as an item is sold, it adds a record to `inventory_reservation` table. And salable quantity is reduced.

We manage our stock in another system, and update the quantity field as orders come in. This is causing issues as suddenly the salable quantities are lowering, and never reset.

Taken the following scenario:

  * We have a stock of 2 Product A 
  * User buys product A
  * Magento decreases salable quantity by 1 resulting in 1 salable unit
  * External system decreases salable quantity by 1 resulting in 1 salable unit
  * External system pushes stock level back to Magento
  * Product A is no longer salable since the quantity is 1, reservation is 1, and salable quantity is 0

The behavior above is amended by the current module. Now scenario is the following:

* Added a plugin that only allows "order_placed" events into the magento reservations table (this enables magento to continue to provide accurate saleable qty between erp qty updates)
`\Comwrap\InventoryReservationCleanUp\Plugin\InventoryReservationsApi\Plugin\RestrictShipmentReservationsPlugin`

* Added function to clear the magento reservations table by sku whenever we update qty from the ERP integration for that sku
`\Comwrap\InventoryReservationCleanUp\Plugin\InventoryApi\ClearReservationPlugin`
  
* Added observer to compensate qty during shipment processing if qty was already updated by ERP
`\Comwrap\InventoryReservationCleanUp\Observer\SalesOrderShipmentSaveBefore`
