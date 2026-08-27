## Why

The transactions filter section only exposes month and transaction type. Users can't narrow the list by category, amount range, a specific date window, or by searching notes/description, which makes it hard to find a specific transaction once the list grows. The backend (`TransactionFilterQuery`) already supports `category_id`, `date_from`, and `date_to`, but the frontend never surfaces them, and `amount` range / `notes` search don't exist as filters anywhere yet.

## What Changes

- Add a category filter (parent + subcategory select, reusing the same options already loaded for the create/edit form) to the transactions filters card.
- Add an amount range filter (`amount_min` / `amount_max`) so users can filter transactions between two values.
- Add a description/notes filter that does a partial, case-insensitive match against the transaction's `notes` field.
- Replace/extend the current single "month" input with an explicit date range (specific "from" and "to" days), reusing the existing `date_from` / `date_to` backend params instead of only whole-month filtering. Month filtering remains available as a convenience but becomes optional once a date range is set.
- Add a "clear filters" action so users can reset back to the default (current month) view.
- **BREAKING**: none — all new filters are additive and optional; existing `GET /transactions` behavior without the new params is unchanged.

## Capabilities

### New Capabilities
(none — this extends the existing transaction listing/filtering behavior)

### Modified Capabilities
- `financial-ledger`: transaction list filtering gains `category_id`-in-UI, `amount_min`/`amount_max`, `notes` (description) search, and an explicit date-range filter alongside the existing month/type/installment filters.

## Impact

- Backend: `App\Support\TransactionFilterQuery`, `App\Http\Requests\Api\V1\ListTransactionsRequest` (new `amount_min`, `amount_max`, `notes` validation rules and query conditions).
- Frontend: `web/src/pages/TransactionsPage.tsx` filters card, `web/src/hooks/api.ts` (`useTransactions` params type), `web/src/types/api.ts` if a shared filter params type exists.
- i18n: new translation keys for the added filter labels/placeholders in `web/src/i18n/config.ts`.
- No database schema changes — all filtered fields already exist on the `transactions` table.
