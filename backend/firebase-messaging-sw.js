importScripts('https://www.gstatic.com/firebasejs/8.3.2/firebase-app.js');
importScripts('https://www.gstatic.com/firebasejs/8.3.2/firebase-messaging.js');

firebase.initializeApp({
    apiKey: "AAAAoU9N6yM:APA91bH_NK7FN2M5bFLc2mC-y4b_u2qY6xaP4g630U436QxlzUo_hP_RSPmVXGKCi7en49UIUnFM0FmtyoLDGlzo_-3jRvuHzhVMAdNQzSRYE3i53eVpNREFXF6Lj_-f_-FYdgQlVUaK",
    authDomain: "foodapp-ea0c1.firebaseapp.com",
    projectId: "foodapp-ea0c1",
    storageBucket: "foodapp-ea0c1.firebasestorage.app",
    messagingSenderId: "692820241187",
    appId: "1:692820241187:android:ce08c139ff7be95dd2fa96",
    measurementId: "G-MF0WC89JZJ"
});

const messaging = firebase.messaging();
messaging.setBackgroundMessageHandler(function (payload) {
    return self.registration.showNotification(payload.data.title, {
        body: payload.data.body ? payload.data.body : '',
        icon: payload.data.icon ? payload.data.icon : ''
    });
});