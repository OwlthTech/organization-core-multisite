# Notifications Module - Complete Requirements

> **Total Modules:** 7  
> **Total Notifications Required:** 50+  
> **Email Templates Needed:** 50+  
> **Scheduled Notifications:** 15+

---

## Table of Contents

1. [Overview](#overview)
2. [Authentication Module Notifications](#authentication-module-notifications)
3. [Bookings Module Notifications](#bookings-module-notifications)
4. [Schools Module Notifications](#schools-module-notifications)
5. [Hotels Module Notifications](#hotels-module-notifications)
6. [Rooming List Module Notifications](#rooming-list-module-notifications)
7. [Quotes Module Notifications](#quotes-module-notifications)
8. [Shareables Module Notifications](#shareables-module-notifications)
9. [Shared Email Templates](#shared-email-templates)
10. [Notification Triggers Summary](#notification-triggers-summary)

---

## Overview

This document consolidates **ALL notification requirements** from all 7 modules, organized by priority and type.

### Priority Levels

- **Critical:** Must implement first (core functionality)
- **Important:** Should implement soon (user experience)
- **Nice to Have:** Can implement later (enhancements)

### Notification Types

- **Instant:** Sent immediately after trigger
- **Scheduled:** Sent at specific time (reminders, digests)
- **Conditional:** Sent based on conditions (status changes)

---

## Authentication Module Notifications

**Current Status:** ✅ 1 template implemented (password reset)

### Critical (2 notifications)

#### 1. Password Reset → User
- **Trigger:** User requests password reset
- **Recipients:** User
- **Template:** `password-reset.php` ✅ **IMPLEMENTED**
- **Type:** Instant
- **Variables:**
  - `user_name`, `reset_url`, `expiration_time`, `site_name`
- **Hook:** `oc_password_reset_requested`
- **Status:** ✅ **WORKING**

#### 2. Account Created → User
- **Trigger:** New user registration
- **Recipients:** User
- **Template:** `account-created.php` ❌ **NEEDED**
- **Type:** Instant
- **Variables:**
  - `user_name`, `user_email`, `login_url`, `site_name`
- **Hook:** `oc_user_registered`
- **Status:** ❌ **NOT IMPLEMENTED**

---

### Important (3 notifications)

#### 3. Login Success → User
- **Trigger:** Successful login
- **Recipients:** User
- **Template:** `login-success.php` ❌ **NEEDED**
- **Type:** Instant
- **Variables:**
  - `user_name`, `login_time`, `ip_address`, `device`
- **Hook:** `wp_login`
- **Status:** ❌ **NOT IMPLEMENTED**

#### 4. Profile Updated → User
- **Trigger:** User updates profile
- **Recipients:** User
- **Template:** `profile-updated.php` ❌ **NEEDED**
- **Type:** Instant
- **Variables:**
  - `user_name`, `updated_fields`, `update_time`
- **Hook:** `oc_profile_updated`
- **Status:** ❌ **NOT IMPLEMENTED**

#### 5. Password Changed → User
- **Trigger:** User changes password
- **Recipients:** User
- **Template:** `password-changed.php` ❌ **NEEDED**
- **Type:** Instant
- **Variables:**
  - `user_name`, `change_time`, `ip_address`
- **Hook:** `password_reset`
- **Status:** ❌ **NOT IMPLEMENTED**

---

### Nice to Have (2 notifications)

#### 6. Suspicious Login → User
- **Trigger:** Login from new device/location
- **Recipients:** User
- **Template:** `suspicious-login.php` ❌ **NEEDED**
- **Type:** Instant

#### 7. Account Inactive → User
- **Trigger:** No login for 90 days
- **Recipients:** User
- **Template:** `account-inactive.php` ❌ **NEEDED**
- **Type:** Scheduled (daily check)

---

## Bookings Module Notifications

**Current Status:** ✅ 2 templates implemented (booking confirmation)

### Critical (3 notifications)

#### 1. Booking Created → User + Admin
- **Trigger:** New booking submitted
- **Recipients:** User + Admin
- **Templates:**
  - `booking-confirmation-user.php` ✅ **IMPLEMENTED**
  - `booking-confirmation-admin.php` ✅ **IMPLEMENTED**
- **Type:** Instant
- **Variables:**
  - `booking_id`, `booking_ref`, `user_name`, `school_name`, `festival_date`, `total_students`, `total_chaperones`, `hotel_name`, `check_in`, `check_out`
- **Hook:** `org_core_booking_created` ✅ **WORKING**
- **Status:** ✅ **WORKING**

#### 2. Booking Status Changed → User
- **Trigger:** Admin changes booking status
- **Recipients:** User
- **Template:** `booking-status-changed.php` ❌ **NEEDED**
- **Type:** Instant
- **Variables:**
  - `booking_ref`, `old_status`, `new_status`, `status_message`
- **Hook:** `oc_booking_status_changed`
- **Status:** ❌ **NOT IMPLEMENTED**

#### 3. Payment Received → User
- **Trigger:** Payment processed
- **Recipients:** User
- **Template:** `payment-received.php` ❌ **NEEDED**
- **Type:** Instant
- **Variables:**
  - `booking_ref`, `amount`, `payment_method`, `receipt_url`
- **Hook:** `oc_payment_received`
- **Status:** ❌ **NOT IMPLEMENTED**

---

### Important (3 notifications)

#### 4. Booking Reminder (7 days before) → User
- **Trigger:** 7 days before festival date
- **Recipients:** User
- **Template:** `booking-reminder-7days.php` ❌ **NEEDED**
- **Type:** Scheduled (daily cron check)
- **Variables:**
  - `booking_ref`, `festival_date`, `days_until`, `checklist_items`
- **Hook:** Action Scheduler
- **Status:** ❌ **NOT IMPLEMENTED**

#### 5. Rooming List Due Date Reminder → User
- **Trigger:** 3 days before rooming list due date
- **Recipients:** User
- **Template:** `rooming-list-due-reminder.php` ❌ **NEEDED**
- **Type:** Scheduled (daily cron check)
- **Variables:**
  - `booking_ref`, `due_date`, `days_until`, `rooming_list_url`
- **Hook:** Action Scheduler
- **Status:** ❌ **NOT IMPLEMENTED**

#### 6. Booking Updated → User + Admin
- **Trigger:** Booking details modified
- **Recipients:** User + Admin
- **Template:** `booking-updated.php` ❌ **NEEDED**
- **Type:** Instant
- **Variables:**
  - `booking_ref`, `updated_fields`, `update_time`
- **Hook:** `oc_booking_updated`
- **Status:** ❌ **NOT IMPLEMENTED**

---

### Nice to Have (3 notifications)

#### 7. Draft Expiring Soon → User
- **Trigger:** Draft booking older than 7 days
- **Recipients:** User
- **Template:** `draft-expiring.php` ❌ **NEEDED**
- **Type:** Scheduled (daily check)

#### 8. Booking Cancelled → User + Admin
- **Trigger:** Booking cancelled
- **Recipients:** User + Admin
- **Template:** `booking-cancelled.php` ❌ **NEEDED**
- **Type:** Instant

#### 9. Admin Notes Added → Admin
- **Trigger:** Admin adds notes to booking
- **Recipients:** Admin
- **Template:** `admin-notes-added.php` ❌ **NEEDED**
- **Type:** Instant

---

## Schools Module Notifications

**Current Status:** ❌ NO templates implemented

### Critical (3 notifications)

#### 1. School Created → User
- **Trigger:** User creates school profile
- **Recipients:** User
- **Template:** `school-created.php` ❌ **NEEDED**
- **Type:** Instant
- **Variables:**
  - `school_name`, `director_name`, `school_address`, `edit_url`
- **Hook:** `mus_school_created`
- **Status:** ❌ **NOT IMPLEMENTED**

#### 2. School Verification Request → Admin
- **Trigger:** User submits school for verification
- **Recipients:** Admin
- **Template:** `school-verification-request.php` ❌ **NEEDED**
- **Type:** Instant
- **Variables:**
  - `school_name`, `user_name`, `school_details`, `verify_url`
- **Hook:** `mus_school_verification_requested`
- **Status:** ❌ **NOT IMPLEMENTED**

#### 3. School Verified → User
- **Trigger:** Admin verifies school
- **Recipients:** User
- **Template:** `school-verified.php` ❌ **NEEDED**
- **Type:** Instant
- **Variables:**
  - `school_name`, `verification_date`, `verified_by`
- **Hook:** `mus_school_verified`
- **Status:** ❌ **NOT IMPLEMENTED**

---

### Important (3 notifications)

#### 4. School Updated → User
- **Trigger:** User updates school profile
- **Recipients:** User
- **Template:** `school-updated.php` ❌ **NEEDED**
- **Type:** Instant

#### 5. School Deleted → User
- **Trigger:** User deletes school
- **Recipients:** User
- **Template:** `school-deleted.php` ❌ **NEEDED**
- **Type:** Instant

#### 6. School Used in Booking → User
- **Trigger:** School selected for booking
- **Recipients:** User
- **Template:** `school-used-booking.php` ❌ **NEEDED**
- **Type:** Instant

---

### Nice to Have (3 notifications)

#### 7. School Expiring → User
- **Trigger:** School data older than 1 year
- **Recipients:** User
- **Template:** `school-expiring.php` ❌ **NEEDED**
- **Type:** Scheduled (monthly check)

#### 8. Multiple Schools Reminder → User
- **Trigger:** User has 5+ schools
- **Recipients:** User
- **Template:** `multiple-schools-reminder.php` ❌ **NEEDED**
- **Type:** Scheduled

#### 9. School Shared → User
- **Trigger:** School shared with another user
- **Recipients:** User
- **Template:** `school-shared.php` ❌ **NEEDED**
- **Type:** Instant

---

## Hotels Module Notifications

**Current Status:** ❌ NO templates implemented

### Critical (3 notifications - All Admin)

#### 1. Hotel Created → Admin
- **Trigger:** Admin creates hotel
- **Recipients:** Admin
- **Template:** `hotel-created-admin.php` ❌ **NEEDED**
- **Type:** Instant
- **Variables:**
  - `hotel_name`, `hotel_address`, `total_rooms`, `capacity`
- **Hook:** `oc_hotel_created`
- **Status:** ❌ **NOT IMPLEMENTED**

#### 2. Hotel Updated → Admin
- **Trigger:** Admin updates hotel
- **Recipients:** Admin
- **Template:** `hotel-updated-admin.php` ❌ **NEEDED**
- **Type:** Instant

#### 3. Hotel Deleted → Admin
- **Trigger:** Admin deletes hotel
- **Recipients:** Admin
- **Template:** `hotel-deleted-admin.php` ❌ **NEEDED**
- **Type:** Instant

---

### Important (3 notifications)

#### 4. Hotel Assigned to Booking → Admin + User
- **Trigger:** Hotel assigned to booking
- **Recipients:** Admin + User
- **Template:** `hotel-assigned-booking.php` ❌ **NEEDED**
- **Type:** Instant

#### 5. Hotel Capacity Warning (80%) → Admin
- **Trigger:** Hotel reaches 80% capacity
- **Recipients:** Admin
- **Template:** `hotel-capacity-warning.php` ❌ **NEEDED**
- **Type:** Conditional

#### 6. Hotel Full (100%) → Admin
- **Trigger:** Hotel reaches full capacity
- **Recipients:** Admin
- **Template:** `hotel-full.php` ❌ **NEEDED**
- **Type:** Conditional

---

### Nice to Have (3 notifications)

#### 7. Bulk Hotels Imported → Admin
- **Trigger:** CSV import completed
- **Recipients:** Admin
- **Template:** `hotels-imported.php` ❌ **NEEDED**
- **Type:** Instant

#### 8. Hotel Availability Report → Admin
- **Trigger:** Weekly report
- **Recipients:** Admin
- **Template:** `hotel-availability-report.php` ❌ **NEEDED**
- **Type:** Scheduled (weekly)

#### 9. Hotel Maintenance Reminder → Admin
- **Trigger:** Scheduled maintenance due
- **Recipients:** Admin
- **Template:** `hotel-maintenance-reminder.php` ❌ **NEEDED**
- **Type:** Scheduled

---

## Rooming List Module Notifications

**Current Status:** ❌ NO templates implemented

### Critical (3 notifications)

#### 1. Rooming List Created → User
- **Trigger:** First time rooming list saved
- **Recipients:** User
- **Template:** `rooming-list-created.php` ❌ **NEEDED**
- **Type:** Instant
- **Variables:**
  - `booking_ref`, `total_rooms`, `total_occupants`, `due_date`, `edit_url`
- **Hook:** `oc_rooming_list_created`
- **Status:** ❌ **NOT IMPLEMENTED**

#### 2. Rooming List Locked → User + Admin
- **Trigger:** User or admin locks list
- **Recipients:** User + Admin
- **Template:** `rooming-list-locked.php` ❌ **NEEDED**
- **Type:** Instant

#### 3. Rooming List Auto-Locked → User + Admin
- **Trigger:** Cron auto-locks after due date
- **Recipients:** User + Admin
- **Template:** `rooming-list-auto-locked.php` ❌ **NEEDED**
- **Type:** Scheduled (daily cron)

---

### Important (3 notifications)

#### 4. Rooming List Updated → User
- **Trigger:** List modified
- **Recipients:** User
- **Template:** `rooming-list-updated.php` ❌ **NEEDED**
- **Type:** Instant

#### 5. Rooming List Due Date Reminder (3 days) → User
- **Trigger:** 3 days before due date
- **Recipients:** User
- **Template:** `rooming-list-due-reminder.php` ❌ **NEEDED**
- **Type:** Scheduled (daily check)

#### 6. Rooming List Incomplete Warning (1 day) → User
- **Trigger:** 1 day before due date, not complete
- **Recipients:** User
- **Template:** `rooming-list-incomplete.php` ❌ **NEEDED**
- **Type:** Scheduled (daily check)

---

### Nice to Have (4 notifications)

#### 7. CSV Imported → User + Admin
- **Trigger:** CSV import successful
- **Recipients:** User + Admin
- **Template:** `rooming-list-imported.php` ❌ **NEEDED**
- **Type:** Instant

#### 8. CSV Exported → User
- **Trigger:** CSV export
- **Recipients:** User
- **Template:** `rooming-list-exported.php` ❌ **NEEDED**
- **Type:** Instant

#### 9. Room Assignment Changed → User
- **Trigger:** Individual assignment modified
- **Recipients:** User
- **Template:** `room-assignment-changed.php` ❌ **NEEDED**
- **Type:** Instant

#### 10. Rooming List Unlocked → User + Admin
- **Trigger:** Admin unlocks list
- **Recipients:** User + Admin
- **Template:** `rooming-list-unlocked.php` ❌ **NEEDED**
- **Type:** Instant

---

## Quotes Module Notifications

**Current Status:** ❌ NO templates implemented

### Critical (3 notifications)

#### 1. Quote Submitted → User + Admin
- **Trigger:** Quote request submitted
- **Recipients:** User + Admin
- **Templates:**
  - `quote-submitted-user.php` ❌ **NEEDED**
  - `quote-submitted-admin.php` ❌ **NEEDED**
- **Type:** Instant
- **Variables:**
  - `quote_id`, `educator_name`, `school_name`, `destination_name`, `quote_details`
- **Hook:** `organization_core_quote_submitted`
- **Status:** ❌ **NOT IMPLEMENTED**

#### 2. Quote Status Changed → User
- **Trigger:** Admin changes quote status
- **Recipients:** User
- **Template:** `quote-status-changed.php` ❌ **NEEDED**
- **Type:** Instant

#### 3. Quote Responded → User
- **Trigger:** Admin responds to quote
- **Recipients:** User
- **Template:** `quote-responded.php` ❌ **NEEDED**
- **Type:** Instant

---

### Important (3 notifications)

#### 4. Quote Follow-up (48 hours) → User
- **Trigger:** 48 hours after submission, no response
- **Recipients:** User
- **Template:** `quote-followup.php` ❌ **NEEDED**
- **Type:** Scheduled

#### 5. Quote Reminder → Admin
- **Trigger:** 24 hours after submission, still pending
- **Recipients:** Admin
- **Template:** `quote-reminder-admin.php` ❌ **NEEDED**
- **Type:** Scheduled

#### 6. Quote Expired → User + Admin
- **Trigger:** 30 days after submission, no conversion
- **Recipients:** User + Admin
- **Template:** `quote-expired.php` ❌ **NEEDED**
- **Type:** Scheduled

---

### Nice to Have (3 notifications)

#### 7. Quote Converted to Booking → User + Admin
- **Trigger:** Quote converted
- **Recipients:** User + Admin
- **Template:** `quote-converted.php` ❌ **NEEDED**
- **Type:** Instant

#### 8. Quote Declined → User
- **Trigger:** Admin declines quote
- **Recipients:** User
- **Template:** `quote-declined.php` ❌ **NEEDED**
- **Type:** Instant

#### 9. Quote Updated → User + Admin
- **Trigger:** Quote details modified
- **Recipients:** User + Admin
- **Template:** `quote-updated.php` ❌ **NEEDED**
- **Type:** Instant

---

## Shareables Module Notifications

**Current Status:** ❌ NO templates implemented

### Critical (3 notifications)

#### 1. Shareable Published → Admin
- **Trigger:** Shareable published
- **Recipients:** Admin
- **Template:** `shareable-published.php` ❌ **NEEDED**
- **Type:** Instant
- **Variables:**
  - `shareable_title`, `uuid`, `public_url`, `publish_time`
- **Hook:** `oc_shareable_published`
- **Status:** ❌ **NOT IMPLEMENTED**

#### 2. Shareable First View → Admin
- **Trigger:** First time shareable viewed
- **Recipients:** Admin
- **Template:** `shareable-first-view.php` ❌ **NEEDED**
- **Type:** Instant

#### 3. Shareable Access Digest → Admin
- **Trigger:** Daily digest of views
- **Recipients:** Admin
- **Template:** `shareable-access-digest.php` ❌ **NEEDED**
- **Type:** Scheduled (daily)

---

### Important (3 notifications)

#### 4. Shareable Created (Draft) → Admin
- **Trigger:** Draft shareable created
- **Recipients:** Admin
- **Template:** `shareable-created.php` ❌ **NEEDED**
- **Type:** Instant

#### 5. Shareable Updated → Admin
- **Trigger:** Shareable modified
- **Recipients:** Admin
- **Template:** `shareable-updated.php` ❌ **NEEDED**
- **Type:** Instant

#### 6. Shareable Deleted → Admin
- **Trigger:** Shareable deleted
- **Recipients:** Admin
- **Template:** `shareable-deleted.php` ❌ **NEEDED**
- **Type:** Instant

---

### Nice to Have (3 notifications)

#### 7. Share Link Copied → Admin
- **Trigger:** Share link copied
- **Recipients:** Admin
- **Template:** `share-link-copied.php` ❌ **NEEDED**
- **Type:** Instant

#### 8. Shareable Expired → Admin
- **Trigger:** Expiration date reached
- **Recipients:** Admin
- **Template:** `shareable-expired.php` ❌ **NEEDED**
- **Type:** Scheduled

#### 9. Shareable View Milestone → Admin
- **Trigger:** 100, 500, 1000 views
- **Recipients:** Admin
- **Template:** `shareable-milestone.php` ❌ **NEEDED**
- **Type:** Conditional

---

## Shared Email Templates

Some notifications can use **shared templates** with different variables:

### 1. Status Changed Template
**Used By:**
- Booking status changed
- Quote status changed
- School verification status

**Template:** `status-changed.php`

**Variables:**
- `item_type`, `item_ref`, `old_status`, `new_status`, `status_message`

---

### 2. Item Created Template
**Used By:**
- School created
- Hotel created
- Shareable created

**Template:** `item-created.php`

**Variables:**
- `item_type`, `item_name`, `item_details`, `edit_url`

---

### 3. Reminder Template
**Used By:**
- Booking reminder
- Rooming list due reminder
- Quote follow-up

**Template:** `reminder.php`

**Variables:**
- `reminder_type`, `item_ref`, `due_date`, `action_url`

---

### 4. Digest Template
**Used By:**
- Shareable access digest
- Hotel availability report
- Weekly summary

**Template:** `digest.php`

**Variables:**
- `digest_type`, `period`, `summary_data`, `details_url`

---

## Notification Triggers Summary

### Instant Triggers (35 notifications)

**Authentication (5):**
- Password reset, Account created, Login success, Profile updated, Password changed

**Bookings (3):**
- Booking created, Status changed, Payment received

**Schools (6):**
- School created, Verification request, Verified, Updated, Deleted, Used in booking

**Hotels (6):**
- Hotel created, Updated, Deleted, Assigned, Capacity warning, Full

**Rooming List (6):**
- Created, Updated, Locked, Unlocked, CSV imported, CSV exported

**Quotes (6):**
- Quote submitted, Status changed, Responded, Converted, Declined, Updated

**Shareables (3):**
- Published, First view, Created

---

### Scheduled Triggers (15 notifications)

**Daily Checks (8):**
- Booking reminder (7 days before)
- Rooming list due reminder (3 days before)
- Rooming list incomplete warning (1 day before)
- Rooming list auto-lock (on due date)
- Quote follow-up (48 hours)
- Quote reminder admin (24 hours)
- Draft expiring (7 days old)
- Shareable access digest

**Weekly Checks (1):**
- Hotel availability report

**Monthly Checks (2):**
- School expiring (1 year old)
- Account inactive (90 days)

**Conditional (4):**
- Quote expired (30 days)
- Shareable expired (if expiration set)
- Hotel capacity warning (80%)
- Shareable view milestone

---

## Implementation Priority

### Phase 1: Critical (20 notifications)
- Authentication: Password reset ✅, Account created
- Bookings: Created ✅, Status changed, Payment received
- Schools: Created, Verification request, Verified
- Hotels: Created, Updated, Deleted
- Rooming List: Created, Locked, Auto-locked
- Quotes: Submitted, Status changed, Responded
- Shareables: Published, First view, Access digest

### Phase 2: Important (18 notifications)
- All "Important" notifications from all modules
- Focus on reminders and user experience

### Phase 3: Nice to Have (12+ notifications)
- All "Nice to Have" notifications
- Advanced features and analytics

---

## Total Requirements Summary

| Module | Critical | Important | Nice to Have | Total |
|--------|----------|-----------|--------------|-------|
| **Authentication** | 2 | 3 | 2 | 7 |
| **Bookings** | 3 | 3 | 3 | 9 |
| **Schools** | 3 | 3 | 3 | 9 |
| **Hotels** | 3 | 3 | 3 | 9 |
| **Rooming List** | 3 | 3 | 4 | 10 |
| **Quotes** | 3 | 3 | 3 | 9 |
| **Shareables** | 3 | 3 | 3 | 9 |
| **TOTAL** | **20** | **21** | **21** | **62** |

---

**Last Updated:** 2024-12-11  
**Total Notifications:** 62  
**Templates Needed:** 50+ (some shared)  
**Scheduled Notifications:** 15  
**Instant Notifications:** 35  
**Conditional Notifications:** 4
