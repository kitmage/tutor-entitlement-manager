# Kitmage Training Entitlements

Kitmage Training Entitlements converts successfully paid WooCommerce line items into finite, shareable enrollment batches for entitlement-only courses in the Kitmage Tutor LMS fork.

## Requirements and installation

Requires WordPress, WooCommerce, PHP 7.4+, OpenSSL, and the [Kitmage Tutor LMS fork](https://github.com/kitmage/tutor-alt) exposing `Tutor\Models\Course::PRICE_TYPE_ENTITLEMENT`. WooCommerce Subscriptions is optional. Copy the plugin directory into `wp-content/plugins`, activate it, then save **Settings → Permalinks** if the host prevents activation-time rewrite flushing.

Activation creates the tables and the `/training/redeem/{TOKEN}` rewrite. Deactivation and uninstall preserve business data. HPOS compatibility is declared; all order access uses WooCommerce CRUD objects.

## Product configuration

In a product's **General** data, enable **Training Entitlements**, select a published course using **Pricing Model → Entitlement Only**, and set positive seats-per-unit and redemption-window values (30 days by default). Variations can override course, seats, and window; blank values inherit the parent. The recorded order-item quantity multiplies seats. The resolved course, per-unit count, total, and window are copied to hidden order-item metadata and the immutable batch snapshot.

## Provisioning and subscriptions

Paid orders are handled by `woocommerce_payment_complete`, `woocommerce_order_status_processing`, and `woocommerce_order_status_completed`. All converge on one idempotent routine, while the unique `order_item_id` database key is the final race-condition guard. `woocommerce_subscription_renewal_payment_complete` provisions the paid renewal order. Each successful renewal creates a separate batch; capacity never rolls over. Failed renewals and subscription cancellation create or invalidate nothing. The renewal order's `_subscription_renewal` metadata supplies the subscription association.

Full refunds are handled through `woocommerce_order_fully_refunded` and mark all funded batches refunded without deleting redemptions or Tutor enrollments. Partial refunds intentionally make no automatic seat adjustment.

## Redemption and accounting

Tokens contain 256 random bits. Only an HMAC-SHA-256 lookup hash and an AES-256-GCM encrypted copy (needed to show the purchaser the shared link) are stored. Regeneration atomically replaces both and increments the token version.

Anonymous visitors see course and expiration information, authenticate through WooCommerce My Account, and return to the invitation. A logged-in POST is nonce-protected. Server-side checks cover state, expiration, capacity, course existence/mode, duplicate redemption, and existing enrollment.

Reservation uses a short InnoDB transaction and `SELECT … FOR UPDATE`; it increments `entitlements_reserved` and inserts a pending redemption. Outside that transaction the dedicated Tutor service calls `Tutor\Models\EnrollmentModel::do_enroll($course_id, 0, $user_id)` and verifies with `Tutor\Models\EnrollmentModel::is_enrolled($course_id, $user_id)`. A verified result converts reserved to used and snapshots identity; failure releases the reservation. The hourly `kte_reconcile_reservations` event checks Tutor before finalizing or releasing pending reservations older than 15 minutes.

## Interfaces

Purchasers use **My Account → Training Enrollments**. Queries are restricted to the current user. Active links, counts, dates, state, and historical names/dates are displayed; trainee emails are never shown there. Administrators use **WooCommerce → Training Entitlements** for paginated batches, details, email-visible redemption history, audit history, revocation/reactivation, token regeneration, expiration changes, and safe total changes. State changes require `manage_woocommerce` and a nonce. Totals cannot fall below used plus reserved seats.

## Database

* `{prefix}kte_batches`: source/order/subscription/product/course snapshot, encrypted token and indexed hash, total/used/reserved counters, expiration and state. Unique `order_item_id` and `token_hash` keys.
* `{prefix}kte_redemptions`: pending/completed/failed workflow, user/course, timestamps, failure details, immutable name/email snapshots. Nullable unique `completed_key` enforces one completed user/batch pair.
* `{prefix}kte_audit_log`: append-only interface history for creation and administrative/refund changes.

Tables are InnoDB and deliberately have no cascading foreign keys so deleted products, courses, users, or orders cannot erase history. Schema option `kte_db_version` drives restart-safe `dbDelta()` upgrades.

## Public actions and filter

* `kitmage_training_entitlements/batch_created` — after insertion; batch ID, order ID, order-item ID (integers).
* `kitmage_training_entitlements/redemption_completed` — after verified Tutor enrollment and accounting finalization; batch ID, user ID, course ID, redemption ID.
* `kitmage_training_entitlements/redemption_failed` — after Tutor failure and reservation release; the same four IDs plus failure code.
* `kitmage_training_entitlements/batch_revoked` — when an administrator revokes; batch ID and administrator user ID.
* `kitmage_training_entitlements/course_eligible` filters the compatibility result, course ID, and detected price type.
* `kitmage_training_entitlements/redemption_url` filters the generated URL and raw token. Consumers must never persist or log the token.

## Operational limitations

Partial refunds are not mapped to quantities. Completed trainees are never automatically unenrolled. The plugin depends on the fork retaining the documented course-price metadata and enrollment model API. Product course selection is a standard select (not remote AJAX) and is best suited to moderate course catalogs. MySQL transactional guarantees require InnoDB. Operational errors use the WooCommerce logger when available; raw tokens are never logged.
