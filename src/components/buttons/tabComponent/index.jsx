// TabComponent.jsx
import React, { useState } from 'react';
import './TabComponent.css';

const TabComponent = () => {
    const [activeTab, setActiveTab] = useState('All');

    const handleTabClick = (tab) => {
        setActiveTab(tab);
    };

    return (
        <div className="tab-container">
            {['All', 'Active', 'Pending', 'Archived'].map((tab) => (
                <button
                    key={tab}
                    className={`tab-button ${activeTab === tab ? 'active' : ''}`}
                    onClick={() => handleTabClick(tab)}
                >
                    {tab}
                </button>
            ))}
        </div>
    );
};

export default TabComponent;
