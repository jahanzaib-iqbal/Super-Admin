import React from 'react'
import './style.css'

// Dummy data for bookings
const bookingsData = [
    { id: 'B001', customerName: 'John Doe', carModel: 'Toyota Corolla', date: '2024-02-25', status: 'confirmed' },
    // ... add more bookings data here
];
function RecentBookingCard() {
    const recentBookings = bookingsData;
    return (
        <div className="recent-bookings">
            <h3>Recent Bookings</h3>
            <div className="booking-cards">
                {recentBookings.map((booking) => (
                    <div key={booking.id} className="booking-card">
                        <div className="card-header">
                            <span className="card-highlight">Booking ID: {booking.id}</span>
                        </div>
                        <div className="card-content">
                            <p>Customer: <span>{booking.customerName}</span></p>
                            <p>Car Model: <span>{booking.carModel}</span></p>
                            <p>Date: <span>{booking.date}</span></p>
                            <p>Status: <span className={`status ${booking.status.toLowerCase()}`}>{booking.status}</span></p>
                        </div>
                    </div>
                ))}
            </div>
        </div>
    )
}

export default RecentBookingCard
