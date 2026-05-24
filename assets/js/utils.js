// Utility Helper Functions
import { auth, database } from './firebase-config.js';
import { ref, get } from 'https://www.gstatic.com/firebasejs/10.8.0/firebase-database.js';
import { onAuthStateChanged } from 'https://www.gstatic.com/firebasejs/10.8.0/firebase-auth.js';

/**
 * Check if user is authenticated and get their data
 * @returns {Promise} - Object with user and userData or null
 */
export async function getAuthenticatedUser() {
    return new Promise((resolve) => {
        const unsubscribe = onAuthStateChanged(auth, async (user) => {
            if (user) {
                try {
                    const userRef = ref(database, `users/${user.uid}`);
                    const snapshot = await get(userRef);
                    if (snapshot.exists()) {
                        resolve({
                            user,
                            userData: snapshot.val()
                        });
                    } else {
                        resolve({ user, userData: null });
                    }
                } catch (error) {
                    console.error('Error fetching user data:', error);
                    resolve({ user, userData: null });
                }
            } else {
                resolve(null);
            }
            unsubscribe();
        });
    });
}

/**
 * Require user authentication - redirect if not logged in
 * @param {string} redirectPath - Path to redirect to if not authenticated
 */
export async function requireAuth(redirectPath = '/index.html') {
    const authUser = await getAuthenticatedUser();
    if (!authUser) {
        window.location.href = redirectPath;
        return null;
    }
    return authUser;
}

/**
 * Require specific role - redirect if user doesn't have the role
 * @param {array|string} allowedRoles - Role(s) that are allowed
 * @param {string} redirectPath - Path to redirect to if not authorized
 */
export async function requireRole(allowedRoles, redirectPath = '/index.html') {
    const authUser = await getAuthenticatedUser();
    if (!authUser) {
        window.location.href = redirectPath;
        return null;
    }

    const roles = Array.isArray(allowedRoles) ? allowedRoles : [allowedRoles];
    
    // Treat sub-admins as admins
    if (roles.includes('admin') && !roles.includes('sub_admin')) {
        roles.push('sub_admin');
    }

    if (!roles.includes(authUser.userData.role)) {
        window.location.href = redirectPath;
        return null;
    }

    return authUser;
}

/**
 * Get role-based dashboard path
 * @param {string} role - User role
 * @returns {string} - Dashboard path
 */
export function getRoleDashboardPath(role) {
    switch (role) {
        case 'admin':
        case 'sub_admin':
            return '/admin/dashboard.html';
        case 'driver':
            return '/driver/dashboard.html';
        case 'parent':
            return '/parent/dashboard.html';
        default:
            return '/index.html';
    }
}

/**
 * Format date for display
 * @param {string|Date} date - Date to format
 * @returns {string} - Formatted date
 */
