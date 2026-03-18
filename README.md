# School Bus Tracker - Website

A complete, production-ready School Bus Tracking Website built with HTML, PHP, MySQL, JavaScript, and CSS. Designed for deployment on **Hostinger**.

## 🚨 IMPORTANT FEATURES

### **Auto-Refresh Map WITHOUT Permission Popups**
- Uses `watchPosition()` API for continuous GPS tracking
- Location permission asked **ONLY ONCE** by browser
- Auto-updates every 1 second WITHOUT manual "OK" buttons
- No repeated permission dialogs
- Works seamlessly over HTTPS

### **Real-Time Tracking**
- Driver sends live location every 1 second (while webpage is open)
- Parents see bus moving live on Google Maps
- ETA & Distance update automatically
- Smooth marker animation (no jumping)

## 🎯 Roles

1. **Admin** - Manage drivers, parents, students, buses, routes
2. **Driver** - Start/stop trips, send live location, SOS alerts
3. **Parent/Student** - Track bus in real-time, view child information

## 📋 Requirements

- **Web Server**: Apache with PHP 7.4+
- **Database**: MySQL 5.7+
- **SSL Certificate**: Required (for geolocation without repeated prompts)
- **Email**: Gmail SMTP configured
- **Google Maps API**: Key included in config

## 🚀 Installation on Hostinger

### Step 1: Upload Files

1. Login to your Hostinger account
2. Go to **File Manager**
3. Navigate to `public_html` directory
4. Upload all project files (you can zip and upload, then extract)

### Step 2: Create Database

1. Go to **Databases** → **MySQL Databases**
2. Click **Create Database**
3. Database name: `school_bus_tracker` (or your choice)
4. Create a database user and password
5. Add user to database with **ALL PRIVILEGES**
6. Note down:
   - Database name
   - Database username
   - Database password
   - Database host (usually `localhost`)

### Step 3: Import Database Schema

1. Go to **phpMyAdmin**
2. Select your database
3. Click **Import** tab
4. Choose file: `database/schema.sql`
5. Click **Go** to import

### Step 4: Configure Settings

Edit `config.php` file and update:

```php
// Database Configuration
define('DB_HOST', 'localhost'); // Usually localhost
define('DB_USER', 'your_database_username'); // Your DB username
define('DB_PASS', 'your_database_password'); // Your DB password
define('DB_NAME', 'school_bus_tracker'); // Your DB name

// Site URL
define('SITE_URL', 'https://yourdomain.com'); // Your domain
```

### Step 5: Install PHPMailer

#### Option A: Download Manually (Recommended for Hostinger)

1. Download PHPMailer from: https://github.com/PHPMailer/PHPMailer/releases
2. Extract the ZIP file
3. Upload the `src` folder to `vendor/PHPMailer/src/` in your project
4. Your structure should look like:
   ```
   /public_html/
     /vendor/
       /PHPMailer/
         /src/
           PHPMailer.php
           SMTP.php
           Exception.php
   ```

#### Option B: Using Composer (If available)

```bash
cd /path/to/your/project
composer require phpmailer/phpmailer
```

### Step 6: Enable SSL (VERY IMPORTANT)

1. Go to Hostinger Control Panel
2. Navigate to **SSL** section
3. Enable **Free SSL Certificate** for your domain
4. Wait 5-10 minutes for SSL to activate
5. Verify your site loads with `https://`

**Why SSL is Required:**
- Browsers require HTTPS for geolocation features
- Without HTTPS, browser will ask for location permission repeatedly
- With HTTPS, permission is asked only ONCE

### Step 7: Set Permissions

Set proper file permissions via File Manager or SSH:

```bash
chmod 755 /public_html
chmod 644 /public_html/config.php
chmod 755 /public_html/assets
chmod 755 /public_html/uploads (if you create this folder)
```

### Step 8: Test Installation

1. Visit: `https://yourdomain.com`
2. Default admin login:
   - **Email**: admin@schoolbus.com
   - **Mobile**: 9999999999
   - **Password**: admin123

## 📱 How It Works

### For Drivers:

1. Login with driver code or email+mobile+password
2. Click **"Start Trip"**
3. Browser asks for location permission (ONLY ONCE)
4. Click "Allow"
5. Location is sent automatically every 1 second
6. No need to click "OK" repeatedly
7. Click **"Stop Trip"** when done

### For Parents:

1. Login with email+mobile+password
2. See live bus location on map
3. Distance and ETA update automatically every 1 second
4. No page refresh needed
5. Map updates in background

### For Admins:

1. Add drivers, parents, students
2. Create buses and routes
3. Assign students to buses
4. View all buses on live tracking map
5. Download trip reports (CSV)

## 🗺️ Google Maps Features

