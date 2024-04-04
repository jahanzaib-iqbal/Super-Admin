import React, { useEffect, useRef } from 'react';
import Chart from 'chart.js/auto';

function BookingChart() {
    const chartRef = useRef(null);
    const chartInstanceRef = useRef(null);

    useEffect(() => {
        const ctx = chartRef.current.getContext('2d');

        // Destroy the previous chart instance if it exists
        if (chartInstanceRef.current) {
            chartInstanceRef.current.destroy();
        }

        const gradient = ctx.createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, 'rgba(255, 99, 132, 0.5)');
        gradient.addColorStop(1, 'rgba(255, 99, 132, 0.2)');

        // Create a new chart instance and store it in the ref
        chartInstanceRef.current = new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
                datasets: [{
                    label: 'Number of Bookings',
                    data: [50, 75, 150, 120],
                    fill: true,
                    backgroundColor: gradient,
                    borderColor: 'rgb(255, 99, 132)',
                    tension: 0.4,
                    pointBackgroundColor: 'white',
                    pointBorderColor: 'rgb(255, 99, 132)',
                    pointHoverRadius: 8,
                    pointHoverBackgroundColor: 'rgb(255, 99, 132)',
                    pointHoverBorderColor: 'white',
                    pointHoverBorderWidth: 2,
                    pointRadius: 6,
                    pointHitRadius: 10,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                    },
                },
            },
        });

        // Cleanup function to destroy chart instance when component unmounts
        return () => {
            if (chartInstanceRef.current) {
                chartInstanceRef.current.destroy();
            }
        };
    }, []); // Empty dependency array ensures this effect runs only once on mount

    return (
        <div className='container' style={{ height: '75%', width: '100%' }} >
            <h4 style={{ color: 'black', fontSize: 26 }}>Monthly Views</h4>
            <canvas ref={chartRef}></canvas>
        </div>
    );
}

export default BookingChart;
