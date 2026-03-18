# Hostinger Deployment Guide - Step by Step

## 📌 Complete Deployment Checklist

### Phase 1: Pre-Deployment (Do this first)

- [ ] Download PHPMailer and keep it ready
- [ ] Have your Hostinger credentials ready
- [ ] Prepare a domain name (or use Hostinger subdomain)
- [ ] Create a zip file of the entire project

### Phase 2: Hostinger Setup

#### 1. Access Hostinger Control Panel

```
1. Login to: https://hpanel.hostinger.com
2. Select your hosting plan
3. Navigate to "File Manager"
```

#### 2. Upload Files

```
Method 1: Via File Manager (Recommended)
1. Go to File Manager → public_html
2. Delete default files (index.html, etc.)
3. Click "Upload" button
4. Upload your project ZIP file
5. Right-click ZIP → Extract
6. Move all files from extracted folder to public_html root
7. Delete the empty folder and ZIP file

Method 2: Via FTP
1. Download FileZilla
2. Connect using credentials from Hostinger
3. Upload all files to public_html
```

#### 3. Create MySQL Database

```
1. Go to "Databases" → "MySQL Databases"
2. Click "Create Database"
3. Enter name: school_bus_tracker (or any name)
4. Click "Create"
5. Note down the generated database name (it will have prefix)
   Example: u123456789_school_bus_tracker
6. Create database user:
   - Username: u123456789_dbuser (example)
   - Password: (create strong password)
   - Click "Create"
7. Add user to database:
   - Select user
   - Select database
   - Grant ALL PRIVILEGES
   - Click "Add"
```

#### 4. Import Database Schema

```
1. Go to "Databases" → "phpMyAdmin"
2. Login (credentials auto-filled)
3. Select your database from left sidebar
4. Click "Import" tab at top
5. Click "Choose File"
6. Select: database/schema.sql
7. Click "Go" at bottom
8. Wait for "Import has been successfully finished"
9. Verify tables are created (check left sidebar)
```

#### 5. Configure config.php

```
1. Go to File Manager → public_html
2. Find and edit config.php
3. Update these lines:

define('DB_HOST', 'localhost');
define('DB_USER', 'u123456789_dbuser'); // Your actual DB user
define('DB_PASS', 'your_password_here'); // Your actual DB password
define('DB_NAME', 'u123456789_school_bus_tracker'); // Your actual DB name

define('SITE_URL', 'https://yourdomain.com'); // Your actual domain

4. Click "Save and Close"
```

#### 6. Install PHPMailer

```
1. Download PHPMailer from:
   https://github.com/PHPMailer/PHPMailer/releases/latest
   
2. Extract the ZIP file on your computer

3. Via File Manager:
   - Go to public_html
   - Create new folder: "vendor"
   - Inside vendor, create folder: "PHPMailer"
   - Inside PHPMailer, create folder: "src"
   - Upload these 3 files from PHPMailer download to src/:
     * PHPMailer.php
     * SMTP.php
     * Exception.php

Final structure:
/public_html/vendor/PHPMailer/src/PHPMailer.php
/public_html/vendor/PHPMailer/src/SMTP.php
/public_html/vendor/PHPMailer/src/Exception.php
```

#### 7. Enable SSL Certificate (CRITICAL)

```
1. Go to "SSL" section in Hostinger panel
2. Click on your domain
3. Enable "Free SSL Certificate"
4. Wait 5-10 minutes for activation
5. Test: Visit https://yourdomain.com
6. Ensure it shows padlock icon 🔒
```

**Why SSL is Required:**
- Browser geolocation REQUIRES HTTPS
- Without HTTPS, permission popup appears repeatedly
- With HTTPS, permission asked only once

#### 8. Configure Email (Optional - Already Set)

Gmail SMTP is already configured in the project:
- Host: smtp.gmail.com
- Port: 587
- Email: schoolbustracker.com@gmail.com
- Password: Ankit9977498131@@@

**To use your own Gmail:**
```
1. Edit config.php
2. Update SMTP_USER and SMTP_PASS
3. Enable "Less secure app access" in Gmail OR
4. Use Gmail "App Password" (more secure)
```

### Phase 3: Testing

#### Test 1: Check Website Loads

```
1. Visit: https://yourdomain.com
2. Should see login page
3. Check browser console (F12) for errors
4. Verify no 404 errors
```

#### Test 2: Login as Admin

```
1. Click "Regular Login" tab
2. Enter:
   Email: admin@schoolbus.com
   Mobile: 9999999999
   Password: admin123
3. Should redirect to Admin Dashboard
4. Verify dashboard loads without errors
```

#### Test 3: Create Test Driver

```
1. Go to "Drivers" in sidebar
2. Click "Add New Driver"
3. Fill form:
   Name: Test Driver
   Email: driver@test.com
   Mobile: 1234567890
   Password: test123
4. Click "Add Driver"
5. Copy the generated Driver Code
```

#### Test 4: Create Test Bus

```
1. Go to "Buses"
2. Click "Add New Bus"
3. Fill form:
   Bus Number: BUS-001
   Driver: Select the test driver
   Capacity: 40
4. Click "Add Bus"
```

#### Test 5: Test Driver Login

