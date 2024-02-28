import React from 'react';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faBell, faCircleNotch } from '@fortawesome/free-solid-svg-icons';
import './SystemNotifications.css'; // Make sure to create a corresponding CSS file

const notificationsData = [
    {
        id: 1,
        message: 'System update available. Please update at your earliest convenience.',
        progress: 20,
    },
    // ... more notification objects
];

const SystemNotifications = () => {
    return (
        <div className="notifications-container">
            <h2>System Notifications</h2>
            <div className="notifications-list">
                {notificationsData.map(notification => (
                    <div key={notification.id} className="notification-card">
                        <FontAwesomeIcon icon={faBell} className="notification-icon" />
                        <div className="notification-content">
                            <p>{notification.message}</p>
                            {notification.progress !== undefined && (
                                <div className="progress-bar-background">
                                    <div className="progress-bar-fill" style={{ width: `${notification.progress}%` }}></div>
                                </div>
                            )}
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
};

export default SystemNotifications;
