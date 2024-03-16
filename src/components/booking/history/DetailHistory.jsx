import React from 'react';
import './DetailedHistoryView.css'; // Make sure to create and link the CSS file

// Dummy data for demonstration
const detailedBookingsData = [
    { id: 'BK001', customerName: 'Jane Doe', vehicleModel: 'Toyota Corolla', dateRange: '2024-03-10 to 2024-03-15', status: 'Confirmed' },
    { id: 'BK002', customerName: 'John Smith', vehicleModel: 'Honda Civic', dateRange: '2024-03-12 to 2024-03-16', status: 'Pending' },
    // Add more booking details as needed
];

const DetailedBookings = () => {
    return (
        <div className="detailed-bookings-container">
            <h2>Detailed Bookings</h2>
            <div className="table-responsive">
                <table className="bookings-table">
                    <thead>
                        <tr>
                            <th>Booking ID</th>
                            <th>Customer Name</th>
                            <th>Vehicle Model</th>
                            <th>Date Range</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        {detailedBookingsData.map((booking) => (
                            <tr key={booking.id}>
                                <td>{booking.id}</td>
                                <td>{booking.customerName}</td>
                                <td>{booking.vehicleModel}</td>
                                <td>{booking.dateRange}</td>
                                <td className={`status ${booking.status.toLowerCase()}`}>{booking.status}</td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
};

export default DetailedBookings;
