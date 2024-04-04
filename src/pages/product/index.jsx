import React, { useState } from 'react';

import AddProductBtn from '../../components/buttons/addProduct';
import SearchBarComponent from '../../components/searchBar';
import TabComponent from '../../components/buttons/tabComponent';
import VehicleTable from '../../components/productTable';
import AddVehicleModal from '../../components/Modal/addVehicleModal'; // Adjust the import path as necessary

function ProductPage() {
    const [selectedTab, setSelectedTab] = useState('all');
    const [searchTerm, setSearchTerm] = useState('');
    const [showAddModal, setShowAddModal] = useState(false);
    const [vehicles, setVehicles] = useState([
        {
            id: 1,
            make: 'Honda',
            model: 'Camry',
            year: '2020',
            vin: '4T1BF1FK5GU190221',
            registration: '7XYZ123',
            location: "San Francisco",
            status: "Available",
            features: 'GPS, Air Conditioning, Bluetooth, Backup Camera',
            statusType: 'pending'
        },
        {
            id: 2,
            make: 'Toyota',
            model: 'Camry',
            year: '2020',
            vin: '4T1BF1FK5GU190221',
            registration: '7XYZ123',
            location: "San Francisco",
            status: "Available",
            features: 'GPS, Air Conditioning, Bluetooth, Backup Camera',
            statusType: 'pending'
        },
        {
            id: 3,
            make: 'Toyota',
            model: 'Camry',
            year: '2020',
            vin: '4T1BF1FK5GU190221',
            registration: '7XYZ123',
            location: "San Francisco",
            status: "Available",
            features: 'GPS, Air Conditioning, Bluetooth, Backup Camera',
            statusType: 'archived'
        },
    ]);

    // Function to handle adding a new vehicle
    const handleAddVehicle = (newVehicle) => {
        // Here you might want to send newVehicle to your backend
        // For now, we'll just add it to our local state
        setVehicles(prevVehicles => [...prevVehicles, newVehicle]);
        setShowAddModal(false); // Close the modal after adding
    };

    return (
        <div>
            <button className="add-vehicle-button" onClick={() => setShowAddModal(true)}>
                Add Vehicle
            </button>
            <h1>Vehicle</h1>
            <SearchBarComponent onSearch={(term) => setSearchTerm(term)} />
            <TabComponent activeTab={selectedTab} onChange={setSelectedTab} />
            <VehicleTable selectedTab={selectedTab} searchTerm={searchTerm} vehicles={vehicles} setVehicles={setVehicles} />
            <AddVehicleModal
                isOpen={showAddModal}
                onClose={() => setShowAddModal(false)}
                onAddVehicle={handleAddVehicle}
            />
        </div>
    );
}

export default ProductPage;