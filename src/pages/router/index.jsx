import { Route, Routes } from "react-router-dom";
import Dashboard from "../dashboard";
import Verifications from "../Verifications";

import LoginPage from "../Login";
import PaymentTransfer from "../PaymentTransfer";
import Users from "../UsersManagement";
export default function Router() {
  return (
    <Routes>
      <Route exact path="/" element={<Dashboard />} />
      <Route exact path="/dashboard" element={<Dashboard />} />
      <Route path="/verification" element={<Verifications />} />
      <Route path="/login" element={<LoginPage />} />
      <Route path="/payments" element={<PaymentTransfer />} />
      <Route path="/users" element={<Users />} />
    </Routes>
  );
}
