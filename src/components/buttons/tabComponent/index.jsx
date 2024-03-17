import React from 'react';
import './TabComponent.css';
const TabComponent = ({ activeTab, onChange }) => {
    console.log("Active Tab on render: ", activeTab); // Debug: Log active tab

    const handleTabClick = (tab) => {
        console.log("Tab clicked: ", tab); // Debug: Log clicked tab
        onChange(tab);
    };

    return (
        <div className="tab-container">
            {['all', 'active', 'pending', 'archived'].map((tab) => (
                <button
                    key={tab}
                    className={`tab-button ${activeTab === tab ? 'active' : ''}`}
                    onClick={() => handleTabClick(tab)}
                >
                    {tab.charAt(0).toUpperCase() + tab.slice(1)}
                </button>
            ))}
        </div>
    );
};


export default TabComponent;
