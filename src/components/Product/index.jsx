import React from 'react';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faWrench, faCarCrash, faPlusCircle, faMinusCircle } from '@fortawesome/free-solid-svg-icons';
import './style.css'; // Make sure to create a corresponding CSS file

const alertsData = [
    {
        id: 1,
        type: 'maintenance',
        message: 'Vehicle ID 1021 scheduled for maintenance',
    },
    {
        id: 2,
        type: 'out-of-service',
        message: 'Vehicle ID 1018 is currently out of service',
    },
    {
        id: 3,
        type: 'added',
        message: 'New vehicle added: Vehicle ID 1050',
    },
    // ... more alerts
];

const iconMap = {
    maintenance: faWrench,
    'out-of-service': faCarCrash,
    added: faPlusCircle,
    removed: faMinusCircle
};

const statusColorMap = {
    maintenance: 'yellow',
    'out-of-service': 'red',
    added: 'green',
    removed: 'grey'
};

const ProductAlerts = () => {
    return (
        <div className="alerts-container">
            {alertsData.map(alert => (
                <div key={alert.id} className="alert-card" style={{ borderLeftColor: statusColorMap[alert.type] }}>
                    <FontAwesomeIcon icon={iconMap[alert.type]} className="alert-icon" />
                    <span className="alert-message">{alert.message}</span>
                    <button className="see-details-btn">See Details</button>
                </div>
            ))}
            <button className="see-all-btn">See All Alerts</button>
        </div>
    );
};

export default ProductAlerts;
