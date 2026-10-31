# Edumia verified payments and learning entitlements

## Supported implementation

Edumia provides an **operator-configured Stripe-hosted Checkout integration** for one-item orders: paid courses, PDF ebooks and bootcamp cohorts. This is original Laravel application code using Laravel's HTTP client and Stripe's HTTPS REST API; card credentials are never collected on Edumia pages.

**Default:** disabled. A fresh installation has no gateway keys and cannot collect money. This module is source-complete for automated verification, but it has **not** been tested with a real merchant account or the installed production domain. Do not label it live-ready.

## Operator configuration (secrets stay in the environment)

1. Set a valid, public HTTPS `APP_URL`, deploy using a backed-up **QA** database and run the incremental migrations. Never run `migrate:fresh` on an installed customer database.
2. Verify that the operator is eligible for a Stripe merchant account and can receive the selected currency. Edumia currently supports **USD** purchases only. Other currencies, taxes, coupons and mixed carts require their own tested logic; never silently convert a price.
3. Add the endpoint `https://YOUR-EDUMIA-DOMAIN/webhooks/stripe` to the Stripe account's webhooks, subscribing to `checkout.session.completed`, `checkout.session.async_payment_succeeded` and `checkout.session.expired`. Capture its `whsec_...` signing secret.
4. Set the environment variables below, and run Laravel's standard `php artisan config:clear` / `php artisan config:cache` as required for the hosting environment. Never place these values in Git, the frontend or debug logs:

```dotenv
STRIPE_ENABLED=false
STRIPE_MODE=test
STRIPE_SECRET_KEY=sk_test_your_private_key
STRIPE_WEBHOOK_SECRET=whsec_your_signing_secret
```

5. In isolated **test mode only**, change `STRIPE_ENABLED=true` after the credentials, HTTPS URL and webhook subscription are correct. Test the paid-course, ebook and capacity-one bootcamp flows. Confirm webhook redelivery, invalid signatures, delayed events, expired sessions and mismatched amounts are rejected or handled correctly.
6. Complete your actual merchant's settlement, accounting, dispute, refund, and operational review before attempting live transactions. Switch `STRIPE_MODE=live` and supply a live secret and corresponding live webhook signing secret only after that review. There is no application UI to expose or edit the private gateway key.

## Business-state guarantees

- Preparing an order does **not** initiate payment. Orders store item title, currency, amount and quantity as an immutable snapshot for checkout.
- The learner must explicitly start hosted Checkout. The server creates the payment session, records its trusted ID, and redirects to the validated `https://checkout.stripe.com` host.
- The success URL, cancellation URL, query parameters, learner session and user-submitted values can **never** mark an order paid.
- Webhooks require a valid SHA-256 timestamped signature over the raw body, a five-minute tolerance, and matching server-retrieved Checkout Session details: session ID, order reference, metadata, USD minor units, live/test mode, `complete` status and `paid` status.
- A single DB transaction writes the payment record, paid order, event identity, audit event and the proper learning access. The event ledger and payment references are unique to prevent double fulfillment.
- Course entitlement creates an active enrollment; ebook purchase creates private PDF-library access; bootcamp purchase creates a completed registration only if capacity remains.
- Bootcamp Checkout initiation reserves a seat against concurrent pending sessions. If a late paid notification arrives after the last seat is taken, fulfillment fails closed and requires an operator to reconcile the payment and possibly refund it. Never delete an unmatched paid transaction from the provider.
- A verified expired Checkout event releases its local session state but never grants access. Expired or cancelled drafts cannot be paid through Edumia. A paid transaction cannot be cancelled by a customer through the pending-order action.

For the latest accounting, refunds and Bakong implementation details, see [Commercial accounting and Bakong](commerce-accounting.md).

## Limitations and next production requirements

- No production merchant credentials, live transactions, or Stripe sandbox acceptance evidence are bundled. All behavior is covered by isolated mocked gateway responses and web feature tests.
- USD card Checkout only; no native KHQR/Bakong payment provider, currencies beyond USD, manual/offline payment verification, recurring subscriptions or multi-product cart.
- Verified Stripe refund/dispute events, invoice snapshots and held instructor earnings are now implemented in source. There is **no refund initiation, tax, coupons, credit notes, external settlement reconciliation or instructor payout execution**. **Do not enable real-money use until refund/dispute handling and accounting/reconciliation are developed and accepted.**
- Stripe Checkout is HTTPS provider-hosted. Customer-specific Stripe account activation or country eligibility must be verified separately.
- Source checkout pages are not a replacement for a real-browser cross-device checkout and return-url verification.

## Test and monitoring commands

```bash
php artisan migrate --force
php artisan route:list --path=checkout
php artisan route:list --path=webhooks
php artisan test --filter=VerifiedPaymentWorkflowTest
php artisan test --fail-on-warning
```

All destructive testing and sandbox payment tests belong on an isolated QA copy, never the installed production baseline. Monitor Stripe's event delivery dashboard and Edumia's `payment_webhook_events`, `payment_histories`, `orders` and `audit_events` tables for reconciliation. Avoid storing raw card details or real gateway secrets in logs.

Official reference: https://docs.stripe.com/payments/checkout and https://docs.stripe.com/webhooks/signature .
