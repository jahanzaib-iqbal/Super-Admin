import React, { useState } from 'react'
import BookingChart from '../../components/charts/booking-chart'
import RecentBookingCard from '../../components/card/recent-booking'
import './style.css'
import DashboardCards from '../../components/card/dashboard-cards'
import RevenueStatistics from '../../components/charts/revenue-chart'
import SwapBtn from '../../components/buttons/swap-buttons'
import FeedbackSummary from '../../components/feedback-summary'
import FeedbackCard from '../../components/card/feedback-card'
import ProductAlerts from '../../components/Product'
import CustomerSupportQueries from '../../components/customer-support-quries'
import SystemNotifications from '../../components/notification'
import RecentBookings from '../../components/card/recent-booking'
function Dashboard() {
    // use the time frame for the revenue card
    const [timeFrame, SetTimeFrame] = useState('Daily');
    const changeTimeFrame = (timeFrame) => {
        SetTimeFrame(timeFrame);
    }
    const revenueData = [
        {
            title: 'Daily Revenue',
            total: 5000,
            change: 5, // Percentage increase or decrease
            currency: '$',
            chartData: {
                labels: ['Compact', 'Sedan', 'SUV', 'Luxury'],
                datasets: [{
                    data: [1500, 2000, 1000, 500], // Sample data representing revenue per car type
                    backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#E7E9ED'],
                    hoverBackgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#E7E9ED']
                }]
            }
        },
        {
            title: 'Weekly Revenue',
            total: 6000,
            change: 5, // Percentage increase or decrease
            currency: '$',
            chartData: {
                labels: ['Compact', 'Sedan', 'SUV', 'Luxury'],
                datasets: [{
                    data: [4000, 1200, 500, 300],
                    backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#E7E9ED'],
                    hoverBackgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#E7E9ED']
                }]
            }
        },
        {
            title: 'Monthly Revenue',
            total: 6000,
            change: 5, // Percentage increase or decrease
            currency: '$',
            chartData: {
                labels: ['Compact', 'Sedan', 'SUV', 'Luxury'],
                datasets: [{
                    data: [4000, 1200, 500, 300],
                    backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#E7E9ED'],
                    hoverBackgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#E7E9ED']
                }]
            }
        },

    ];
    return (
        <div className="booking-overview">

            <DashboardCards />
            <div className="booking-container">
                <BookingChart />
            </div>

            <div className="card-table-container">

                <div className="revenue-container">
                    <SwapBtn onChangeTimeFrame={changeTimeFrame} />
                    <RevenueStatistics revenueData={revenueData.filter((data) => data.title.includes(timeFrame))} />
                </div>
                <div className="recent-table">
                    <h2>Recent Bookings</h2>
                    <RecentBookings />
                </div>
            </div>

            {/* <RecentBookingCard />
            <FeedbackCard />
            <ProductAlerts />
            <CustomerSupportQueries />
            <SystemNotifications /> */}
        </div>
    )
}

export default Dashboard
