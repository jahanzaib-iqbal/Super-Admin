import React from 'react';
import { Bar } from 'react-chartjs-2';
import './style.css';
import { Chart, CategoryScale, BarElement, LinearScale, Title, Tooltip, Legend } from 'chart.js';

Chart.register(CategoryScale, BarElement, LinearScale, Title, Tooltip, Legend);

// Dummy data for bookings
const bookingsData = [
    { id: 'B001', customerName: 'John Doe', carModel: 'Toyota Corolla', date: '2024-02-25', status: 'completed' },
    // ... add more bookings data here
];

// Dummy data for upcoming bookings
const upcomingBookingsData = [
    { id: 'B002', customerName: 'Jane Smith', carModel: 'Honda Civic', date: '2024-03-01', status: 'upcoming' },
    // ... add more upcoming bookings data here
];

// Dummy data for bookings trend
const bookingsTrendData = {
    labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
    datasets: [
        {
            label: 'Number of Bookings',
            data: [50, 75, 150, 120],
            backgroundColor: 'rgba(54, 162, 235, 0.2)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 1,
        },
    ],
};

// Options for the chart
const chartOptions = {
    scales: {
        y: { // No longer an array, directly an object
            ticks: {
                beginAtZero: true,
            },
        },
    },
};


const BookingOverview = () => {
    const recentBookings = bookingsData.slice(-5);
    const upcomingBookings = upcomingBookingsData;

    return (
        <div className="booking-overview">
            <h2>Booking Overview</h2>

            {/* <div className="recent-bookings">
                <h3>Recent Bookings</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Booking ID</th>
                            <th>Customer Name</th>
                            <th>Car Model</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        {recentBookings.map((booking) => (
                            <tr key={booking.id}>
                                <td>{booking.id}</td>
                                <td>{booking.customerName}</td>
                                <td>{booking.carModel}</td>
                                <td>{booking.date}</td>
                                <td>{booking.status}</td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div> */}
            <div className="recent-bookings">
                <h3>Recent Bookings</h3>
                <div className="table-responsive">
                    <table className="table-premium">
                        <thead>
                            <tr>
                                <th>Booking ID</th>
                                <th>Customer Name</th>
                                <th>Car Model</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            {recentBookings.map((booking) => (
                                <tr key={booking.id}>
                                    <td>{booking.id}</td>
                                    <td>{booking.customerName}</td>
                                    <td>{booking.carModel}</td>
                                    <td>{booking.date}</td>
                                    <td className={`status ${booking.status.toLowerCase()}`}>{booking.status}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>

            <div className="booking-trends">
                <h3>Booking Trends</h3>
                <div className="trend-chart">
                    <Bar data={bookingsTrendData} options={chartOptions} />
                </div>
            </div>

            <div className="quick-stats">
                <h3>Quick Statistics</h3>
                <div className="stats-container">
                    <div className="stat">
                        <p className="stat-value">123</p>
                        <p className="stat-name">Total Bookings</p>
                    </div>
                    <div className="stat">
                        <p className="stat-value">98%</p>
                        <p className="stat-name">Customer Satisfaction</p>
                    </div>
                    {/* Add more statistics as needed */}
                </div>
            </div>
        </div>
    );
};

export default BookingOverview;
