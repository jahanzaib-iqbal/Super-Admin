import React, { useState, useEffect } from "react";
import "./styles.css";

// Component
const ConfirmationModal = ({ show, onClose, onConfirm }) => {
  return (
    <div className={`modal ${show ? "show" : ""}`}>
      <div className="modal-content">
        <p>Are you sure you want to Block the user?</p>
        <div className="modal-buttons">
          <button className="modal-no-btn" onClick={onClose}>
            No
          </button>
          <button className="modal-yes-btn" onClick={onConfirm}>
            Yes
          </button>
        </div>
      </div>
    </div>
  );
};

const Index = () => {
  const [users, setUsers] = useState([]);
  const [searchTerm, setSearchTerm] = useState("");
  const [selectedRole, setSelectedRole] = useState("All");
  const [loading, setLoading] = useState(true); // Add loading state
  const [showModal, setShowModal] = useState(false); // State to manage modal visibility

  useEffect(() => {
    fetch("https://travelix-backend-v2.vercel.app/api/users")
      .then((response) => response.json())
      .then((data) => {
        setUsers(data);
        setLoading(false); // Set loading to false after data is fetched
      })
      .catch((error) => console.error("Error fetching users:", error));
  }, []);

  const filteredUsers = users.filter((user) => {
    return (
      user.name.toLowerCase().includes(searchTerm.toLowerCase()) &&
      (selectedRole === "All" || user.role === selectedRole)
    );
  });

  const handleDeleteUser = () => {
    // Implement your delete user logic here
    // For demonstration, we'll just log a message
    console.log("User deleted");
    // Close the modal after action is performed
    setShowModal(false);
  };

  return (
    <div className="user-management">
      {/* Conditional rendering of loader */}
      {loading ? (
        <div className="loader">Loading...</div>
      ) : (
        <>
          <div className="search-container">
            <input
              className="booking-search-input"
              type="text"
              placeholder="Search by name..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
            />
            <select
              className="booking-search-select"
              value={selectedRole}
              onChange={(e) => setSelectedRole(e.target.value)}
            >
              <option value="All">All Users</option>
              <option value="user">Customers</option>
              <option value="hotelOwner">Hotel Owner</option>
              <option value="tourOwner">Tour Owner</option>
              <option value="carOwner">Car Owner</option>
            </select>
          </div>
          <table className="user-table">
            <thead>
              <tr>
                <th>Sr. No</th>
                <th>Image</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Role</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              {filteredUsers.map((user, index) => (
                <tr key={index}>
                  <td>{index + 1}</td>
                  <td>
                    <img className="userM-userImg" src={user.image} alt="image" />
                  </td>
                  <td>{user.name}</td>
                  <td>{user.email}</td>
                  <td>{user.phone}</td>
                  <td>{user.role}</td>
                  <td>
                    {/* Button to trigger the modal */}
                    <button
                      className="payment-block-btn"
                      onClick={() => setShowModal(true)}
                    >
                      Block User
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
          {/* Render the modal component outside of the table */}
          <ConfirmationModal
            show={showModal}
            onClose={() => setShowModal(false)} // Function to close the modal
            onConfirm={handleDeleteUser} // Function to handle confirm action
          />
        </>
      )}
    </div>
  );
};

export default Index;