- **Bus Icon Marker**: Shows bus location
- **User Marker**: Shows parent/student location
- **Distance Calculation**: Shows distance in meters/kilometers
- **ETA Calculation**: Uses Google Directions API
- **Map Toggle**: Switch between Roadmap and Hybrid view
- **Center Bus**: Button to center map on bus
- **Center Me**: Button to center map on user
- **Fit Both**: Auto-fit both markers on screen
- **GPS Accuracy Filter**: Ignores GPS accuracy > 30 meters

## 📧 Email Notifications

Emails are sent automatically for:

1. **Trip Start**: When driver starts trip
2. **Trip End**: When driver completes trip
3. **Bus Near Stop**: When bus approaches pickup point
4. **SOS Alert**: Emergency alert to admin

Gmail SMTP is already configured in `config.php`:
- Email: schoolbustracker.com@gmail.com
- Password: Ankit9977498131@@@

## 💾 Offline Mode (Driver Panel)

When driver loses internet connection:
- Locations are stored in IndexedDB
- Automatically synced when connection returns
- No data loss

## 🔒 Security Features

- Password hashing with bcrypt
- SQL injection prevention (PDO prepared statements)
- XSS protection (htmlspecialchars)
- Session hijacking prevention
- CSRF token support (can be added)
- HTTPS enforcement via .htaccess
- Secure cookie settings

## 📊 Database Tables

- `users` - Admin, drivers, parents
- `buses` - Bus information
- `routes` - Bus routes
- `stops` - Route stops with GPS coordinates
- `students` - Student information
- `student_bus_assignments` - Student-bus mapping
- `trips` - Trip records
- `locations` - Real-time GPS data
- `notifications` - Email notifications

## 🛠️ File Structure

```
/public_html/
├── config.php              # Main configuration
├── index.php              # Login page
├── logout.php             # Logout handler
├── .htaccess              # Apache configuration
├── /admin/                # Admin panel
│   ├── dashboard.php
│   ├── drivers.php
│   ├── parents.php
│   ├── students.php
│   ├── buses.php
│   ├── routes.php
│   ├── trips.php
│   └── live-tracking.php
├── /driver/               # Driver panel
│   └── dashboard.php
├── /parent/               # Parent panel
│   └── dashboard.php
├── /api/                  # API endpoints
│   ├── location.php       # Location updates
│   ├── trip.php          # Trip management
│   └── calculate_eta.php  # ETA calculation
├── /includes/             # Helper files
│   ├── auth.php          # Authentication
│   └── email.php         # Email functions
├── /assets/               # Static assets
│   ├── /css/
│   │   └── style.css
│   └── /images/
│       ├── bus-marker.png
│       └── user-marker.png
├── /database/             # Database files
│   └── schema.sql
└── /vendor/               # Third-party libraries
    └── /PHPMailer/
```

## 🐛 Troubleshooting

### Location Permission Popup Appears Repeatedly

**Solution:**
- Ensure your site is using HTTPS (not HTTP)
- Clear browser cache and cookies
- Check SSL certificate is valid
- Verify `.htaccess` is forcing HTTPS

### Map Not Loading

**Solution:**
- Check Google Maps API key in `config.php`
- Verify API key has Maps JavaScript API enabled
- Check browser console for errors

### Email Not Sending

**Solution:**
- Verify Gmail credentials in `config.php`
- Enable "Less secure app access" in Gmail (or use App Password)
- Check PHPMailer is installed correctly
- Check PHP error logs in Hostinger

### Database Connection Error

**Solution:**
- Verify database credentials in `config.php`
- Check database exists in phpMyAdmin
- Ensure database user has proper privileges

### Trip Not Starting

**Solution:**
- Ensure bus and route are assigned to driver
- Check browser console for JavaScript errors
- Verify driver has active bus assignment

## 📞 Support

For issues:
1. Check browser console (F12) for errors
2. Check PHP error logs in Hostinger cPanel
3. Verify all configuration settings
4. Ensure SSL is active

## 🔄 Future Enhancements (Optional)

- SMS notifications via Twilio
- WebSocket for real-time updates (instead of AJAX polling)
- Mobile app version
- Route optimization
- Student attendance tracking
- Multi-language support

## 📝 Notes

- This is a **WEBSITE**, not a mobile app
- Background tracking only works while browser is open
- Location permission is asked once per browser
- Requires active internet connection for real-time tracking
- Best viewed on modern browsers (Chrome, Firefox, Safari, Edge)

## 🎓 Default Admin Credentials

**Change these after first login!**

- Email: admin@schoolbus.com
- Mobile: 9999999999
- Password: admin123

## 📜 License

This is a custom-built solution. All rights reserved.

---

**Built for Hostinger Deployment | Production-Ready | No External Dependencies**