```
1. Logout from admin
2. Login as driver:
   - Use Driver Code + Password OR
   - Use Email + Mobile + Password
3. Should see Driver Dashboard
4. Click "Start Trip"
5. Browser asks for location permission
6. Click "Allow"
7. Location tracking should start
8. GPS coordinates should update every second
9. Click "Stop Trip" to end
```

#### Test 6: Test Location Tracking

```
1. While driver trip is active
2. In new tab, login as admin
3. Go to "Live Tracking"
4. Should see the active trip
5. Click on trip card
6. Map should show bus marker
7. Marker should move every second
```

### Phase 4: Create Real Data

#### 1. Change Admin Password

```
1. Login as admin
2. Go to admin profile (if available) or
3. Manually update in database:
   - Go to phpMyAdmin
   - Select your database
   - Open "users" table
   - Find admin user (id=1)
   - Update password field with new hash
   OR better: Create a new admin user
```

#### 2. Add Real Drivers

```
1. Go to Admin → Drivers
2. Add each driver with:
   - Full name
   - Valid email
   - Mobile number
   - Strong password
3. Note down driver codes for each driver
4. Share credentials with drivers
```

#### 3. Add Buses

```
1. Go to Admin → Buses
2. For each bus, add:
   - Bus number
   - Registration number
   - Driver assignment
   - Capacity
   - Model
```

#### 4. Create Routes

```
1. Go to Admin → Routes
2. Create routes:
   - Route name (e.g., "North Route", "South Route")
   - Assign bus
   - Start location
   - End location
```

#### 5. Add Parents

```
1. Go to Admin → Parents
2. Add each parent:
   - Full name
   - Email (for notifications)
   - Mobile
   - Password
```

#### 6. Add Students

```
1. Go to Admin → Students
2. For each student:
   - Student name
   - Class
   - Parent (select from dropdown)
   - Home address
   - Home latitude/longitude (optional, use Google Maps)
   - Emergency contact
   - Assign to bus
   - Assign to route
```

### Phase 5: Monitoring & Maintenance

#### Regular Checks

```
✓ Check email notifications are sending
✓ Monitor trip reports weekly
✓ Review location data accuracy
✓ Check for any error logs
✓ Backup database monthly
```

#### Database Backup

```
Method 1: Via phpMyAdmin
1. Go to phpMyAdmin
2. Select database
3. Click "Export"
4. Choose "Quick" method
5. Click "Go"
6. Save SQL file to your computer

Method 2: Via Hostinger
1. Some plans have automatic backups
2. Check "Backups" section in panel
```

#### Log Files

```
Check logs in Hostinger:
1. Go to File Manager
2. Navigate to error_log file
3. Check for PHP errors
4. Fix any issues found
```

## 🚨 Common Issues & Solutions

### Issue: "Database connection failed"

**Solution:**
```
1. Check config.php has correct credentials
2. Verify database name includes prefix
3. Check database user has privileges
4. Test database connection in phpMyAdmin
```

### Issue: "Location permission popup keeps appearing"

**Solution:**
```
1. MUST use HTTPS (not HTTP)
2. Check SSL certificate is active
3. Clear browser cache
4. Try different browser
5. Check .htaccess is forcing HTTPS
```

### Issue: "PHPMailer not found"

**Solution:**
```
1. Verify folder structure:
   /public_html/vendor/PHPMailer/src/
2. Check file names are exact:
   PHPMailer.php (capital P, capital M)
   SMTP.php (all caps)
   Exception.php (capital E)
3. Re-upload if needed
```

### Issue: "Map not loading"

**Solution:**
```
1. Check Google Maps API key in config.php
2. Open browser console (F12)
3. Look for API errors
4. Verify API key has Maps JavaScript API enabled
5. Check if API key has restrictions
```

### Issue: "Trip won't start"

**Solution:**
```
1. Ensure driver has bus assigned
2. Ensure bus has route assigned
3. Check browser console for errors
4. Verify no active trip already exists
5. Check database tables are not empty
```

## 📞 Support Checklist

Before asking for help, check:

- [ ] HTTPS is working (padlock icon visible)
- [ ] Database is imported correctly
- [ ] config.php has correct credentials
- [ ] PHPMailer is uploaded correctly
- [ ] Browser console shows no errors (F12)
- [ ] PHP error logs are checked

## 🎉 Success Indicators

Your deployment is successful when:

✅ Login page loads at https://yourdomain.com
✅ Admin can login successfully
✅ Admin can create drivers, buses, routes
✅ Driver can login and start trip
✅ Location permission asked only ONCE
✅ GPS coordinates update every second
✅ Parents can see live tracking
✅ Map auto-refreshes without manual intervention
✅ Email notifications are sent
✅ No console errors in browser

## 📋 Production Checklist

Before going live:

- [ ] Change default admin password
- [ ] Test all three user roles (admin, driver, parent)
- [ ] Test on mobile devices
- [ ] Test on different browsers
- [ ] Verify email notifications work
- [ ] Test SOS alert functionality
- [ ] Setup database backup schedule
- [ ] Document all passwords securely
- [ ] Train staff on how to use the system
- [ ] Prepare user manuals for drivers and parents

---

**Deployment complete! Your School Bus Tracker is now live! 🚌**
