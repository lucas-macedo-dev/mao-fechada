## Why

Users who make credit card purchases in installments (parcelamento) currently have no way to register and track these as recurring monthly entries — they must manually create each installment, leading to incomplete financial records and no visibility into total installment debt. This feature brings automated installment tracking natively into the transaction flow.

## What Changes

- The new transaction form gains conditional installment fields when the payment method is "Cartão de Crédito" and transaction type is expense
- A toggle activates installment mode; when on, the user provides the current installment number and the total number of installments
- On save, the system auto-generates all installment records: past months (already elapsed), the current month, and future months — each as a linked transaction entry
- The transactions page gains a filter to show only installment-type expenses
- The home page dashboard gains a summary card showing the total sum of active installment purchases for the selected month

## Capabilities

### New Capabilities
- `credit-card-installments`: Full lifecycle management of credit card installment purchases — form inputs, automatic generation of linked installment records across months, transactions filter, and home dashboard summary card.

### Modified Capabilities
- `financial-ledger`: Transaction creation now supports an installment group concept — linked transactions share a group ID, installment number, and total installment count. Filter API extended to support installment-type queries.
- `dashboard-charts`: Home dashboard gains a new summary card for total monthly installment spend.

## Impact

- **Backend (Laravel API)**:
  - `transactions` table: new columns `installment_group_id`, `installment_number`, `installment_total`
  - Transaction store endpoint: detects installment payload and bulk-creates linked records across months
  - Transaction list/filter endpoint: new filter param `installment=true`
  - New or extended summary endpoint: returns monthly installment total for dashboard card
- **Frontend (React)**:
  - `NewTransactionForm`: conditional installment toggle + inputs
  - `TransactionsPage`: new filter chip/select for installment expenses
  - `HomePage`: new summary card component consuming installment total from API
- **No breaking changes to existing transaction schema** — new columns are nullable/optional
