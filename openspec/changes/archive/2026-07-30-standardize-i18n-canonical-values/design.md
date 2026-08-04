## Context

Verified current state (2026-07-29):

**`type` (transactions & categories):**
- Both `transactions.type` and `categories.type` are DB-level `ENUM('income','expense')` already — the database has never stored `entrada`/`saida`.
- `TransactionTypeMapper::toApi()` (DB → API direction) is called in exactly one place in the entire codebase: `CsvReportWriter.php`. No controller (`TransactionController`, `CategoryController`, `DashboardController`, `MonthlySummaryController`) translates `type` before returning JSON — they already return the native `income`/`expense` DB value. So the JSON API contract is already correct; the CSV export is the only output-side leak.
- `TransactionTypeMapper::toDatabase()` (API → DB direction) and `incomeValues()`/`expenseValues()` (both aliases) are called from 6 Form Requests' `Rule::in(['entrada','saida','income','expense'])`, plus `TransactionController`, `CategoryController`, `TransactionFilterQuery`, `DashboardController`, and `MonthlySummaryController` — all accepting `entrada`/`saida` as valid *input* alongside `income`/`expense`.
- The frontend is the actual source of `entrada`/`saida` traffic: `ReportsPage.tsx`, `TransactionsPage.tsx`, and `CategoriesPage.tsx` hardcode `entrada`/`saida` as Select option values and local form state, then send them as request payloads/query params, relying on the backend's alias acceptance. `CategoriesPage.tsx` and `TransactionsPage.tsx` even round-trip API values through PT-BR internally (e.g. `TransactionsPage.tsx`'s `normalizeType()` converts `"income"` *into* `"entrada"` for internal comparisons) for no external reason — pure self-inflicted indirection.

**`payment_method` (transactions only):**
- Unlike `type`, this is genuinely stored in Portuguese at the DB level: `transactions.payment_method` is `ENUM('cartao_credito','cartao_debito','dinheiro','pix','boleto','ted')` with no English aliasing layer at all (no equivalent of `TransactionTypeMapper`).
- Validated identically (Portuguese-only `Rule::in`) in `StoreTransactionRequest`, `UpdateTransactionRequest`, `ListTransactionsRequest`, `GenerateReportRequest`.
- Frontend `TransactionsPage.tsx` defines `paymentMethods = ["cartao_credito", "cartao_debito", "dinheiro", "pix", "boleto", "ted"]` and interpolates it directly into the i18n key: `t(\`transactions.payment.${method}\`)`. `web/src/i18n/config.ts` has matching PT-keyed entries (e.g. `'transactions.payment.cartao_credito'`) in both the `pt-BR` and `en` blocks — i.e. even the English translation is keyed on a Portuguese identifier today.
- This one requires an actual data migration, not just a validation/frontend change, because real rows exist with these values.

**Related, explicitly out of scope:** `DefaultCategorySeeder` bakes category `name` (e.g. "Salário"/"Salary") into the row at seed time based on the user's locale at that moment — the name never re-translates if the user later switches UI language. That's a legitimate related gap, but it's about free-text/user-owned data (categories can be renamed by the user), not an enum-like code with a fixed set of values, so it doesn't fit this change's "value vs. label" fix mechanically. Left as a known follow-up, not a task here.

## Goals / Non-Goals

**Goals:**
- Make `type` and `payment_method` canonical English everywhere they're used as a *value* (storage, validation, filters, comparisons), with `t()` applied only where they become a *label*.
- Do this as a clean break (confirmed direction): stop accepting `entrada`/`saida`/Portuguese `payment_method` codes as input at all, rather than keeping them as permanent aliases. No mobile client exists yet to depend on the old contract.
- Migrate the one place with real stored Portuguese data (`payment_method`) safely, with existing rows rewritten, not just new rows going forward.
- Delete `TransactionTypeMapper` once it has no remaining callers, rather than leaving a dead abstraction around.

