import React from 'react'
import BookingChart from '../../components/charts/booking-chart'
import RecentBookingCard from '../../components/card/recent-booking'
import './style.css'
import DashboardCards from '../../components/card/dashboard-cards'
function Dashboard() {
    return (
        <div className="booking-overview">

            <DashboardCards />
            <div className="booking-container">
                <BookingChart />
                <RecentBookingCard />
            </div>

        </div>
    )
}

export default Dashboard
