## Why

There is no standard for how the app separates a value used by logic (filters, storage, comparisons) from a value used for display, and today the two are frequently the same Portuguese string — e.g. transaction/category `type` is compared and filtered as `"entrada"`/`"saida"` in several frontend pages, and `payment_method` is stored in the database and validated as raw Portuguese words (`cartao_credito`, `dinheiro`, `boleto`, `ted`). This means the app's internal logic is language-dependent, and adding a second UI language (or auditing the code) requires reading Portuguese to understand control flow. The standard going forward: all code and stored/wire values are canonical `en-US`, and translation happens only in the presentation layer (`t()`) to produce the visible label — never the underlying value.

## What Changes

- Establish the standing rule: enum-like domain values (`type`, `payment_method`, and any future field of this kind) SHALL be canonical English identifiers everywhere — database, validation, API request/response, and frontend logic. Only the rendered label goes through `t()`.
- **BREAKING**: `type` — stop accepting `entrada`/`saida` as valid input anywhere (`Rule::in` validation across 6 Form Requests currently accepts both). The database and JSON responses already use `income`/`expense` natively (verified: `TransactionTypeMapper::toApi()` is only ever called in the CSV report writer — no JSON endpoint currently outputs `entrada`/`saida`), so this is an input-side and frontend-internal cleanup, not a stored-data migration.
- **BREAKING**: `payment_method` — the `transactions.payment_method` column is a MySQL `ENUM` genuinely stored in Portuguese (`cartao_credito`, `cartao_debito`, `dinheiro`, `pix`, `boleto`, `ted`). Migrate existing data and the enum definition to English canonical values (`credit_card`, `debit_card`, `cash`, `pix`, `bank_slip`, `bank_transfer` — `pix` is unchanged, it's a proper noun used identically in both languages), and update validation (4 Form Requests) to match.
- Remove `TransactionTypeMapper` entirely once its only remaining callers (`entrada`/`saida` aliasing) are gone — it becomes a no-op after the input-side cleanup.
- Frontend: replace every `"entrada"`/`"saida"` literal (Select option values, form state, ternary comparisons) in `ReportsPage.tsx`, `TransactionsPage.tsx`, `CategoriesPage.tsx`, `HomePage.tsx` with `"income"`/`"expense"`, matching what the API already returns. Delete the now-pointless `normalizeType()` helper in `TransactionsPage.tsx` and the equivalent PT/EN round-trip in `CategoriesPage.tsx`.
- Frontend: rename the `paymentMethods` constant array in `TransactionsPage.tsx` to the new English codes, and rename the corresponding `transactions.payment.*` i18n keys in `web/src/i18n/config.ts` (both `pt-BR` and `en` blocks) to match — the display text for each key is unchanged, only the key/value it's keyed on changes.
- Tighten `payment_method`'s frontend type from `string` to a literal union of the new canonical values (`web/src/types/api.ts`), consistent with the stricter-typing effort in the separate `enforce-strict-typing` change.

## Capabilities

### New Capabilities
- `i18n-canonical-values`: codebase-wide requirement that domain enum values are canonical `en-US` strings, with translation applied only at the display/label layer, never to the underlying value used by logic, storage, or the API contract.

### Modified Capabilities
- `financial-ledger`: transaction `payment_method` and `type` values are now constrained to canonical English identifiers end-to-end (previously `payment_method` was stored/validated in Portuguese, and `type` accepted Portuguese aliases as input).

## Impact

- DB migration on `transactions.payment_method` (widen enum → rewrite existing rows → narrow enum to English-only values).
- `api/app/Support/TransactionTypeMapper.php`: deleted.
- `api/app/Http/Requests/Api/V1/{Store,Update}TransactionRequest.php`, `{Store,Update}CategoryRequest.php`, `ListTransactionsRequest.php`, `GenerateReportRequest.php`: validation rules updated (drop `entrada`/`saida`; rename `payment_method` values).
- `api/app/Http/Controllers/Api/V1/{Transaction,Category,Dashboard,MonthlySummary}Controller.php`, `api/app/Support/TransactionFilterQuery.php`, `api/app/Support/Reports/{Csv,Pdf}ReportWriter.php`: drop `TransactionTypeMapper` calls.
- `web/src/pages/{Reports,Transactions,Categories,Home}Page.tsx`: literal value cleanup.
- `web/src/i18n/config.ts`: rename `transactions.payment.*` keys.
- `web/src/types/api.ts`: tighten `payment_method` type.
- No change to the JSON response shape or the `data`/`error` envelope — only which literal strings are valid values within existing fields.
