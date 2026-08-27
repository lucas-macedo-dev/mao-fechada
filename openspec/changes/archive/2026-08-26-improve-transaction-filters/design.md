## Context

Filtering lives in `App\Support\TransactionFilterQuery::apply()`, driven by `ListTransactionsRequest` validation and called from `TransactionController@index`. The frontend only sends `month`, `type`, and `installment` today (`web/src/pages/TransactionsPage.tsx`, `web/src/hooks/api.ts#useTransactions`), even though `category_id`, `date_from`, and `date_to` are already implemented server-side and unused by the UI. The app runs MySQL in production (`DB_CONNECTION=mysql`) and SQLite in tests, so filter query logic needs to behave the same on both drivers.

## Goals / Non-Goals

**Goals:**
- Reuse the existing `date_from`/`date_to` and `category_id` backend params from the UI instead of re-implementing them.
- Add `amount_min`, `amount_max`, and `notes` as new backend filter params with the same "all filters are optional and AND-combined" model as the existing ones.
- Keep the filters card usable on mobile (it's currently a 2-column `SimpleGrid`).

**Non-Goals:**
- No changes to how `month` filtering itself works — it stays as a convenience default; it does not need to be mutually exclusive with `date_from`/`date_to` at the API level (if both are sent, both conditions apply, which is a query the UI simply won't produce since it treats them as alternate modes).
- No full-text search infrastructure for `notes` — a simple `LIKE` is sufficient at this data scale (a single user's personal transactions).
- No saved/named filter presets — out of scope for this change.

## Decisions

- **Case-insensitive `notes` match via `whereRaw('LOWER(notes) LIKE ?', ...)`** rather than relying on column collation. MySQL's default collation is usually case-insensitive already, but SQLite (used in tests) is case-sensitive by default for `LIKE` on non-ASCII and inconsistent across environments — normalizing with `LOWER()` on both sides keeps behavior identical on both drivers without depending on DB config. The search term itself is also lowercased in PHP before binding, and `%`/`_` wildcard characters in the raw input are escaped so user input can't inject SQL LIKE wildcards.
- **`amount_min`/`amount_max` validated as `numeric`, `min:0`, with a cross-field rule** (`amount_max` must be `>= amount_min` when both present) enforced in `ListTransactionsRequest` via `Rule::when`/`gte:amount_min`, returning a standard 422 validation error — consistent with how the existing `date_from`/`date_to` pair is handled (no cross-validation today, kept consistent by *not* adding one for dates, only for amounts since an inverted amount range is unambiguously a user input mistake).
- **UI keeps `month` as the default filter and treats the new date-range inputs as an alternate, explicit mode**: selecting a "from"/"to" day clears the `month` param client-side (and vice versa) so the two never combine unintentionally from the UI, even though the API itself doesn't forbid combining them. This avoids a confusing "why did my range filter get ignored" UX without adding backend complexity.
- **Category filter reuses `categories` already fetched by `useCategories()`** for the create/edit form — same parent/subcategory two-select pattern already built for the form, applied to the filter card, rather than a new component.
- **Debounce the description (`notes`) text input** (~400ms) before triggering a refetch, same as any typeahead, to avoid firing a request per keystroke against `useTransactions`.

## Risks / Trade-offs

- [`LIKE '%term%'` on `notes` does a full scan per request] → Acceptable at per-user personal-finance scale (hundreds to low thousands of rows); revisit with an index or search service only if this becomes a real bottleneck.
- [More filter params increase the query string / cache-key surface for `useTransactions`] → React Query already keys on the full params object, so this is automatic; no extra work needed.
- [Adding a cross-field validation rule (`amount_max >= amount_min`) is one more thing that can reject a request users didn't expect] → Mitigated by mirroring the error back into the same filters card so the user sees why immediately, same pattern as existing field-level validation errors.

## Migration Plan

Additive only — new optional query params and new optional UI inputs. No data migration, no breaking change to `GET /transactions` callers that don't send the new params. Ship backend and frontend together; no feature flag needed given the small blast radius.
