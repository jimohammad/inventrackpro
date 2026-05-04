# APP_MAP.md

## Main Routing
- `index.php` routes by `?page=...&action=...`
- Controllers are in `app/controllers`
- Views are in `app/views`
- Models are in `app/models`

## Main Modules
### Sales
- Controller: `app/controllers/SalesController.php`
- Model: `app/models/Sale.php`
- Views: `app/views/sales`
- Key tables: `sales`, `sale_items`, `sale_item_imei`, `payments`, `imei_records`, `stock`
- Critical logic:
  - Sale deducts stock.
  - Sale marks IMEIs sold.
  - Payment updates sale balance and account balance.
  - Cancel sale restores stock and IMEIs.

### Purchases
- Controller: `app/controllers/PurchaseController.php`
- Views: `app/views/purchases`
- Key tables: `purchases`, `purchase_items`, `stock`, `imei_records`, `payments`
- Critical logic:
  - Purchase adds stock.
  - Purchase receiving may register IMEIs.

### Purchase Orders
- Controller: `app/controllers/PurchaseOrderController.php`
- Views: `app/views/purchase_orders`
- Key tables: `purchase_orders`, `purchase_order_items`, `payments`, `purchases`
- Critical logic:
  - PO payment deducts account.
  - Convert PO creates purchase invoice.
  - Conversion adds stock.

### Returns
- Controller: `app/controllers/ReturnController.php`
- Model: `app/models/Return.php`
- Views: `app/views/returns`
- Key tables: `returns`, `return_items`, `return_item_imei`, `stock`, `imei_records`
- Critical logic:
  - Sale return restores stock.
  - Sale return marks IMEIs back in stock.
  - Linked return reduces sale balance.

### Parties
- Controller: `app/controllers/PartyController.php`
- Model: `app/models/Party.php`
- Views: `app/views/parties`
- Key tables: `parties`, `sales`, `purchases`, `payments`, `returns`
- Critical logic:
  - Party balance must use directional payment logic.