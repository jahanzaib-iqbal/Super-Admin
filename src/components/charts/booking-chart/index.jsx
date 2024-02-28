import React from 'react';
import { Bar } from 'react-chartjs-2';
import './style.css';
import { Chart, CategoryScale, BarElement, LinearScale, Title, Tooltip, Legend } from 'chart.js';


Chart.register(CategoryScale, BarElement, LinearScale, Title, Tooltip, Legend);


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
        y: {
            ticks: {
                beginAtZero: true,
            },
        },
    },
};
function BookingChart() {
    return (
        <div className="booking-trends">
            <h3>Booking Trends</h3>
            <div className="trend-chart">
                <Bar data={bookingsTrendData} options={chartOptions} />
            </div>
        </div>
    )
}

export default BookingChart
