import React from 'react';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faCar, faDollarSign, faThumbsUp, faCalendarCheck } from '@fortawesome/free-solid-svg-icons';
import './style.css';

const DashboardCards = () => {
    const stats = [
        {
            id: 1,
            title: 'Total Bookings',
            value: '320',
            icon: faCar,
        },
        {
            id: 2,
            title: 'Active Rentals',
            value: '85',
            icon: faCalendarCheck,
        },
        {
            id: 3,
            title: 'Customer Satisfaction',
            value: '95%',
            icon: faThumbsUp,
        },
        {
            id: 4,
            title: 'Revenue',
            value: '$24K',
            icon: faDollarSign,
        },
    ];

    return (
        <div className="dashboard-cards">
            {stats.map((stat) => (
                <div key={stat.id} className="dashboard-card">
                    <FontAwesomeIcon icon={stat.icon} className="card-icon" />
                    <div className="card-title">{stat.title}</div>
                    <div className="card-value">{stat.value}</div>
                </div>
            ))}
        </div>
    );
};

export default DashboardCards;
