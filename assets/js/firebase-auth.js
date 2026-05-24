// Firebase Authentication Module
import {
    createUserWithEmailAndPassword,
    signInWithEmailAndPassword,
    signOut,
    onAuthStateChanged,
    setPersistence,
    browserLocalPersistence,
    sendPasswordResetEmail
} from 'https://www.gstatic.com/firebasejs/10.8.0/firebase-auth.js';
import { auth, database } from './firebase-config.js';
import { ref, set, get, child, update } from 'https://www.gstatic.com/firebasejs/10.8.0/firebase-database.js';

// Set persistence to LOCAL
setPersistence(auth, browserLocalPersistence).catch(err => console.log('Persistence error:', err));

/**
 * Register a new user with Firebase
 * @param {string} email - User email
 * @param {string} password - User password
 * @param {object} userData - Additional user data (name, mobile, role)
 * @returns {Promise}
 */
export async function registerUser(email, password, userData) {
    try {
        // Create user with email and password
        const userCredential = await createUserWithEmailAndPassword(auth, email, password);
        const user = userCredential.user;
        
        // Create user profile in database
        const userRef = ref(database, `users/${user.uid}`);
        await set(userRef, {
            uid: user.uid,
            name: userData.name,
            email: user.email,
            mobile: userData.mobile,
            role: userData.role || 'parent',
            createdAt: new Date().toISOString(),
            updatedAt: new Date().toISOString(),
            isActive: true
        });
        
        return { success: true, user, uid: user.uid };
    } catch (error) {
        console.error('Registration error:', error);
        return { success: false, error: error.message };
    }
}

/**
 * Login user with email and password
 * @param {string} email - User email
 * @param {string} password - User password
 * @returns {Promise}
 */
export async function loginUser(email, password) {
    try {
        const userCredential = await signInWithEmailAndPassword(auth, email, password);
        const user = userCredential.user;
        
        // Get user data from database to verify role and status
        const userRef = ref(database, `users/${user.uid}`);
        const snapshot = await get(userRef);
        
        if (!snapshot.exists()) {
            await signOut(auth);
            return { success: false, error: 'User profile not found' };
        }
        
        const userData = snapshot.val();
        
        if (!userData.isActive) {
            await signOut(auth);
            return { success: false, error: 'Your account has been deactivated' };
        }
        
        return { success: true, user, userData };
    } catch (error) {
        console.error('Login error:', error);
        let errorMessage = 'Login failed';
        if (error.code === 'auth/user-not-found') {
            errorMessage = 'User not found';
        } else if (error.code === 'auth/wrong-password') {
            errorMessage = 'Invalid password';
        } else if (error.code === 'auth/invalid-email') {
            errorMessage = 'Invalid email address';
        }
        return { success: false, error: errorMessage };
    }
}

/**
 * Logout current user
 * @returns {Promise}
 */
export async function logoutUser() {
    try {
        await signOut(auth);
        return { success: true };
    } catch (error) {
        console.error('Logout error:', error);
        return { success: false, error: error.message };
    }
}

/**
 * Send password reset email
 * @param {string} email - User email
 * @returns {Promise}
 */
export async function resetPassword(email) {
    try {
        await sendPasswordResetEmail(auth, email);
        return { success: true };
    } catch (error) {
        console.error('Password reset error:', error);
        return { success: false, error: error.message };
    }
}

/**
 * Get current authenticated user
 * @returns {Promise}
 */
export function getCurrentUser() {
    return new Promise((resolve) => {
        const unsubscribe = onAuthStateChanged(auth, async (user) => {
            if (user) {
                const userRef = ref(database, `users/${user.uid}`);
                try {
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
 * Setup auth state listener
 * @param {function} callback - Callback function when auth state changes
 * @returns {function} - Unsubscribe function
 */
export function setupAuthListener(callback) {
    return onAuthStateChanged(auth, async (user) => {
        if (user) {
            const userRef = ref(database, `users/${user.uid}`);
            try {
                const snapshot = await get(userRef);
                if (snapshot.exists()) {
                    callback({ user, userData: snapshot.val() });
                } else {
                    callback({ user, userData: null });
                }
            } catch (error) {
                console.error('Error fetching user data:', error);
                callback({ user, userData: null });
            }
        } else {
            callback(null);
        }
    });
}

/**
 * Update user profile
 * @param {string} uid - User UID
 * @param {object} updates - Object with fields to update
 * @returns {Promise}
 */
export async function updateUserProfile(uid, updates) {
    try {
        const userRef = ref(database, `users/${uid}`);
        await update(userRef, {
            ...updates,
            updatedAt: new Date().toISOString()
        });
        return { success: true };
    } catch (error) {
        console.error('Update profile error:', error);
        return { success: false, error: error.message };
    }
}

/**
 * Get user by email (helper for driver login)
 * @param {string} email - User email
 * @returns {Promise}
 */
export async function getUserByEmail(email) {
    try {
        const usersRef = ref(database, 'users');
        const snapshot = await get(usersRef);
        
        if (!snapshot.exists()) {
            return { success: false, error: 'User not found' };
        }
        
        const users = snapshot.val();
        const userData = Object.values(users).find(u => u.email === email);
        
        if (!userData) {
            return { success: false, error: 'User not found' };
        }
        
        return { success: true, userData };
    } catch (error) {
        console.error('Get user by email error:', error);
        return { success: false, error: error.message };
    }
}
