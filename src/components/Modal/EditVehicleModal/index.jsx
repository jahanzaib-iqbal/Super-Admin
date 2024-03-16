import React, { useState, useEffect } from 'react';
import './VehicleEditModal.css'; // Ensure this CSS file is created and linked

const VehicleEditModal = ({ isOpen, onClose, vehicle, onSave }) => {
    const [formData, setFormData] = useState({
        make: '',
        model: '',
        year: '',
        vin: '',
        registration: '',
        location: '',
        status: 'Available',
        features: ''
    });

    useEffect(() => {
        if (vehicle) setFormData(vehicle);
    }, [vehicle]);

    const handleChange = (e) => {
        const { name, value } = e.target;
        setFormData(prev => ({ ...prev, [name]: value }));
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        onSave(formData);
    };

    if (!isOpen) return null;

    return (
        <div className="modal-overlay" onClick={onClose}>
            <div className="modal-content" onClick={e => e.stopPropagation()}>
                <form onSubmit={handleSubmit} className="edit-form">
                    <h2>Edit Vehicle</h2>
                    <label htmlFor="make">Make:</label>
                    <input id="make" name="make" value={formData.make} onChange={handleChange} required />

                    <label htmlFor="model">Model:</label>
                    <input id="model" name="model" value={formData.model} onChange={handleChange} required />

                    <label htmlFor="year">Year:</label>
                    <input id="year" name="year" type="number" value={formData.year} onChange={handleChange} required />

                    <label htmlFor="vin">VIN:</label>
                    <input id="vin" name="vin" value={formData.vin} onChange={handleChange} />

                    <label htmlFor="registration">Registration:</label>
                    <input id="registration" name="registration" value={formData.registration} onChange={handleChange} />

                    <label htmlFor="location">Location:</label>
                    <input id="location" name="location" value={formData.location} onChange={handleChange} required />

                    <label htmlFor="status">Status:</label>
                    <select id="status" name="status" value={formData.status} onChange={handleChange}>
                        <option value="Available">Available</option>
                        <option value="Unavailable">Unavailable</option>
                    </select>

                    <label htmlFor="features">Features:</label>
                    <input id="features" name="features" value={formData.features} onChange={handleChange} />

                    <div className="form-actions">
                        <button type="submit" className="save-btn">Save Changes</button>
                        <button type="button" className="cancel-btn" onClick={onClose}>Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    );
};

export default VehicleEditModal;
