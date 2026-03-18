# Quick Start Guide - School Bus Tracker

## 🚀 Get Started in 5 Minutes

### Step 1: Upload to Hostinger (2 minutes)
```
1. Login to Hostinger File Manager
2. Go to public_html folder
3. Upload all project files
4. Done!
```

### Step 2: Create Database (1 minute)
```
1. Go to Databases → Create Database
2. Name: school_bus_tracker
3. Create user and password
4. Import: database/schema.sql
```

### Step 3: Configure (1 minute)
Edit `config.php`:
```php
define('DB_USER', 'your_db_username');
define('DB_PASS', 'your_db_password');
define('DB_NAME', 'your_db_name');
define('SITE_URL', 'https://yourdomain.com');
```

### Step 4: Install PHPMailer (1 minute)
```
1. Download from: https://github.com/PHPMailer/PHPMailer/releases
2. Upload to: vendor/PHPMailer/src/
3. Files needed: PHPMailer.php, SMTP.php, Exception.php
```

### Step 5: Enable SSL (Automatic)
```
1. Go to SSL section in Hostinger
2. Enable Free SSL
3. Wait 5-10 minutes
```

## ✅ Test Your Installation

### 1. Login as Admin
```
URL: https://yourdomain.com
Email: admin@schoolbus.com
Mobile: 9999999999
Password: admin123
```

### 2. Add a Test Driver
```
Admin Panel → Drivers → Add New Driver
System generates unique driver code
```

### 3. Add a Test Bus
```
Admin Panel → Buses → Add New Bus
Assign to the driver you created
```

### 4. Test Driver Panel
```
Logout → Login as Driver
Start Trip → Allow Location → Location tracks every 1 second
Stop Trip → Done!
```

### 5. Test Live Tracking
```
Login as Admin → Live Tracking
See bus moving on map in real-time
```

## 🎯 Key Features to Test

### ✅ Location Permission (MOST IMPORTANT)
- Should ask permission **ONLY ONCE**
- No repeated popups after first allow
- Must use HTTPS for this to work
- If popup repeats: Check SSL is active

### ✅ Real-Time Updates
- Location updates every 1 second
- Map refreshes automatically
- No manual refresh needed
- Smooth marker movement

### ✅ Email Notifications
- Trip start sends email to parents
- Trip end sends email to parents
- SOS sends email to admin
- Check spam folder if not received

## 🐛 Quick Troubleshooting

### Database Connection Error
```
→ Check config.php credentials
→ Verify database name includes prefix
→ Check database exists in phpMyAdmin
```

### Location Permission Repeating
```
→ MUST use HTTPS (not HTTP)
→ Check SSL certificate is active
→ Clear browser cache and cookies
→ Try incognito/private mode
```

### Map Not Loading
```
→ Check Google Maps API key in config.php
→ Open browser console (F12) for errors
→ Verify API has Maps JavaScript API enabled
```

### Email Not Sending
```
→ Check PHPMailer is uploaded correctly
→ Verify Gmail credentials in config.php
→ Check PHP error logs in Hostinger
```

## 📞 Need Help?

1. Read full README.md for details
2. Check DEPLOYMENT_GUIDE.md for step-by-step
3. Review FEATURES.md for all capabilities
4. Check browser console (F12) for JavaScript errors
5. Check PHP error logs in Hostinger

## 🎉 Success Checklist

- [ ] Website loads at https://yourdomain.com
- [ ] Admin can login successfully
- [ ] Admin can create driver, bus, route
- [ ] Driver can login and start trip
- [ ] Location permission asked only once
- [ ] GPS updates every 1 second
- [ ] Parent can see live tracking
- [ ] Map auto-refreshes without popup
- [ ] Email notifications sent
- [ ] No console errors

## 🔐 Security Reminder

**After installation, immediately:**
1. Change admin password
2. Update config.php with your Gmail (optional)
3. Backup database regularly
4. Keep credentials secure

## 📚 Next Steps

1. Add real drivers
2. Add real buses
3. Create routes
4. Add parents
5. Add students
6. Assign students to buses
7. Start using the system!

---

**You're ready to track school buses! 🚌**

Need detailed instructions? Check `DEPLOYMENT_GUIDE.md`
