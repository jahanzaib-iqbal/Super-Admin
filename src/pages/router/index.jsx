import { Route, Routes } from "react-router-dom";
import Dashboard from "../dashboard";
import Bookings from '../bookings'
import DetailedHistoryView from "../../components/booking/history/DetailHistory";
import VehicleManagement from "../product";
import VehicleManagementPage from "../product";
import ProductPage from "../product";

export default function Router() {
  return (
    <Routes>
      <Route exact path="/dashboard" element={<Dashboard />} />
      <Route path="/bookings" element={<Bookings />} />
      <Route path="/detailedBookings" element={<DetailedHistoryView />} />
      <Route path="/product" element={<ProductPage />} />
      {/* <Route path="/vehicles" element={<Project />} />
      <Route path="/customers" element={<Resume />} />
      <Route path="/feedback" element={<Blog />} />
      <Route path="/settings" element={<Blog />} /> */}
    </Routes>
  );
}
