import React from 'react'
import BookingOverview from '../../components/booking/booking-overview'
import CancelBooking from '../../components/booking/booking-cancel'
import HistoricalDataAccess from '../../components/booking/history'

function Bookings() {
    return (
        <div>
            <BookingOverview />
            <CancelBooking />
            <HistoricalDataAccess />
        </div>
    )
}

export default Bookings
