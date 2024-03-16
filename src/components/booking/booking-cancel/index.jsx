import React, { useState } from 'react';
import './style.css'; // Make sure to create and link the CSS file

const CancelBooking = ({ bookingId, onCancel }) => {
    const [notifyEmail, setNotifyEmail] = useState(true);
    const [notifySMS, setNotifySMS] = useState(false);

    const handleCancel = () => {
        onCancel(bookingId, { notifyEmail, notifySMS });
        // Trigger the actual cancellation logic here, such as updating the database and sending notifications.
    };

    return (
        <div className="cancel-booking-container">
            <h3>Cancel Booking</h3>
            <p>Booking ID: {bookingId}</p>
            <div className="notification-options">
                <label>
                    <input
                        type="checkbox"
                        checked={notifyEmail}
                        onChange={(e) => setNotifyEmail(e.target.checked)}
                    />
                    Notify by Email
                </label>
                <label>
                    <input
                        type="checkbox"
                        checked={notifySMS}
                        onChange={(e) => setNotifySMS(e.target.checked)}
                    />
                    Notify by SMS
                </label>
            </div>
            <button onClick={handleCancel}>Confirm Cancellation</button>
        </div>
    );
};

export default CancelBooking;
