// Firebase Realtime Database Module
import {
    ref,
    set,
    get,
    update,
    remove,
    push,
    onValue,
    query,
    orderByChild,
    equalTo,
    limitToLast,
    off
} from 'https://www.gstatic.com/firebasejs/10.8.0/firebase-database.js';
import { database } from './firebase-config.js';

// ==================== USERS ====================

export async function getUserData(uid) {
    try {
        const userRef = ref(database, `users/${uid}`);
        const snapshot = await get(userRef);
        return snapshot.exists() ? snapshot.val() : null;
    } catch (error) {
        console.error('Error getting user data:', error);
        return null;
    }
}

export async function getAllUsers(role = null) {
    try {
        const usersRef = ref(database, 'users');
        const snapshot = await get(usersRef);
        
        if (!snapshot.exists()) return [];
        
        const users = snapshot.val();
        const userList = Object.values(users);
        
        if (role) {
            return userList.filter(u => u.role === role);
        }
        
        return userList;
    } catch (error) {
        console.error('Error getting users:', error);
        return [];
    }
}

export async function updateUserData(uid, updates) {
    try {
        const userRef = ref(database, `users/${uid}`);
        await update(userRef, {
            ...updates,
            updatedAt: new Date().toISOString()
        });
        return { success: true };
    } catch (error) {
        console.error('Error updating user:', error);
        return { success: false, error: error.message };
    }
}

// ==================== DRIVERS ====================

export async function getDriverData(driverId) {
    try {
        const driverRef = ref(database, `drivers/${driverId}`);
        const snapshot = await get(driverRef);
        return snapshot.exists() ? snapshot.val() : null;
    } catch (error) {
        console.error('Error getting driver data:', error);
        return null;
    }
}

export async function getAllDrivers() {
    try {
        return await getAllUsers('driver');
    } catch (error) {
        console.error('Error getting drivers:', error);
        return [];
    }
}

export async function createDriver(driverId, driverData) {
    try {
        const driverRef = ref(database, `drivers/${driverId}`);
        await set(driverRef, {
            ...driverData,
            createdAt: new Date().toISOString(),
            updatedAt: new Date().toISOString()
        });
        return { success: true };
    } catch (error) {
        console.error('Error creating driver:', error);
        return { success: false, error: error.message };
    }
}

// ==================== BUSES ====================

export async function getBusData(busId) {
    try {
        const busRef = ref(database, `buses/${busId}`);
        const snapshot = await get(busRef);
        return snapshot.exists() ? snapshot.val() : null;
    } catch (error) {
        console.error('Error getting bus data:', error);
        return null;
    }
}

export async function getAllBuses() {
    try {
        const busesRef = ref(database, 'buses');
        const snapshot = await get(busesRef);
        return snapshot.exists() ? Object.values(snapshot.val()) : [];
    } catch (error) {
        console.error('Error getting buses:', error);
        return [];
    }
}

export async function createBus(busId, busData) {
    try {
        const busRef = ref(database, `buses/${busId}`);
        await set(busRef, {
            ...busData,
            createdAt: new Date().toISOString(),
            updatedAt: new Date().toISOString()
        });
        return { success: true };
    } catch (error) {
        console.error('Error creating bus:', error);
        return { success: false, error: error.message };
    }
}

export async function updateBusLocation(busId, latitude, longitude, speed = 0) {
    try {
        const locationRef = ref(database, `locations/${busId}`);
        await set(locationRef, {
            latitude,
            longitude,
            speed,
            updatedAt: new Date().toISOString()
        });
        return { success: true };
    } catch (error) {
        console.error('Error updating bus location:', error);
        return { success: false, error: error.message };
    }
}

export function subscribeToBusLocation(busId, callback) {
    try {
        const locationRef = ref(database, `locations/${busId}`);
        const unsubscribe = onValue(locationRef, (snapshot) => {
            if (snapshot.exists()) {
                callback(snapshot.val());
            }
        });
        return unsubscribe;
    } catch (error) {
        console.error('Error subscribing to bus location:', error);
        return () => {};
    }
}

// ==================== ROUTES ====================

export async function getRouteData(routeId) {
    try {
        const routeRef = ref(database, `routes/${routeId}`);
        const snapshot = await get(routeRef);
        return snapshot.exists() ? snapshot.val() : null;
    } catch (error) {
        console.error('Error getting route data:', error);
        return null;
    }
}

export async function getAllRoutes() {
    try {
        const routesRef = ref(database, 'routes');
        const snapshot = await get(routesRef);
        return snapshot.exists() ? Object.values(snapshot.val()) : [];
    } catch (error) {
        console.error('Error getting routes:', error);
        return [];
    }
}

export async function createRoute(routeId, routeData) {
    try {
        const routeRef = ref(database, `routes/${routeId}`);
        await set(routeRef, {
            ...routeData,
            createdAt: new Date().toISOString(),
            updatedAt: new Date().toISOString()
        });
        return { success: true };
    } catch (error) {
        console.error('Error creating route:', error);
        return { success: false, error: error.message };
    }
}

// ==================== STUDENTS ====================

export async function getStudentData(studentId) {
    try {
        const studentRef = ref(database, `students/${studentId}`);
        const snapshot = await get(studentRef);
        return snapshot.exists() ? snapshot.val() : null;
    } catch (error) {
        console.error('Error getting student data:', error);
        return null;
    }
}

export async function getStudentsByParent(parentId) {
    try {
        const studentsRef = ref(database, 'students');
        const snapshot = await get(studentsRef);
        
        if (!snapshot.exists()) return [];
        
        const students = snapshot.val();
        return Object.values(students).filter(s => s.parentId === parentId);
    } catch (error) {
        console.error('Error getting students:', error);
        return [];
    }
}

