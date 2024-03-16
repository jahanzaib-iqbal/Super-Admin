import React from 'react';
import './style.css'; // Ensure this is correctly linked
import { Link } from 'react-router-dom';
// Sample Booking History Data
const bookingHistoryData = [
    { date: '2024-02-01', action: 'Booking created', details: 'Toyota Corolla for 2024-03-10 to 2024-03-15', status: 'Confirmed' },
    { date: '2024-02-10', action: 'Modified booking', details: 'Changed vehicle to Honda Civic', status: 'Modified' },
    // Add more history entries as needed
];

// Sample Customer Rental History Data
const customerRentalHistoryData = [
    { bookingId: 'BK001', vehicleModel: 'Toyota Corolla', dateRange: '2023-12-01 to 2023-12-05', status: 'Completed' },
    { bookingId: 'BK002', vehicleModel: 'Honda Civic', dateRange: '2024-01-15 to 2024-01-20', status: 'Completed' },
    // Add more rental entries as needed
];

const HistoricalDataAccess = () => {
    return (
        <div className="historical-container">
            <div className="section">
                <h2 className="section-title">Booking History</h2>
                <div className="cards-container">
                    {bookingHistoryData.map((entry, index) => (
                        <div key={index} className="card">
                            <h3>{entry.date}</h3>
                            <p><strong>Action:</strong> {entry.action}</p>
                            <p><strong>Details:</strong> {entry.details}</p>
                            <p className={`status ${entry.status.toLowerCase()}`}>{entry.status}</p>
                        </div>
                    ))}
                </div>
                <Link to="/detailedbookings" className="view-all" onClick={() => handleViewAll('BookingHistory')}>View All Bookings</Link>
            </div>
            <div className="section">
                <h2 className="section-title">Customer Rental History</h2>
                <div className="cards-container">
                    {customerRentalHistoryData.map((entry, index) => (
                        <div key={index} className="card">
                            <h3>Booking ID: {entry.bookingId}</h3>
                            <p><strong>Vehicle:</strong> {entry.vehicleModel}</p>
                            <p><strong>Date Range:</strong> {entry.dateRange}</p>
                            <p className={`status ${entry.status.toLowerCase()}`}>{entry.status}</p>
                        </div>
                    ))}
                </div>
                <button className="view-all" onClick={() => handleViewAll('CustomerRentalHistory')}>View All Rentals</button>
            </div>
        </div>
    );
};

export default HistoricalDataAccess;
