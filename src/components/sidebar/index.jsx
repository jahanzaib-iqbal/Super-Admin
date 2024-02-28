// import React from 'react'

// function Sidebar() {
//     return (
//         <div className='sidebar'>
//             <div className="logo-container">
//                 <h3>Travelix</h3>
//             </div>
//             <hr />
//             <div className="profile-container">
//                 <img src="https://t4.ftcdn.net/jpg/03/64/21/11/360_F_364211147_1qgLVxv1Tcq0Ohz3FawUfrtONzz8nq3e.jpg" alt="user profile image" />
//                 <h2>Hamdan</h2>
//                 <button>Edit profile</button>
//             </div>
//             <hr />

//         </div>
//     )
// }

// export default Sidebar;


// Sidebar.js
import React, { useState } from 'react';
import { FaHome, FaCar, FaCalendarAlt, FaUsers, FaComments, FaChartPie, FaCog, FaBars, FaSignOutAlt } from 'react-icons/fa';
import './Sidebar.css'; // Assuming you have a corresponding CSS file for styling

const Sidebar = () => {
    const [collapsed, setCollapsed] = useState(false);

    const toggleSidebar = () => setCollapsed(!collapsed);

    const menuItem = [
        { name: 'Dashboard', icon: <FaHome />, path: '/dashboard' },
        { name: 'Bookings', icon: <FaCalendarAlt />, path: '/bookings' },
        { name: 'Vehicles', icon: <FaCar />, path: '/vehicles' },
        { name: 'Customers', icon: <FaUsers />, path: '/customers' },
        { name: 'Feedback', icon: <FaComments />, path: '/feedback' },
        { name: 'Chat Support', icon: <FaComments />, path: '/chat-support' },
        { name: 'Reports', icon: <FaChartPie />, path: '/reports' },
        { name: 'Settings', icon: <FaCog />, path: '/settings' },
    ];
    const userProfile = {
        name: "Admin Name",
        imageUrl: "https://t4.ftcdn.net/jpg/03/64/21/11/360_F_364211147_1qgLVxv1Tcq0Ohz3FawUfrtONzz8nq3e.jpg" // Placeholder image, replace with actual profile image URL
    };
    return (
        <div className={`sidebar ${collapsed ? 'collapsed' : ''}`}>
            <div className="sidebar-header">
                <FaBars onClick={toggleSidebar} />
            </div>
            <div className={`user-profile ${collapsed ? 'collapsed' : ''}`}>
                <img src={userProfile.imageUrl} alt="Profile" className="profile-picture" />
                {!collapsed && <div className="user-name">{userProfile.name}</div>}
            </div>
            <ul className="sidebar-menu">
                {menuItem.map((item, index) => (
                    <li key={index} className={`menu-item ${collapsed ? 'collapsed' : ''}`}>
                        <a href={item.path}>
                            {item.icon}
                            {!collapsed && <span>{item.name}</span>}
                        </a>
                    </li>
                ))}
            </ul>
            <div className={`sidebar-footer ${collapsed ? 'collapsed' : ''}`}>
                <a href="/logout" className={`${collapsed ? 'footer-collapsed' : ''}`}>
                    <FaSignOutAlt />
                    {!collapsed && <span>Logout</span>}
                </a>
            </div>
        </div>
    );
};

export default Sidebar;
