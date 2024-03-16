import React, { useState } from "react";
Sidebar

import "./style.css";
import Sidebar from "../components/sidebar";

export default function Layout({ children }) {

  return (
    <div className="app-container">
      <div className="header">
        <Sidebar />
      </div>
      <main>{children}</main>
      {/* <Footer /> */}
    </div>
  );
}
