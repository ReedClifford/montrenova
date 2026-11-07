# Preorders

## Admin workflow

1. Open **Watch Stocks**, choose a sold watch and select **Preorder listing**. Alternatively, open **Preorders → Publish a watch** and search the sold models.
2. Set the preorder price and arrival estimate. Publishing copies the watch specifications and photos to an independent listing; it does not change the original sale, buyer or warranty. Hide a listing to stop public inquiries without affecting existing orders.
3. Customers see **Preorder** in the Collection, the 20% deposit requirement, and a Messenger inquiry button. Messenger opens a prepared inquiry; it does not automatically create or confirm an order.
4. After agreeing with the customer, create a **Customer order**. Set the agreed price, customer contact and estimated arrival. The deposit defaults to 20%; an admin can set the agreed percentage. Subsequent listing price changes do not change existing orders.
5. Use **Record payment** only for money received. Include the date, method, reference and optional private proof. Partial payments accumulate, balance updates automatically, and reaching the required deposit confirms the order.
6. Mark the order **Ordered**, record paid supplier and direct costs (including shipping), then mark it **Arrived**.
7. Collect the balance and record every acquisition cost before fulfillment. Enter the actual serial number and confirm handover. Fulfillment creates exactly one new sold watch; the original sold watch remains unchanged. The handover date becomes the sale date and the start of the existing one-year warranty.

Order flow: **Awaiting deposit → Confirmed → Ordered → Arrived → Fulfilled**. Payment state is tracked independently. Fulfillment requires full payment and recorded supplier costs.

## Accounting

- Unfulfilled receipts are customer deposit liabilities, not sales or profit.
- Pending supplier spending reduces cash but is not also recorded as an expense.
- On fulfillment, the agreed total is recorded once as sales; all paid supplier and direct costs become the new watch's capital. Deposits are already part of that total.
- Do **not** record preorder supplier costs again in the Expenses page.
- Cash reports include pending receipts less refunds and pending supplier spending. Investor fund value includes the pending supplier cost asset and excludes the customer deposit liability.
- Investor dashboards keep their existing date-cutoff scope. A fulfilled watch and cancellation expense inherit the order's creation date for scope; their sale/spend dates still reflect actual fulfillment/cancellation.
- Each investor dashboard's **Expenses** tab shows every operating expense and paid preorder supplier/direct cost in its own scope. Category totals, search, cost-type and date filters are available; the default view has no month/year restriction or row limit. Capital costs are labelled separately from operating expenses. Cancellation costs appear once, with their original supplier payments expandable beneath the expense.
- Example: ₱20,000 agreed price, ₱4,000 deposit, ₱13,500 total paid costs → ₱16,000 balance and ₱6,500 gross profit at fulfillment. General expenses are deducted separately.

## Cancellation

Cancellation is final. The confirmation explicitly shows the refundable customer deposit and supplier spending that will be written off. Recorded supplier spending becomes one locked cancellation expense. Do not cancel as a write-off if you are still arranging supplier recovery or retaining a watch for resale.

Refunds are recorded separately after money is returned, including partial refunds. The system keeps the remaining deposit as a refund liability until fully refunded; it does not automatically treat deposits as profit.

## Integrity and access

- Only admins can manage orders, record transactions or download payment proofs.
- Payment proofs use private local storage. Public listings contain no original buyer, serial or capital data.
- Payment entries are append-only; repeated submissions with the same request key cannot double-record money.
- Payments/refunds cannot exceed the outstanding balance/deposit. Fulfilled orders are locked.
- Linked sold-watch financial details and cancellation expenses cannot be edited/deleted through existing admin actions. Listing photos remain independent from original stock files.

## Deployment and verification

Run `php artisan migrate` and `npm run build` when deploying. The preorder migration adds three tables and a nullable preorder link on expenses; it does not modify original sales.

Run `php vendor/bin/phpunit tests/Feature/PreorderTest.php` for the preorder integration tests. The configured test database is in-memory SQLite.