export async function getAllStudents() {
    try {
        const studentsRef = ref(database, 'students');
        const snapshot = await get(studentsRef);
        return snapshot.exists() ? Object.values(snapshot.val()) : [];
    } catch (error) {
        console.error('Error getting students:', error);
        return [];
    }
}

export async function createStudent(studentId, studentData) {
    try {
        const studentRef = ref(database, `students/${studentId}`);
        await set(studentRef, {
            ...studentData,
            createdAt: new Date().toISOString(),
            updatedAt: new Date().toISOString()
        });
        return { success: true };
    } catch (error) {
        console.error('Error creating student:', error);
        return { success: false, error: error.message };
    }
}

// ==================== TRIPS ====================

export async function getTripData(tripId) {
    try {
        const tripRef = ref(database, `trips/${tripId}`);
        const snapshot = await get(tripRef);
        return snapshot.exists() ? snapshot.val() : null;
    } catch (error) {
        console.error('Error getting trip data:', error);
        return null;
    }
}

export async function getAllTrips() {
    try {
        const tripsRef = ref(database, 'trips');
        const snapshot = await get(tripsRef);
        return snapshot.exists() ? Object.values(snapshot.val()) : [];
    } catch (error) {
        console.error('Error getting trips:', error);
        return [];
    }
}

export async function getActiveTrips() {
    try {
        const trips = await getAllTrips();
        return trips.filter(t => t.status === 'started');
    } catch (error) {
        console.error('Error getting active trips:', error);
        return [];
    }
}

export async function getTripsByDriver(driverId) {
    try {
        const trips = await getAllTrips();
        return trips.filter(t => t.driverId === driverId);
    } catch (error) {
        console.error('Error getting driver trips:', error);
        return [];
    }
}

export async function createTrip(tripId, tripData) {
    try {
        const tripRef = ref(database, `trips/${tripId}`);
        await set(tripRef, {
            ...tripData,
            status: 'started',
            createdAt: new Date().toISOString(),
            updatedAt: new Date().toISOString()
        });
        return { success: true };
    } catch (error) {
        console.error('Error creating trip:', error);
        return { success: false, error: error.message };
    }
}

export async function updateTrip(tripId, updates) {
    try {
        const tripRef = ref(database, `trips/${tripId}`);
        await update(tripRef, {
            ...updates,
            updatedAt: new Date().toISOString()
        });
        return { success: true };
    } catch (error) {
        console.error('Error updating trip:', error);
        return { success: false, error: error.message };
    }
}

export async function endTrip(tripId) {
    try {
        await updateTrip(tripId, { status: 'ended' });
        return { success: true };
    } catch (error) {
        console.error('Error ending trip:', error);
        return { success: false, error: error.message };
    }
}

// ==================== NOTIFICATIONS ====================

export async function createNotification(userId, notificationData) {
    try {
        const notificationsRef = ref(database, `notifications/${userId}`);
        const newNotifRef = push(notificationsRef);
        await set(newNotifRef, {
            ...notificationData,
            createdAt: new Date().toISOString(),
            read: false
        });
        return { success: true, id: newNotifRef.key };
    } catch (error) {
        console.error('Error creating notification:', error);
        return { success: false, error: error.message };
    }
}

export async function getNotifications(userId) {
    try {
        const notificationsRef = ref(database, `notifications/${userId}`);
        const snapshot = await get(notificationsRef);
        return snapshot.exists() ? Object.values(snapshot.val()) : [];
    } catch (error) {
        console.error('Error getting notifications:', error);
        return [];
    }
}

export function subscribeToNotifications(userId, callback) {
    try {
        const notificationsRef = ref(database, `notifications/${userId}`);
        const unsubscribe = onValue(notificationsRef, (snapshot) => {
            if (snapshot.exists()) {
                const notifs = snapshot.val();
                callback(Object.values(notifs));
            } else {
                callback([]);
            }
        });
        return unsubscribe;
    } catch (error) {
        console.error('Error subscribing to notifications:', error);
        return () => {};
    }
}

// ==================== REAL-TIME LISTENERS ====================

/**
 * Subscribe to real-time data changes
 * @param {string} path - Database path
 * @param {function} callback - Callback function
 * @returns {function} - Unsubscribe function
 */
export function subscribeToData(path, callback) {
    try {
        const dataRef = ref(database, path);
        const unsubscribe = onValue(dataRef, (snapshot) => {
            callback(snapshot.exists() ? snapshot.val() : null);
        });
        return unsubscribe;
    } catch (error) {
        console.error('Error subscribing to data:', error);
        return () => {};
    }
}

/**
 * Unsubscribe from real-time updates
 * @param {string} path - Database path
 */
export function unsubscribeFromData(path) {
    try {
        const dataRef = ref(database, path);
        off(dataRef);
    } catch (error) {
        console.error('Error unsubscribing:', error);
    }
}

// ==================== REAL-TIME TRACKING ====================

/**
 * Start tracking a bus location in real-time
 * @param {string} busId - Bus ID
 * @param {function} callback - Callback function with location data
 * @returns {function} - Unsubscribe function
 */
export function startTrackingBus(busId, callback) {
    return subscribeToBusLocation(busId, callback);
}

/**
 * Stop tracking a bus
 * @param {function} unsubscribe - Unsubscribe function returned from startTrackingBus
 */
export function stopTrackingBus(unsubscribe) {
    if (unsubscribe && typeof unsubscribe === 'function') {
        unsubscribe();
    }
}
