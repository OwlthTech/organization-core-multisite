# Production Deployment Guide - Rooming List Auto-Lock

## 📋 Pre-Deployment Checklist

Before going live, ensure you have:

- [ ] Live domain is finalized and configured
- [ ] SSL certificate is installed and active
- [ ] WordPress is updated to latest version
- [ ] Plugin is tested on staging environment
- [ ] Database backups are configured (daily recommended)
- [ ] UptimeRobot account created (free tier is sufficient)

---

## 🚀 Step-by-Step Deployment

### Step 1: Configure UptimeRobot

**1.1 Create Account**
- Go to [https://uptimerobot.com](https://uptimerobot.com)
- Sign up for free account
- Verify your email address

**1.2 Add Monitor**
- Click **"+ Add New Monitor"**
- **Monitor Type**: `HTTP(s)`
- **Friendly Name**: `WordPress Cron - [Your Site Name]`
- **URL**: `https://your-live-domain.com/wp-cron.php`
- **Monitoring Interval**: `5 minutes` (free tier)
- Click **"Create Monitor"**

**1.3 Verify Monitor**
- Wait 5-10 minutes
- Check monitor status shows **"Up"**
- Verify response time is reasonable (< 5 seconds)

**1.4 Setup Alerts (Optional)**
- Add email notification for downtime
- Configure SMS alerts (paid plan only)

---

### Step 2: Enable Auto-Lock Logic

**2.1 Edit Cron File**

Open: `modules/bookings/class-bookings-cron.php`

Find line ~118 (inside `check_rooming_list_due_dates` method):

```php
/*
// ============================================================
// AUTO-LOCK LOGIC - DISABLED FOR PRODUCTION DEPLOYMENT
// ============================================================
// TO ENABLE: Uncomment this block when ready to go live
```

**2.2 Uncomment the Block**

Remove the `/*` at the beginning and `*/` at the end:

**BEFORE** (Disabled):
```php
/*
// ============================================================
// AUTO-LOCK LOGIC - DISABLED FOR PRODUCTION DEPLOYMENT
// ============================================================

// Check if current date is >= due date
if ($current_date >= $due_date) {
    // Lock the rooming list
    $result = self::lock_rooming_list($booking_id);
    // ... rest of code
}
// ============================================================
*/
```

**AFTER** (Enabled):
```php
// ============================================================
// AUTO-LOCK LOGIC - ENABLED FOR PRODUCTION
// ============================================================

// Check if current date is >= due date
if ($current_date >= $due_date) {
    // Lock the rooming list
    $result = self::lock_rooming_list($booking_id);
    // ... rest of code
}
// ============================================================
```

**2.3 Remove Testing Log**

Find and remove/comment out the testing log (around line 160):

```php
// REMOVE THIS:
// Log booking found (monitoring only - auto-lock disabled)
self::log_cron_activity(sprintf(
    'Booking #%d - Due: %s, Current: %s (Auto-lock disabled)',
    $booking_id,
    $due_date,
    $current_date
));
```

**2.4 Save and Upload**

- Save the file
- Upload to your live server
- Clear any caching (if applicable)

---

### Step 3: Configure Site-Specific Activation (Multisite Only)

**If you want auto-lock only on specific sites:**

Edit `wp-config.php` and add:

```php
// Enable rooming list auto-lock only for specific sites
// Add blog IDs of sites where you want auto-lock enabled
define('OC_ENABLE_ROOMING_LIST_CRON', array(
    1,  // Main site
    3,  // Site 3
    // Add more blog IDs as needed
));
```

**If you want auto-lock on ALL sites:**

Don't add anything to `wp-config.php` - it will be enabled on all sites by default.

---

### Step 4: Schedule Cron Events

**4.1 Activate Plugin on Each Site**

For each site where you want auto-lock:

1. Go to site's admin: `https://your-site.com/wp-admin/`
2. Navigate to **Plugins** → **Installed Plugins**
3. Find **Organization Core Latest**
4. Click **Deactivate**
5. Wait 2 seconds
6. Click **Activate**

**4.2 Verify Cron is Scheduled**

Install **WP Crontrol** plugin:
1. Go to **Plugins** → **Add New**
2. Search for "WP Crontrol"
3. Install and activate
4. Go to **Tools** → **Cron Events**
5. Search for: `oc_check_rooming_list_due_dates`
6. Verify it shows:
   - **Next Run**: Tomorrow at midnight
   - **Recurrence**: Daily

---

### Step 5: Testing

**5.1 Create Test Booking**

1. Create a new booking
2. Assign a hotel
3. Set check-in date: `[Tomorrow's date]`
4. Set due date: `[Today's date]`
5. Create rooming list with 3-5 test entries
6. Save (but don't lock manually)

**5.2 Manually Trigger Cron**

Using WP Crontrol:
1. Go to **Tools** → **Cron Events**
2. Find `oc_check_rooming_list_due_dates`
3. Click **"Run Now"**

**5.3 Verify Results**

Check the rooming list:
1. Go back to the booking
2. Click **"Manage Rooming List"**
3. All items should now be **locked** (lock icon visible)
4. Edit/delete buttons should be disabled

**5.4 Check Logs**

View `wp-content/debug.log`:
```
[OC Bookings Cron] [INFO] Starting due date check...
[OC Bookings Cron] [INFO] Found 1 bookings with due dates
[OC Bookings Cron] [INFO] Locked rooming list for Booking #123
[OC Bookings Cron] [INFO] Locked 5 items for Booking #123
[OC Bookings Cron] [INFO] Due date check completed. Total: 1, Locked: 1, Skipped: 0, Errors: 0
```

**5.5 Delete Test Booking**

After successful test, delete the test booking.

---

### Step 6: Monitor & Maintain

**6.1 Daily Monitoring (First Week)**

- Check debug logs daily
- Verify UptimeRobot shows consistent uptime
- Monitor for any error emails

**6.2 Weekly Monitoring (Ongoing)**

- Review UptimeRobot dashboard
- Check cron logs for errors
- Verify rooming lists are being locked as expected

**6.3 Monthly Maintenance**

- Review and clear old cron logs
- Check database performance
- Verify backup system is working

---

## 🔧 Troubleshooting

### Issue: Cron Not Running

**Symptoms**: Rooming lists not being locked automatically

**Solutions**:
1. Check UptimeRobot monitor status
2. Verify `wp-cron.php` is accessible (visit in browser)
3. Check if `DISABLE_WP_CRON` is set in `wp-config.php` (should be false or not set)
4. Review debug logs for errors

---

### Issue: Some Bookings Not Locking

**Symptoms**: Only some rooming lists are locked

**Solutions**:
1. Verify due dates are set correctly
2. Check if rooming list exists for the booking
3. Review debug logs for specific booking errors
4. Ensure booking status is not "cancelled" or "completed"

---

### Issue: Cron Running But Not Locking

**Symptoms**: Logs show cron runs, but no locking happens

**Solutions**:
1. Verify auto-lock code is uncommented
2. Check if current date >= due date
3. Ensure rooming list has items
4. Review error logs for database issues

---

### Issue: Multiple Sites Not Working (Multisite)

**Symptoms**: Cron works on main site but not subdomains

**Solutions**:
1. Deactivate/reactivate plugin on each site
2. Check `OC_ENABLE_ROOMING_LIST_CRON` configuration
3. Verify cron is scheduled on each site (use WP Crontrol)
4. Review site-specific debug logs

---

## 📊 Performance Expectations

### Normal Operation

- **Cron Execution Time**: 1-5 seconds for 10 bookings
- **Database Queries**: 3 queries per booking
- **Memory Usage**: < 10MB
- **CPU Usage**: Minimal (< 1% spike)

### High Volume

- **100 Bookings**: ~30-60 seconds execution time
- **500 Bookings**: ~2-3 minutes execution time
- **1000+ Bookings**: Consider optimization (batch processing)

---

## 🔒 Security Considerations

1. **Debug Logs**: Disable `WP_DEBUG_LOG` on production after initial testing
2. **File Permissions**: Ensure cron files are not writable by web server
3. **UptimeRobot URL**: Keep `wp-cron.php` URL private (don't share publicly)
4. **Database Backups**: Maintain daily backups before auto-lock runs

---

## 📝 Rollback Procedure

If you need to disable auto-lock:

1. Edit `class-bookings-cron.php`
2. Re-comment the auto-lock block (add `/*` and `*/`)
3. Upload to server
4. Cron will continue running but won't lock anything

---

## ✅ Post-Deployment Checklist

After deployment, verify:

- [ ] UptimeRobot monitor is active and showing "Up"
- [ ] Cron event is scheduled (visible in WP Crontrol)
- [ ] Auto-lock code is uncommented
- [ ] Test booking was successfully locked
- [ ] Debug logs show successful execution
- [ ] Email alerts are configured (optional)
- [ ] Team is notified of new auto-lock feature
- [ ] Documentation is updated with live URLs
- [ ] Backup system is verified
- [ ] Monitoring dashboard is bookmarked

---

## 📞 Support & Resources

### Documentation Files

- **Setup Guide**: `CRON_SETUP_GUIDE.md`
- **Technical Docs**: `CRON_LOGIC_DOCUMENTATION.md`
- **Multisite Guide**: (See artifacts directory)

### Useful Commands

```bash
# Check cron events (WP-CLI)
wp cron event list

# Run cron manually (WP-CLI)
wp cron event run oc_check_rooming_list_due_dates

# View cron logs (SQL)
SELECT option_value FROM wp_options WHERE option_name = 'oc_bookings_cron_log';
```

### Key Files

- **Cron Handler**: `modules/bookings/class-bookings-cron.php`
- **Activator**: `modules/bookings/activator.php`
- **CRUD**: `modules/bookings/crud.php`
- **Debug Log**: `wp-content/debug.log`

---

## 🎉 Success Criteria

Your deployment is successful when:

✅ UptimeRobot shows 99%+ uptime  
✅ Cron runs daily at midnight  
✅ Rooming lists lock automatically on due date  
✅ No errors in debug logs  
✅ Users report lists are locked as expected  
✅ System performance remains normal  

---

**Last Updated**: December 9, 2025  
**Version**: 1.0  
**Author**: Organization Core Development Team
