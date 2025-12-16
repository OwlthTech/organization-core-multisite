# AJAX Endpoint Inventory

This document summarises every AJAX endpoint registered in `modules/bookings/ajax.php`, grouped by feature area. Each entry records security guards, expected payload, responses, and suggested improvements. Hyperlinks in the summary tables jump to detailed notes below.

## Summary Matrix

### Booking Lifecycle (Front-End)

| Action Hook(s) | Function | Access | Security | Notes |
| --- | --- | --- | --- | --- |
| `wp_ajax_create_booking`, `wp_ajax_nopriv_create_booking` | [handle_create_booking](#handle_create_booking) | Logged-in users (nopriv route still enforced) | `check_ajax_referer('bookings_nonce','nonce')`, login required | Confirms final booking or converts draft |
| `wp_ajax_get_bookings` | [handle_get_bookings](#handle_get_bookings) | Logged-in users | `check_ajax_referer('bookings_nonce','nonce')`, login required | Returns paginated booking list for current user |
| `wp_ajax_cancel_booking` | [handle_cancel_booking](#handle_cancel_booking) | Logged-in users | `check_ajax_referer('bookings_nonce','nonce')`, login required | Cancels pending bookings |
| `wp_ajax_get_package_data`, `wp_ajax_nopriv_get_package_data` | [handle_get_package_data](#handle_get_package_data) | Both (no login check) | `check_ajax_referer('bookings_nonce','nonce')` | Exposes package meta; accessible to visitors |
| `wp_ajax_save_booking_draft` | [handle_save_booking_draft](#handle_save_booking_draft) | Logged-in users | `check_ajax_referer('bookings_nonce','nonce')`, login required | Persists multi-step booking wizard state |
| `wp_ajax_get_booking_draft` | [handle_get_booking_draft](#handle_get_booking_draft) | Logged-in users | `check_ajax_referer('bookings_nonce','nonce')`, login required | Restores draft data for package |

### School Management (Front-End Profile)

| Action Hook(s) | Function | Access | Security | Notes |
| --- | --- | --- | --- | --- |
| `wp_ajax_save_user_school` | [handle_save_school](#handle_save_school) | Logged-in users | `check_ajax_referer('bookings_nonce','nonce')`, login required | Creates school record owned by user |
| `wp_ajax_load_user_schools` | [handle_get_schools_of_current_user](#handle_get_schools_of_current_user) | Logged-in users | `check_ajax_referer('bookings_nonce','nonce')`, login required | Returns current user's schools |
| `wp_ajax_delete_user_school` | [handle_delete_school](#handle_delete_school) | Logged-in users | `check_ajax_referer('bookings_nonce','nonce')`, login required | Soft deletes owned school |
| `wp_ajax_update_user_school` | [handle_update_school](#handle_update_school) | Logged-in users | `check_ajax_referer('bookings_nonce','nonce')`, login required | Updates owned school attributes |
| `wp_ajax_get_user_schools` | [handle_get_schools](#handle_get_schools) | Logged-in users | `check_ajax_referer('bookings_nonce','nonce')`, login required | Duplicate fetch endpoint for user schools |

### Admin Booking Maintenance (Dashboard)

| Action Hook(s) | Function | Access | Security | Notes |
| --- | --- | --- | --- | --- |
| `wp_ajax_save_booking_price` | [save_booking_price](#save_booking_price) | Admin (`manage_options`) | `check_ajax_referer('booking_price_nonce','nonce')`, capability check | Updates total amount column |
| `wp_ajax_send_booking_email` | [handle_send_booking_email](#handle_send_booking_email) | Admin (`manage_options`) | `check_ajax_referer('send_booking_email_nonce','nonce')`, capability check | Triggers transactional emails |
| `wp_ajax_update_booking_status` | [ajax_update_booking_status](#ajax_update_booking_status) | Admin (`manage_options`) | `check_ajax_referer('update_booking_status_nonce','nonce')`, capability check | Changes booking status |
| `wp_ajax_delete_booking` | [ajax_delete_booking](#ajax_delete_booking) | Admin (`manage_options`) | `check_ajax_referer('delete_booking_nonce','nonce')`, capability check | Deletes booking row |

### Orphan Check

All hooks declared in `__construct` have corresponding handler methods. Runtime discovery of unused endpoints (e.g. lacking JS callers) was **not** part of this audit, so potential front-end orphan usage remains unverified. No handler is currently unreferenced within the registration table itself.

---

## Endpoint Details

### handle_create_booking
- **Hooks:** `wp_ajax_create_booking`, `wp_ajax_nopriv_create_booking`
- **Security:** Enforces `check_ajax_referer('bookings_nonce','nonce')`; immediately rejects if user not logged in despite `nopriv` hook. Uses `get_current_user_id()` and validates ownership of drafts. Sanitises input via `sanitize_booking_input` before persisting.
- **Body Parameters:**
  - `nonce` (required)
  - `draft_id` (int, optional; >0 triggers draft confirmation path)
  - Booking form fields: `package_id`, `location_id`, `date_selection`, `school_id`, `parks[]`|`parks_selection`, `other_park_name`, `total_students`, `total_chaperones`, `transportation`, `include_meal_vouchers`|`meal_vouchers`, `meals_per_day`, `lodging_dates`, `special_notes`, `park_meal_options`, `ensembles`
  - Final-step overrides in raw `$_POST`
- **Error Responses:** JSON error with messages such as "Please log in", "Draft not found", validation failures (`WP_Error` codes), or generic catch-all.
- **Success Payload:** `booking_id`, `booking_reference`, `redirect_url`, `booking_data` snapshot, plus human-readable message.
- **Enhancements:** Consider removing `nopriv` hook (since login is mandatory), log booking creations, and rate-limit submissions to mitigate abuse.

### handle_get_bookings
- **Hook:** `wp_ajax_get_bookings`
- **Security:** `check_ajax_referer('bookings_nonce','nonce')`, login check.
- **Body Parameters:** `nonce`, `page` (default 1), `per_page` (default 10), optional `status` filter.
- **Errors:** Returns generic failure on nonce/auth issues or unexpected exceptions.
- **Success Payload:** `bookings` array (processed for display), pagination meta (`page`, `total_pages`, `total_items`).
- **Enhancements:** Add server-side bounds for `per_page` to avoid large queries; consider capability gating for staff vs customers.

### handle_cancel_booking
- **Hook:** `wp_ajax_cancel_booking`
- **Security:** `check_ajax_referer('bookings_nonce','nonce')`, login required. Verifies booking ownership and status.
- **Body Parameters:** `nonce`, `booking_id`.
- **Errors:** Not logged in, invalid ID, booking not found, ownership mismatch, status not `pending`, generic failure.
- **Success Payload:** `{ message: 'Booking cancelled successfully.' }`.
- **Enhancements:** Audit trail/logging; allow admins override with capability; return updated booking object for UI refresh.

### handle_get_package_data
- **Hooks:** `wp_ajax_get_package_data`, `wp_ajax_nopriv_get_package_data`
- **Security:** `check_ajax_referer('bookings_nonce','nonce')`; **no login requirement**.
- **Body Parameters:** `nonce`, `package_id`.
- **Errors:** Invalid/missing package ID, missing post or wrong post type, generic exception.
- **Success Payload:** Package attributes (title, excerpt, pricing, dates—see code for full structure).
- **Enhancements:** Consider relaxing to REST API or caching. Ensure nonce distributed to visitors is short-lived; evaluate if exposure of package meta to unauthenticated users is acceptable.

### handle_save_booking_draft
- **Hook:** `wp_ajax_save_booking_draft`
- **Security:** `check_ajax_referer('bookings_nonce','nonce')`, login required.
- **Body Parameters:** `nonce`, `package_id`, optional `draft_id`, `step_data` (array/object representing step form state).
- **Errors:** Missing package ID, permission issues, CRUD failure, generic exception.
- **Success Payload:** `draft_id`, `message`, `is_new` flag (per CRUD implementation).
- **Enhancements:** Validate `step_data` structure; add throttling per user/package combination.

### handle_get_booking_draft
- **Hook:** `wp_ajax_get_booking_draft`
- **Security:** `check_ajax_referer('bookings_nonce','nonce')`, login required.
- **Body Parameters:** `nonce`, `package_id`.
- **Errors:** Not logged in, invalid package ID, draft not found.
- **Success Payload:** Restored draft data (`data`), metadata such as `draft_id`, `updated_at`.
- **Enhancements:** Return HTTP 404 equivalent error codes; include draft expiry timestamp if applicable.

### handle_save_school
- **Hook:** `wp_ajax_save_user_school`
- **Security:** `check_ajax_referer('bookings_nonce','nonce')`, login required.
- **Body Parameters:** `nonce`, `school_data` (JSON string representing form fields).
- **Errors:** Invalid JSON, validation failure, CRUD failure, generic exception.
- **Success Payload:** `school_id`, sanitized `school` data, message.
- **Enhancements:** Introduce capability checks for staff-managed schools; enforce stricter schema validation before insert.

### handle_get_schools_of_current_user
- **Hook:** `wp_ajax_load_user_schools`
- **Security:** `check_ajax_referer('bookings_nonce','nonce')`, login required.
- **Body Parameters:** `nonce`.
- **Errors:** Auth failure, CRUD fetch issues.
- **Success Payload:** `schools` array, `total` count.
- **Enhancements:** Support pagination for large datasets; consider caching results per request.

### handle_delete_school
- **Hook:** `wp_ajax_delete_user_school`
- **Security:** `check_ajax_referer('bookings_nonce','nonce')`, login required. Confirms ownership of school record.
- **Body Parameters:** `nonce`, `school_id`.
- **Errors:** Missing ID, school not found or not owned, deletion failure, generic exception.
- **Success Payload:** `{ message: 'School deleted!' }`.
- **Enhancements:** Return remaining school list; provide soft-delete flag in response for UI reconciliation.

### handle_update_school
- **Hook:** `wp_ajax_update_user_school`
- **Security:** `check_ajax_referer('bookings_nonce','nonce')`, login required.
- **Body Parameters:** `nonce`, `school_id`, `school_data` (JSON string).
- **Errors:** Missing ID/data, ownership mismatch, validation failure, update failure.
- **Success Payload:** Updated `school` data, message.
- **Enhancements:** Include revision history; enforce rate limiting to prevent rapid edits.

### handle_get_schools
- **Hook:** `wp_ajax_get_user_schools`
- **Security:** `check_ajax_referer('bookings_nonce','nonce')`, login required.
- **Body Parameters:** `nonce`.
- **Errors:** Auth failure, CRUD fetch issues.
- **Success Payload:** `{ schools: [...] }`.
- **Enhancements:** This duplicates `handle_get_schools_of_current_user`; consider consolidating to a single endpoint or alias wrapper.

### save_booking_price
- **Hook:** `wp_ajax_save_booking_price`
- **Security:** `check_ajax_referer('booking_price_nonce','nonce')`, `current_user_can('manage_options')`.
- **Body Parameters:** `nonce`, `booking_id`, `price`.
- **Errors:** Permission denied, invalid booking ID, negative price, booking missing, database update failure.
- **Success Payload:** `message`, `display_price`, `raw_price`.
- **Enhancements:** Replace raw capability check with dedicated capability; add audit logging.

### handle_send_booking_email
- **Hook:** `wp_ajax_send_booking_email`
- **Security:** `check_ajax_referer('send_booking_email_nonce','nonce')`, `current_user_can('manage_options')`.
- **Body Parameters:** `nonce`, `booking_id`, `email_type` (`user` or `admin`).
- **Errors:** Permission denied, invalid parameters, booking/user not found.
- **Success Payload:** `'Email sent successfully'` message.
- **Enhancements:** Return delivery status metadata; allow custom templates selection.

### ajax_update_booking_status
- **Hook:** `wp_ajax_update_booking_status`
- **Security:** `check_ajax_referer('update_booking_status_nonce','nonce')`, `current_user_can('manage_options')`.
- **Body Parameters:** `nonce`, `booking_id`, `new_status`.
- **Errors:** Permission denied, missing parameters, database error.
- **Success Payload:** `{ message: 'Status updated successfully' }`.
- **Enhancements:** Validate `new_status` against allowed enum; include updated booking object in response.

### ajax_delete_booking
- **Hook:** `wp_ajax_delete_booking`
- **Security:** `check_ajax_referer('delete_booking_nonce','nonce')`, `current_user_can('manage_options')`.
- **Body Parameters:** `nonce`, `booking_id`.
- **Errors:** Permission denied, invalid ID, deletion failure.
- **Success Payload:** `{ message: 'Booking deleted successfully' }`.
- **Enhancements:** Provide soft-delete option; emit action hook after deletion for audit trails.

---

## Additional Recommendations
- Standardise nonce names (currently `bookings_nonce` vs `booking_price_nonce`, etc.) and document expected localisation variables.
- Consider migrating public-facing endpoints to the REST API for better cache control and documentation support.
- Implement unified error codes to assist front-end handling instead of free-form strings.
- Add central logging for critical operations (create/cancel/delete) to aid support and compliance.