export function formatDate(date) {
    if (!date) return '';
    const d = typeof date === 'string' ? new Date(date) : date;
    return d.toLocaleDateString('en-IN', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

/**
 * Format time for display
 * @param {string|Date} time - Time to format
 * @returns {string} - Formatted time
 */
export function formatTime(time) {
    if (!time) return '';
    const d = typeof time === 'string' ? new Date(time) : time;
    return d.toLocaleTimeString('en-IN', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });
}

/**
 * Format datetime for display
 * @param {string|Date} datetime - Datetime to format
 * @returns {string} - Formatted datetime
 */
export function formatDateTime(datetime) {
    if (!datetime) return '';
    return formatDate(datetime) + ' ' + formatTime(datetime);
}

/**
 * Calculate distance between two coordinates using Haversine formula
 * @param {number} lat1 - Latitude of point 1
 * @param {number} lon1 - Longitude of point 1
 * @param {number} lat2 - Latitude of point 2
 * @param {number} lon2 - Longitude of point 2
 * @returns {number} - Distance in kilometers
 */
export function calculateDistance(lat1, lon1, lat2, lon2) {
    const R = 6371; // Earth's radius in km
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
        Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
        Math.sin(dLon / 2) * Math.sin(dLon / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    return R * c;
}

/**
 * Estimate ETA based on distance and average speed
 * @param {number} distance - Distance in km
 * @param {number} averageSpeed - Average speed in km/h (default 40)
 * @returns {number} - ETA in minutes
 */
export function estimateETA(distance, averageSpeed = 40) {
    if (distance <= 0 || averageSpeed <= 0) return 0;
    return Math.round((distance / averageSpeed) * 60);
}

/**
 * Show notification toast
 * @param {string} message - Notification message
 * @param {string} type - Type: 'success', 'error', 'warning', 'info'
 * @param {number} duration - Duration in milliseconds
 */
export function showNotification(message, type = 'info', duration = 3000) {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    
    const style = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 4px;
        color: white;
        font-size: 14px;
        z-index: 10000;
        animation: slideIn 0.3s ease-out;
    `;
    
    const bgColor = {
        'success': '#4CAF50',
        'error': '#f44336',
        'warning': '#ff9800',
        'info': '#2196F3'
    };
    
    notification.style.cssText = style + `background-color: ${bgColor[type] || bgColor.info};`;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease-in';
        setTimeout(() => notification.remove(), 300);
    }, duration);
}

/**
 * Show loading spinner
 * @returns {HTMLElement} - Spinner element
 */
export function showLoadingSpinner(message = 'Loading...') {
    const spinner = document.createElement('div');
    spinner.className = 'loading-spinner';
    spinner.innerHTML = `
        <div class="spinner"></div>
        <p>${message}</p>
    `;
    spinner.style.cssText = `
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: white;
        padding: 40px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        z-index: 10001;
        text-align: center;
    `;
    document.body.appendChild(spinner);
    return spinner;
}

/**
 * Hide loading spinner
 * @param {HTMLElement} spinner - Spinner element
 */
export function hideLoadingSpinner(spinner) {
    if (spinner && spinner.parentNode) {
        spinner.remove();
    }
}

/**
 * Validate email format
 * @param {string} email - Email to validate
 * @returns {boolean} - True if valid email
 */
export function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

/**
 * Validate phone number (10 digits for India)
 * @param {string} phone - Phone number to validate
 * @returns {boolean} - True if valid phone
 */
export function isValidPhone(phone) {
    const phoneRegex = /^[0-9]{10}$/;
    return phoneRegex.test(phone);
}

/**
 * Validate password strength
 * @param {string} password - Password to validate
 * @returns {object} - Object with isValid and message
 */
export function validatePasswordStrength(password) {
    if (password.length < 6) {
        return { isValid: false, message: 'Password must be at least 6 characters' };
    }
    if (!/[A-Z]/.test(password)) {
        return { isValid: false, message: 'Password must contain uppercase letter' };
    }
    if (!/[0-9]/.test(password)) {
        return { isValid: false, message: 'Password must contain number' };
    }
    return { isValid: true, message: 'Strong password' };
}

/**
 * Generate unique ID
 * @returns {string} - Unique ID
 */
export function generateId() {
    return 'ID_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
}

/**
 * Parse GPS coordinates
 * @param {string|number} value - GPS value
 * @returns {number} - Parsed coordinate
 */
export function parseCoordinate(value) {
    const parsed = parseFloat(value);
    return isNaN(parsed) ? 0 : parsed;
}

/**
 * Check if location permission is granted
 * @returns {Promise<boolean>} - True if permission granted
 */
export async function isLocationPermissionGranted() {
    try {
        if (!navigator.permissions || !navigator.permissions.query) {
            return true; // Assume granted if API not available
        }
        const permission = await navigator.permissions.query({ name: 'geolocation' });
        return permission.state === 'granted';
    } catch (error) {
        console.log('Error checking location permission:', error);
        return true;
    }
}

/**
 * Sanitize HTML string to prevent XSS
 * @param {string} str - String to sanitize
 * @returns {string} - Sanitized string
 */
export function sanitizeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}
