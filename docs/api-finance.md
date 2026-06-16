# Finance API v1

Base URL: `/api/v1`

## Auth

- `POST /auth/register`
  - Body: `{ "name": "Lucas", "email": "lucas@example.com", "password": "password123" }`
  - Returns: `{ "data": { "token": "...", "user": { ... } } }`
- `POST /auth/login`
  - Body: `{ "email": "lucas@example.com", "password": "password123" }`
  - Returns: `{ "data": { "token": "...", "user": { ... } } }`
- `GET /auth/me` (Bearer token)
- `POST /auth/logout` (Bearer token)

## Categories

- `GET /categories`
- `POST /categories`
  - Body: `{ "name": "Food", "type": "expense" }`
- `GET /categories/{id}`
- `PUT /categories/{id}`
- `DELETE /categories/{id}`

## Transactions

- `GET /transactions`
- `POST /transactions`
  - Body: `{ "category_id": 1, "type": "expense", "amount": 42.90, "transacted_at": "2026-06-15", "notes": "Lunch" }`
- `GET /transactions/{id}`
- `PUT /transactions/{id}`
- `DELETE /transactions/{id}`

## Budgets

- `GET /budgets?year=2026&month=6`
- `POST /budgets`
  - Body: `{ "category_id": 1, "year": 2026, "month": 6, "amount": 600 }`

## Monthly summary

- `GET /summaries/monthly?year=2026&month=6`

Response example:

```json
{
  "data": {
    "year": 2026,
    "month": 6,
    "income_total": 2000,
    "expense_total": 300,
    "net_balance": 1700,
    "category_variance": [
      {
        "category_id": 1,
        "category_name": "Food",
        "budget": 500,
        "actual": 300,
        "variance": 200
      }
    ]
  }
}
```

## Error format

All error responses follow:

```json
{
  "error": {
    "type": "validation_error",
    "message": "Validation failed.",
    "details": {
      "field": ["Message"]
    }
  }
}
```

## Web-first rollout notes

- Frontend uses `VITE_API_URL` pointing to this API (default local: `http://localhost:8000/api`).
- Keep `/api/v1` contract stable for mobile reuse; add new fields additively and avoid breaking response envelope.
- Launch checklist for web:
  - Apply latest migrations.
  - Confirm API health and auth flow (`/auth/register`, `/auth/login`, `/auth/me`).
  - Verify transaction/budget/summary flows for current month from the SPA.
