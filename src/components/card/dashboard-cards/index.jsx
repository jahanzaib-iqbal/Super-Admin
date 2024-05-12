import React, { useEffect, useState } from "react";
import { FontAwesomeIcon } from "@fortawesome/react-fontawesome";
import {
  faCar,
  faUser,
  faDollarSign,
  faThumbsUp,
  faCalendarCheck,
} from "@fortawesome/free-solid-svg-icons";
import "./style.css";

const DashboardCards = () => {
  const [users, setUsers] = useState([]);
  const [serviceProviders, setServiceProviders] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetch("https://travelix-backend-v2.vercel.app/api/users")
      .then((response) => response.json())
      .then((data) => {
        setUsers(data);
        setLoading(false); // Set loading to false after data is fetched
      })
      .catch((error) => console.error("Error fetching users:", error));
  }, []);

  useEffect(() => {
    setServiceProviders(
      users.filter(
        (user) =>
          user.role === "carOwner" ||
          user.role === "hotelOwner" ||
          user.role === "tourOwner"
      )
    );
  }, [users]);

  const stats = [
    {
      id: 1,
      title: "Total Users",
      value: users.length,
      icon: faUser,
    },
    {
        id: 2,
        title: "Service Providers",
        value: serviceProviders.length,
        icon: faThumbsUp,
      },
    {
      id: 3,
      title: "Active Rentals",
      value: "85",
      icon: faCalendarCheck,
    },

    {
      id: 4,
      title: "Revenue",
      value: "$24K",
      icon: faDollarSign,
    },
  ];

  return (
    <div className="dashboard-cards">
      {loading ? (
        <div className="loader">Loading...</div>
      ) : (
        stats.map((stat) => (
          <div key={stat.id} className="dashboard-card">
            <FontAwesomeIcon icon={stat.icon} className="card-icon" />
            <div className="card-title">{stat.title}</div>
            <div className="card-value">{stat.value}</div>
          </div>
        ))
      )}
    </div>
  );
};

export default DashboardCards;
