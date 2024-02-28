import React from 'react';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faEnvelope, faComments, faTicketAlt, faExclamationCircle } from '@fortawesome/free-solid-svg-icons';
import './CustomerSupportQueries.css'; // Make sure to create a corresponding CSS file

const queriesData = [
    {
        id: 1,
        type: 'email',
        content: 'There was an issue with my last booking...',
        priority: 'high',
        date: '2024-02-28'
    },
    // ... more query objects
];

const queryIcons = {
    email: faEnvelope,
    chat: faComments,
    ticket: faTicketAlt
};

const CustomerSupportQueries = () => {
    return (
        <div className="support-queries-container">
            <h2>Customer Support Queries</h2>
            <div className="queries-list">
                {queriesData.map(query => (
                    <div key={query.id} className={`query-card ${query.priority}`}>
                        <FontAwesomeIcon icon={queryIcons[query.type]} className="query-icon" />
                        <div className="query-content">
                            <p>{query.content}</p>
                            <small>{query.date}</small>
                        </div>
                        {query.priority === 'high' && (
                            <FontAwesomeIcon icon={faExclamationCircle} className="priority-flag" />
                        )}
                    </div>
                ))}
            </div>
        </div>
    );
};

export default CustomerSupportQueries;
