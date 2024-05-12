import React from "react";
import { useLocation } from "react-router-dom";
import Sidebar from "../components/sidebar";
import "./style.css";

export default function Layout({ children }) {
  const location = useLocation();

  // Check if the current location is "/login"
  const isLoginPage = location.pathname === "/login";

  return (
    <div className="app-container">
      {!isLoginPage && ( // Render Sidebar only if not on the login page
        <div className="header">
          <Sidebar />
        </div>
      )}
      <main>{children}</main>
      {/* <Footer /> */}
    </div>
  );
}
