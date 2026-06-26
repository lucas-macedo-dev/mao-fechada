## Purpose

Defines requirements for the Mercado Pago payment integration scaffold, including SDK setup, webhook ingestion, and a Payment Methods UI page — without enforcing any payment gate on application access.

## Requirements

### Requirement: Mercado Pago integration scaffold is present but not enforced
The system SHALL have the Mercado Pago SDK installed and configured, a webhook ingestion endpoint registered, and a Payment Methods UI page accessible in settings — but SHALL NOT require or enforce any payment to use the application.

#### Scenario: Webhook endpoint accepts and acknowledges Mercado Pago notifications
- **WHEN** Mercado Pago sends a POST request to `/v1/webhooks/mercadopago`
- **THEN** the system logs the raw payload and returns HTTP 200 without further processing

#### Scenario: Payment Methods page is accessible in settings
- **WHEN** an authenticated user navigates to the Payment Methods section in settings
- **THEN** the system displays the Payment Methods page skeleton with a placeholder indicating payment configuration is coming soon

#### Scenario: Application access is not gated by payment status
- **WHEN** any authenticated and email-verified user accesses protected routes
- **THEN** the system grants access regardless of payment or subscription status
