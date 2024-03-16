import React, { useState } from 'react';

import './style.css';
import VehicleEditModal from '../Modal/EditVehicleModal';

const VehicleTable = () => {
    const [vehicles, setVehicles] = useState([
        {
            id: 1,
            name: "Toyota Corolla",
            description: "Sedan, 4 seats, 4 doors",
            location: "San Francisco",
            status: "Available"
        },
        {
            id: 3,
            name: "Toyota Corolla",
            description: "Sedan, 4 seats, 4 doors",
            location: "San Francisco",
            status: "Available"
        },
        {
            id: 2,
            name: "Toyotta",
            description: "Sedan, 4 seats, 4 doors",
            location: "San Francisco",
            status: "Available"
        },

    ]);

    const [editVehicle, setEditVehicle] = useState(null); // State to manage currently edited vehicle

    // Function to update vehicle details in the state
    const handleSaveChanges = (updatedVehicle) => {
        const updatedVehicles = vehicles.map(vehicle => {
            if (vehicle.id === updatedVehicle.id) {
                return updatedVehicle;
            }
            return vehicle;
        });
        setVehicles(updatedVehicles);
        setEditVehicle(null); // Close the modal after saving changes
    };

    return (
        <div>
            <table className="vehicle-table">
                <thead>
                    <tr>
                        <th>Id</th>
                        <th>Vehicle</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    {vehicles.map(vehicle => (
                        <tr key={vehicle.id}>
                            <td>{vehicle.id}</td>
                            <td>
                                <div className="vehicle-info">
                                    <div className="vehicle-name">{vehicle.name} </div>
                                    <div className="vehicle-description">{vehicle.description}</div>
                                </div>
                            </td>
                            <td>{vehicle.location}</td>
                            <td>{vehicle.status}</td>
                            <td>
                                <button onClick={() => setEditVehicle(vehicle)}>Edit</button>
                                <button onClick={() => setEditVehicle(vehicle)}>Delete</button>
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
            {editVehicle && (
                <VehicleEditModal
                    isOpen={!!editVehicle}
                    onClose={() => setEditVehicle(null)}
                    vehicle={editVehicle}
                    onSave={handleSaveChanges}
                />
            )}
        </div>
    );
};

export default VehicleTable;
