# External payment acceptance — operator verification gates

Edumia has a Stripe-hosted checkout and signed-webhook fulfillment foundation for courses, ebooks and bootcamps. It does **not** bundle a merchant account, real credentials, real sandbox evidence or live authorization. It has no payment integration for tutor booking or team training packages. Both new offerings are free-only until future independent verified provider integration.

## Admin verification

Open `/admin/commerce/gateway-readiness`. The page shows sanitized environment configuration and genuine Stripe **test API** connectivity outcomes, with request result, merchant country, and provider-supplied charges-enabled indicator. Secrets are never displayed or persisted. The test action uses the server-side `sk_test_` key against `/v1/account`; it does not charge a card, prove settlement eligibility, or verify webhook delivery.

The supported operator settings are:

```dotenv
STRIPE_ENABLED=false
STRIPE_MODE=test
STRIPE_SECRET_KEY=
STRIPE_WEBHOOK_SECRET=
STRIPE_LIVE_APPROVED=false
```

The existing Checkout gateway requires an enabled provider plus a matching mode-specific secret and webhook signing secret, and now refuses **live** mode unless `STRIPE_LIVE_APPROVED=true`. The default is false. Setting this flag is a manual operator action, not evidence that provider onboarding succeeded. This flag must **never** be set just to make a CI test pass.

## Unfinished acceptance gates

1. Verify legal merchant eligibility and country support using the provider's official onboarding instructions. Stripe does not currently list Cambodia on its standard supported-country list: https://stripe.com/global .
2. In a dedicated sandbox test merchant account, carry out a real hosted checkout and actual signed event delivery, including duplicate, expired, delayed, wrong amount and negative-case attempts. Mocked CI is useful but not real provider acceptance.
3. Validate refunds, disputes and reconciliation against test provider events, and ensure accurate invoice/tax, FX and settlement handling for every jurisdiction enabled.
4. Accept all customer installations independently, including real aaPanel/cPanel upgrades with existing data and physical-device testing.
5. Only after approvals and end-to-end evidence consider enabling live transactions. Keep tutor and team paid checkouts blocked until they have their own product-specific verified entitlement workflow.

## Upgrade safety

The new team, quiz and provider-check migrations are additive. Take backups first. Run `php artisan migrate --force` only against the intended installed database. Never `migrate:fresh` on a customer's installation. The downloadable ZIP is a QA development artifact, not an approved real-money/customer release.
