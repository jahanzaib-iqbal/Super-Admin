import React, { useState } from 'react';
import { confirmAlert } from 'react-confirm-alert';
import 'react-confirm-alert/src/react-confirm-alert.css';
import './style.css';
import VehicleEditModal from '../Modal/EditVehicleModal';

const VehicleTable = ({ vehicles, setVehicles, searchTerm, selectedTab }) => {
    const [editVehicle, setEditVehicle] = useState(null);

    const filteredVehicles = vehicles
        .filter(vehicle => selectedTab === 'all' || vehicle.statusType === selectedTab)
        .filter(vehicle =>
            vehicle.make.toLowerCase().includes(searchTerm.toLowerCase()) ||
            vehicle.model.toLowerCase().includes(searchTerm.toLowerCase())
        );

    const handleSaveChanges = (updatedVehicle) => {
        const updatedVehicles = vehicles.map(vehicle =>
            vehicle.id === updatedVehicle.id ? updatedVehicle : vehicle
        );
        setVehicles(updatedVehicles);
        setEditVehicle(null);
    };

    const handleDeleteVehicle = (vehicleId) => {
        confirmAlert({
            title: 'Confirm to delete',
            message: 'Are you sure to do this?',
            buttons: [
                {
                    label: 'Yes',
                    onClick: () => setVehicles(vehicles.filter(vehicle => vehicle.id !== vehicleId))
                },
                {
                    label: 'No'
                }
            ]
        });
    };

    return (
        <div className='table-div'>
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
                    {
                        filteredVehicles.map(vehicle => (
                            <tr key={vehicle.id}>
                                <td>{vehicle.id}</td>
                                <td>
                                    <div className="vehicle-info">
                                        <div className="vehicle-image">
                                            <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTzYQ7HSdHIcJMoATh7sfXCHwl-Uf6CjHIpl0elOXzEjg&s" alt="" />
                                        </div>
                                        <div id="flex-div">
                                            <div className="vehicle-name">
                                                <span>{vehicle.make}</span>
                                                <span>{vehicle.model}</span>
                                                <span>{vehicle.year}</span>
                                            </div>
                                            <div className="vehicle-description">{vehicle.features}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>{vehicle.location}</td>
                                <td>{vehicle.status}</td>
                                <td>

                                    <button className="product-edit-del-btn" onClick={() => setEditVehicle(vehicle)}>Edit</button>


                                    <button className="product-edit-del-btn" onClick={() => handleDeleteVehicle(vehicle.id)}>Delete</button>


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
