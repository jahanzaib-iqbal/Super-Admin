import React, { useState } from 'react';
import './AddVehicleModal.css'; // Make sure this CSS file is properly imported

const AddVehicleModal = ({ isOpen, onClose, onAddVehicle }) => {
    const [vehicleData, setVehicleData] = useState({
        id: 4,
        make: '',
        model: '',
        year: '',
        vin: '',
        registrationNumber: '',
        location: '',
        status: '',
        features: '',
        rentPricing: '',
        imageUrl: '',
        statusType: 'pending'
    });

    const handleInputChange = (event) => {
        const { name, value } = event.target;
        setVehicleData(prev => ({ ...prev, [name]: value }));
    };

    const handleImageChange = (event) => {
        // Logic to handle image file will go here
    };

    const handleSubmit = (event) => {
        event.preventDefault();
        onAddVehicle(vehicleData);
        onClose();
    };

    return (
        <div className={`avm-overlay ${isOpen ? 'avm-overlay--visible' : ''}`} onClick={onClose}>
            <div className="avm-content" onClick={(event) => event.stopPropagation()}>
                <div className="avm-header">
                    <h5 className="avm-title">Add New Vehicle</h5>
                    <button type="button" className="avm-close" onClick={onClose}>&times;</button>
                </div>
                <div className="avm-body">
                    <form onSubmit={handleSubmit} className="avm-form">
                        <label htmlFor="make">Make</label>
                        <input id="make" name="make" type="text" value={vehicleData.make} onChange={handleInputChange} required />

                        <label htmlFor="model">Model</label>
                        <input id="model" name="model" type="text" value={vehicleData.model} onChange={handleInputChange} required />

                        <label htmlFor="year">Year</label>
                        <input id="year" name="year" type="number" value={vehicleData.year} onChange={handleInputChange} required />

                        <label htmlFor="vin">VIN</label>
                        <input id="vin" name="vin" type="text" value={vehicleData.vin} onChange={handleInputChange} />

                        <label htmlFor="registrationNumber">Registration Number</label>
                        <input id="registrationNumber" name="registrationNumber" type="text" value={vehicleData.registrationNumber} onChange={handleInputChange} />

                        <label htmlFor="location">Location</label>
                        <input id="location" name="location" type="text" value={vehicleData.location} onChange={handleInputChange} required />

                        <label htmlFor="status">Status</label>
                        <select id="status" name="status" value={vehicleData.status} onChange={handleInputChange} required>
                            <option value="">Select Status</option>
                            <option value="Available">Available</option>
                            <option value="Unavailable">Unavailable</option>
                        </select>

                        <label htmlFor="features">Features (comma-separated)</label>
                        <textarea id="features" name="features" value={vehicleData.features} onChange={handleInputChange} required />

                        <label htmlFor="rentPricing">Rent Pricing ($ per day)</label>
                        <input id="rentPricing" name="rentPricing" type="number" value={vehicleData.rentPricing} onChange={handleInputChange} required />

                        <label htmlFor="imageUpload">Vehicle Image</label>
                        <input id="imageUpload" name="imageUpload" type="file" onChange={handleImageChange} />

                        <div className="avm-footer">
                            <button type="submit" className="avm-btn avm-btn--primary">Add Vehicle</button>
                            <button type="button" className="avm-btn avm-btn--secondary" onClick={onClose}>Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    );
};

export default AddVehicleModal;
