## 1. Backend filter support

- [x] 1.1 Add `amount_min`, `amount_max`, and `notes` validation rules to `ListTransactionsRequest` (`numeric`/`min:0` for amounts, cross-field rule rejecting `amount_max < amount_min`; `string`/`max:255` for `notes`)
- [x] 1.2 Extend `TransactionFilterQuery::apply()` to filter by `amount_min` (`>=`), `amount_max` (`<=`), and a case-insensitive `notes` LIKE match (lowercased both sides, with `%`/`_` escaped in the bound term)
- [x] 1.3 Add/extend feature tests for `GET /transactions` covering: amount range (min only, max only, both), inverted range rejection (422), notes partial match (case-insensitive), date range independent of `month`, and combining several filters at once

## 2. Frontend data layer

- [x] 2.1 Extend the `useTransactions` params type in `web/src/hooks/api.ts` with `amount_min?`, `amount_max?`, `notes?`, `date_from?`, `date_to?`
- [x] 2.2 Add new i18n keys for the filter labels/placeholders ("Category", "Min amount", "Max amount", "Description", "From", "To", "Clear filters") to both locale blocks in `web/src/i18n/config.ts`

## 3. Transactions filters UI

- [x] 3.1 Add category (parent + subcategory) selects to the filters `SectionCard` in `TransactionsPage.tsx`, reusing the same category list already loaded via `useCategories()`
- [x] 3.2 Add min/max amount `NumberInput`s to the filters card, wired into `params`
- [x] 3.3 Add a debounced (~400ms) description `TextInput` that sets the `notes` param
- [x] 3.4 Add "from"/"to" date inputs that set `date_from`/`date_to`, and make them mutually exclusive with `month` on the client (selecting a date range clears `month`; selecting a month clears the date range)
- [x] 3.5 Add a "Clear filters" button that resets all filter state (including the new ones) back to the default (current month, no other filters) and refetches
- [x] 3.6 Give the new filter inputs `id`s following the existing `transactions-*` id convention on this page

## 4. Verification

- [x] 4.1 Run backend test suite (`php artisan test` or project equivalent) and confirm new/updated tests pass
- [x] 4.2 Run `npx tsc --noEmit` in `web/` to confirm no type errors
- [x] 4.3 Exercised each new filter (category, amount range, description, date range, combined) against the real running stack (nginx + PHP-FPM + MySQL, `docker exec`) via direct API calls — all matched expectations; test data cleaned up afterward. UI click-through in an actual browser not performed (no browser available in this session) — recommend a quick manual pass on the Transactions page
