import React from 'react';
import './style.css'; // Make sure to create a corresponding CSS file

const bookingsData = [
    {
        id: 'BK001',
        customerName: 'John Doe',
        bookingDate: '2024-03-01',
        status: 'Confirmed'
    },
    {
        id: 'BK001',
        customerName: 'John Doe',
        bookingDate: '2024-03-01',
        status: 'Confirmed'
    },
    {
        id: 'BK001',
        customerName: 'John Doe',
        bookingDate: '2024-03-01',
        status: 'Confirmed'
    },
    {
        id: 'BK001',
        customerName: 'John Doe',
        bookingDate: '2024-03-01',
        status: 'Confirmed'
    },
    // Add more bookings as needed
];

const RecentBookings = () => {
    return (
        <div className="recent-bookings-container">
          
            <table className="bookings-table">
                <thead>
                    <tr>
                        <th>Booking ID</th>
                        <th>Customer Name</th>
                        <th>Booking Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    {bookingsData.map((booking) => (
                        <tr key={booking.id}>
                            <td>{booking.id}</td>
                            <td>{booking.customerName}</td>
                            <td>{booking.bookingDate}</td>
                            <td>{booking.status}</td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
};

export default RecentBookings;
