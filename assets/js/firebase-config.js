// Firebase Configuration
import { initializeApp } from 'https://www.gstatic.com/firebasejs/10.8.0/firebase-app.js';
import { getAuth } from 'https://www.gstatic.com/firebasejs/10.8.0/firebase-auth.js';
import { getDatabase } from 'https://www.gstatic.com/firebasejs/10.8.0/firebase-database.js';

const firebaseConfig = {
    apiKey: "AIzaSyAVZ51_kUdiJ_7Kaj5pVtfcHSa84qXTvkk",
    authDomain: "school-bus-tracker-app-sanjay.firebaseapp.com",
    databaseURL: "https://school-bus-tracker-app-sanjay-default-rtdb.firebaseio.com",
    projectId: "school-bus-tracker-app-sanjay",
    storageBucket: "school-bus-tracker-app-sanjay.firebasestorage.app",
    messagingSenderId: "887455472863",
    appId: "1:887455472863:web:59536175e55d549c811d6a"
};

// Initialize Firebase
const app = initializeApp(firebaseConfig);
const auth = getAuth(app);
const database = getDatabase(app);

// Enable offline persistence
try {
    auth.setPersistence(auth.PERSISTENCE);
} catch (e) {
    console.log('Offline persistence not available');
}

export { app, auth, database, firebaseConfig };
