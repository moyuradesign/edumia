# Edumia commercial accounting and Bakong integration

**Status:** source-development milestone (not a production payment certification).

## Implemented

- On a **verified successful Stripe payment**, the same database transaction creates the paid order, existing payment history, customer invoice snapshot, and a signed integer-minor-unit instructor earning entry.
- Invoice identifiers are unique per order. The buyer can view only their invoice through an authenticated route. The record explicitly states **tax not assessed**, not a jurisdiction-approved tax invoice.
- Commission defaults to 20% via operator environment variable EDUMIA_COMMISSION_BPS=2000. A product's instructor/author owns the earning entry. All earnings are marked **held**.
- Signed charge.refunded and charge.dispute.created events are independently confirmed against Stripe's API before local changes. Partial refunds create cumulative negative earning entries; full refunds revoke payment-linked learning access and certificates. Disputes suspend payment-linked access. Duplicate provider events do not duplicate ledger entries.
- A read-only administrator Accounting page shows invoice records, held earnings, payout ledger records, and discrepancies between paid orders, payment history, invoice and earning records. Administrators may scan local data to persist reconciliation exception status.
- The instructor payout schema is present, but **disbursement and withdrawals are intentionally disabled**. Provider reconciliation, payout approval, account verification, VAT/tax, fee settlement, reserve periods, and manual refund workflows remain outstanding.
- Database migrations are additive and source filenames are descriptive and version-free apart from Laravel's required timestamped migration filenames.

## Cambodia / Bakong status adapter

The National Bank of Cambodia Open API documents a POST /v1/check_transaction_by_md5 endpoint that accepts a bearer token and a QR-derived MD5. A result may include sender, recipient, currency, amount, tracking status, and a transaction hash.

Source: https://sit-api-bakong.nbc.gov.kh/document

Edumia now contains a **read-only, fail-closed BakongStatusGateway** which can independently query that endpoint using an operator's test token, confirm the designated merchant receiving account, currency, amount and tracking state, and return a match **signal**. It does **not** create a KHQR QR payload, accept customer QR screenshots, initiate a debit, or mark an order paid. This is deliberate: without unique trusted dynamic QR generation, binding and transaction-replay controls, a reusable static QR or copied hash could misassign customer payments.

Configuration (unset by default):

\`\`\`dotenv
BAKONG_ENABLED=false
BAKONG_BASE_URL=https://sit-api-bakong.nbc.gov.kh
BAKONG_ACCESS_TOKEN=
BAKONG_RECEIVER_ACCOUNT=
\`\`\`

Required before public KHQR payments: merchant/partner authorization, API token provisioning, per-order dynamic QR issuance via the official KHQR requirements, trusted QR payload/hash binding to one order, expiry, duplicate-transaction prevention, receiver/currency/amount validation, production endpoint and settlement acceptance. Then integrate into PaymentFulfillmentService under an explicit provider-specific trusted confirmation method, with negative and replay tests. Never treat a user-supplied MD5 as payment proof.

## Refund / dispute operations

For Stripe, subscribe to:
- \`checkout.session.completed\`
- \`checkout.session.async_payment_succeeded\`
- \`checkout.session.expired\`
- \`charge.refunded\`
- \`charge.dispute.created\`

The webhook signature is checked on the exact raw body; adjustments are confirmed through authenticated provider API reads. Stripe dashboard refund initiation is **not** exposed in Edumia yet. A full refund/dispute preserves original accounting history instead of deleting a sale; access is revoked only for a specifically identified paid entitlement. Partial refunds leave the learner's access unchanged pending explicit business policy.

Dispute resolution, reopening entitlements after a successful appeal, already-paid-out earnings clawback, formal credit notes, and operator response workflows are **not implemented**. Review provider dashboards and transactions manually in sandbox until these features pass acceptance.

## Verification

\`\`\`bash
composer install
npm ci && npm run build
php artisan migrate --force
php artisan test --fail-on-warning
php artisan route:list --path=commerce
\`\`\`

Run on an **isolated QA database**. This is not permission to upgrade or migrate the installed edumia.tonsamith.com server. Current tests fake external payment provider responses. No real Bakong account or Stripe merchant sandbox payment has been executed.

## Next development sequence

1. Provider-specific transaction settlement/reconciliation jobs (rate limited, controlled retries and issuer statements).
2. Operator-approved refunds and chargeback case management, credit notes and fee-aware accounting.
3. Tax/jurisdiction/business registration fields and compliant invoice generation.
4. Payout eligibility, reserve windows, identity verification, approvals and audited transfers.
5. Merchant-authorized KHQR dynamic creation and atomic final entitlement confirmation.
6. End-to-end bank/merchant sandbox QA, accessibility/browser checks, cPanel installation and customer upgrade rehearsals.
