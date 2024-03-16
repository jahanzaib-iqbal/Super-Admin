import React, { useState } from 'react';
import './style.css'; // Ensure this is correctly linked

// Sample bookings data
const bookingsData = [
    { id: 'BK001', customerName: 'Jane Doe', vehicleModel: 'Toyota Corolla', pickupDate: '2024-03-10', dropoffDate: '2024-03-15', status: 'Confirmed' },
    { id: 'BK002', customerName: 'John Smith', vehicleModel: 'Honda Civic', pickupDate: '2024-03-12', dropoffDate: '2024-03-16', status: 'Pending' },
    { id: 'BK002', customerName: 'John Smith', vehicleModel: 'Honda Civic', pickupDate: '2024-03-12', dropoffDate: '2024-03-16', status: 'Canceled' },
];

const BookingOverview = () => {
    const [filter, setFilter] = useState('');

    // Filter bookings based on the selected status
    const filteredBookings = bookingsData.filter(booking =>
        filter.length === 0 || booking.status.toLowerCase() === filter.toLowerCase()
    );

    return (
        <div className="booking-overview-container">
            <h2>Recent Bookings</h2>
            <div className="filter-section">
                <select onChange={(e) => setFilter(e.target.value)} defaultValue="">
                    <option value="">All Statuses</option>
                    <option value="Confirmed">Confirmed</option>
                    <option value="Pending">Pending</option>
                    <option value="Canceled">Canceled</option>
                </select>
            </div>
            <div className="bookings-list">
                {filteredBookings.map((booking) => (
                    <div key={booking.id} className="booking-card">
                        <div className="booking-header">
                            <span className={`status-indicator ${booking.status.toLowerCase()}`}></span>
                            <h3 className="customer-name">{booking.customerName}</h3>
                        </div>
                        <div className="booking-info">
                            <p><strong>Vehicle:</strong> {booking.vehicleModel}</p>
                            <p><strong>Dates:</strong> {booking.pickupDate} - {booking.dropoffDate}</p>
                            <p className={`status ${booking.status.toLowerCase()}`}>{booking.status}</p>
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
};

export default BookingOverview;
