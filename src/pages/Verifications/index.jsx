import React, { useState, useEffect } from "react";
import "./styles.css";
import BlueUnderline from "../../assets/underlineBlue2.svg";

// Component
const ConfirmationModal = ({ show, onClose, onConfirm, currentUser }) => {
  return (
    <div className={`ver-modal ${show ? "show" : ""}`}>
      <div className="ver-modal-content">
        <h3 className="ver-detail-title">
          {currentUser.name} <span>({currentUser.role})</span>
        </h3>
        <div className="ver-modal-heading-container">
          <h4>Name:</h4>
          <p>{currentUser.name}</p>
        </div>
        <div className="ver-modal-heading-container">
          <h4>Email:</h4>
          <p>{currentUser.email}</p>
        </div>
        <div className="ver-modal-heading-container">
          <h4>Phone Number:</h4>
          <p>{currentUser.phone}</p>
        </div>
        <div className="ver-modal-heading-container">
          <h4>Bank Name:</h4>

          <p>{currentUser.bankName}</p>
        </div>
        <div className="ver-modal-heading-container">
          <h4>Account Number:</h4>
          <p>{currentUser.accountNumber}</p>
        </div>
        <div className="ver-modal-heading-container">
          <h4>Email:</h4>
          <p>{currentUser.email}</p>
        </div>
        <h4 className="ver-head-idcard">Id Card Image:</h4>
        <div className="ver-modal-img-container">
          <img
            className="ver-id-img"
            src={currentUser.idCardImage}
            alt="idCard Image"
          />
        </div>
        <div className="ver-modal-buttons">
          <button className="ver-modal-yes-btn" onClick={onClose}>
            Reject
          </button>
          <button className="ver-modal-no-btn" onClick={onConfirm}>
            Verify
          </button>
        </div>
      </div>
    </div>
  );
};

const Index = () => {
  const [users, setUsers] = useState([]);

  const [loading, setLoading] = useState(true); // Add loading state
  const [showModal, setShowModal] = useState(false); // State to manage modal visibility
  const [currentUser, setCurrentUser] = useState({});

  useEffect(() => {
    fetch("https://travelix-backend-v2.vercel.app/api/users")
      .then((response) => response.json())
      .then((data) => {
        setUsers(data);
        setLoading(false); // Set loading to false after data is fetched
      })
      .catch((error) => console.error("Error fetching users:", error));
  }, []);

  const serviceProviders = users.filter(
    (user) =>
      user.role === "carOwner" ||
      user.role === "hotelOwner" ||
      user.role === "tourOwner"
  );
  const handleVerify = () => {
    // Implement your delete user logic here
    // For demonstration, we'll just log a message
    console.log("Service Provider Verified");
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
          <div className="ver-heading-container">
            <h2 className="ver-main-heading">Service Providers Verification</h2>
            <img src={BlueUnderline} />
          </div>
          <table className="user-table">
            <thead>
              <tr>
                <th>Sr. No</th>
                <th>Image</th>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              {serviceProviders.map((user, index) => (
                <tr key={index}>
                  <td>{index + 1}</td>
                  <td>
                    <img
                      className="userM-userImg"
                      src={user.image}
                      alt="image"
                    />
                  </td>
                  <td>{user.name}</td>
                  <td>{user.email}</td>
                  <td>{user.role}</td>
                  <td>
                    {/* Button to trigger the modal */}
                    <button
                      className="ver-detail-btn"
                      onClick={() => {
                        setShowModal(true), setCurrentUser(user);
                      }}
                    >
                      View Details
                    </button>
                    <ConfirmationModal
                      show={showModal}
                      onClose={() => setShowModal(false)} // Function to close the modal
                      onConfirm={handleVerify} // Function to handle confirm action
                      currentUser={currentUser}
                    />
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
          {/* Render the modal component outside of the table */}
        </>
      )}
    </div>
  );
};

export default Index;