**Non-Goals:**
- Not touching category `name` localization (see Context) — different problem shape, tracked as a known gap, not a task.
- Not localizing the CSV/PDF report's static column headers (`'Date'`, `'Category'`, `'Type'`, `'Payment Method'`, `'Amount'`, `'Notes'` in `CsvReportWriter.php`) — those are already hardcoded English regardless of user locale, which is consistent with this change's canonical-English direction for values, and full report i18n (translating headers to the user's locale) is a separate presentational feature, not part of standardizing the value/label split.
- Not changing the `data`/`error` response envelope or any HTTP status/shape — only which literal strings are valid within the `type` and `payment_method` fields.
- Not part of the `enforce-strict-typing` change — that change handles `any`/strict-mode/PHPStan; this one only borrows its direction when tightening `payment_method`'s frontend type from `string` to a literal union, since we're already touching every reference.

## Decisions

**1. `type`: input-side and frontend cleanup only — no DB migration needed.**
Since the DB and JSON responses already use `income`/`expense` natively, the only work is: stop accepting the aliases in `Rule::in`, stop calling `TransactionTypeMapper` anywhere, and replace the frontend's self-inflicted `entrada`/`saida` literals with `income`/`expense`. This is safe and low-risk because it's tightening what's *accepted*, not changing what's *stored or returned* — no client relying on `entrada`/`saida` responses exists, since nothing ever emitted them except the CSV export.

**2. `TransactionTypeMapper` is deleted, not deprecated in place.**
Once no caller passes `entrada`/`saida`, `toDatabase()`/`toApi()`/`incomeValues()`/`expenseValues()` are all identity operations or single-value lookups — keeping the class around as a no-op would be exactly the kind of leftover abstraction to avoid. Call sites simplify: `TransactionFilterQuery`'s `where('type', TransactionTypeMapper::toDatabase($filters['type']))` becomes `where('type', $filters['type'])`; `DashboardController`/`MonthlySummaryController`'s `whereIn('type', TransactionTypeMapper::incomeValues())` becomes `where('type', 'income')` (single value, no longer `whereIn`); `PdfReportWriter`'s `$transaction->type === TransactionTypeMapper::toDatabase('income')` becomes `$transaction->type === 'income'`.

**3. `payment_method`: three-step migration in one file (widen → rewrite → narrow), not a shadow column.**
Alternative considered: add a new `payment_method_new` column, backfill, swap, drop old column — safer for very large tables but overkill here (no evidence of a large dataset, and `ENUM` alteration is a fast metadata-ish operation in MySQL for this row count). Chosen approach, within a single migration's `up()`:
   1. `MODIFY payment_method ENUM(<old 6 values>, <new 6 values>)` — widen so both old and new values are valid simultaneously.
   2. `UPDATE transactions SET payment_method = CASE payment_method WHEN 'cartao_credito' THEN 'credit_card' WHEN 'cartao_debito' THEN 'debit_card' WHEN 'dinheiro' THEN 'cash' WHEN 'boleto' THEN 'bank_slip' WHEN 'ted' THEN 'bank_transfer' ELSE payment_method END` (`pix` needs no mapping — identical in both languages).
   3. `MODIFY payment_method ENUM(<new 6 values only>) DEFAULT 'pix'` — narrow back down now that no row holds an old value.
   `down()` performs the same three steps in reverse for a clean rollback.

**4. Canonical English mapping for `payment_method`:**
| Old (pt-BR) | New (canonical English) |
|---|---|
| `cartao_credito` | `credit_card` |
| `cartao_debito` | `debit_card` |
| `dinheiro` | `cash` |
| `pix` | `pix` (unchanged) |
| `boleto` | `bank_slip` |
| `ted` | `bank_transfer` |

**5. i18n keys follow the value, not the other way around.**
`web/src/i18n/config.ts`'s `transactions.payment.*` keys are renamed to match the new codes (e.g. `transactions.payment.credit_card`), in both `pt-BR` and `en` blocks. The displayed text itself doesn't change — only the key it's addressed by, since the frontend interpolates the live value directly into the key (`t(\`transactions.payment.${method}\`)`).

## Risks / Trade-offs

- **[Risk]** The `payment_method` enum migration touches live transaction data — a mistake in the `CASE` mapping could silently mis-tag transactions → **Mitigation**: single migration file with widen/rewrite/narrow keeps old and new values valid simultaneously during the rewrite step (no window where an unmapped value would be rejected), and a symmetric `down()` allows a clean rollback if verification fails after deploy.
- **[Risk]** Removing `entrada`/`saida`/Portuguese `payment_method` acceptance is a breaking API input change — any external caller still sending old values gets a validation error instead of silent acceptance → **Mitigation**: confirmed acceptable (no mobile client or third party depends on this API yet); this is the explicit "clean break" direction chosen for this change.
- **[Risk]** Frontend and backend changes must land together (backend stops accepting old values the same moment frontend stops sending them) → **Mitigation**: this is a repo-local, single-deploy change (Docker-first, one `web` + one `api` release), not a phased multi-service rollout, so there's no meaningful window where old frontend talks to new backend or vice versa.
