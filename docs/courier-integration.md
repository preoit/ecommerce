# Courier integration

## Deploy

Run `php artisan migrate --force`, then `php artisan optimize:clear`.
New bookings run directly during the HTTP request; no queue worker is required. Bulk booking sends one request per order sequentially and shows progress. Keep the page open until finished.
Allow at least 70 seconds for PHP/web-server request timeouts. Existing queued bookings from older versions can be drained with the old worker; do not resubmit them.
Run `php artisan schedule:run` every minute via cron. Status polling runs every ten minutes.
Keep APP_KEY stable and backed up: credentials and parcel payloads use Laravel encryption.

Configure providers at `/admin/couriers/settings`. Both are inactive by default.
Secrets are write-only in the UI. Blank fields retain the saved secret. Test Connection uses saved settings.
Only allowlisted official base URLs are accepted and redirects are disabled.

## Provider contracts and verification

Pathao authentication, stores, locations and creation use the official implementation:
https://github.com/pathao-eng/courier-woocommerce-plugin/blob/main/pathao-bridge.php
The implementation uses external/login with client_id/client_secret (not password-grant login).
Pathao status polling uses orders/{consignment}/info; confirm access for the merchant account before enabling automatic mapping.

Steadfast adapter uses Api-Key/Secret-Key and create_order, get_balance, status_by_cid.
Steadfast merchant documentation was not publicly accessible during development. This contract requires merchant documentation and live credential verification before production activation.
No live requests or parcel bookings were made during development. Tests use HTTP fakes.

Cancellation, rate quotation, and carrier-issued labels have not been verified for either account.
Unsupported methods return explanatory validation errors. Print produces an explicitly marked merchant A5 label, not a carrier-issued label.
Steadfast sandbox is disabled; Pathao uses the official sandbox host.
Polling is available; inbound webhook endpoints are deliberately not exposed without verified authentication contracts.

## Booking lifecycle

A unique active_order_id prevents duplicate queued or uncertain submissions across providers.
Direct requests do not retry create requests. Timeouts / missing consignment IDs retain the lock as needs_verification.
The scheduler marks interrupted submissions for verification; it never resubmits them.
Use the reference in the merchant panel to investigate. Linking a consignment requires API confirmation of its merchant reference. If unavailable, contact provider support; never clear the lock based only on a timeout.
Cancellation must be performed in the merchant panel and then confirmed through status sync.
Deactivation hides new booking selection; existing bookings remain refreshable. Environment changes are blocked while active bookings exist.

All status mappings default to manual. Unknown carrier statuses are logged without changing order state.
Terminal order states are not automatically overwritten. Sandbox status never changes website order status.
Payment is never marked paid from delivery status. Collected COD is manually reconciled against a settlement reference with an admin audit log.

## Extension

Implement CourierServiceInterface (or extend Provider), register service/URLs/credential fields in config/couriers.php and add a couriers row in a migration. The booking orchestration resolves registered services without provider switches. Provider-specific form requirements can be added alongside the provider.

## Verification

Run `php artisan test --filter=CourierIntegrationTest` using an isolated test database, then `npm run build`.
Before activation, test authentication, locations, one sandbox booking, status polling and merchant-reference reconciliation with merchant credentials; verify label acceptance and rates with the courier.
