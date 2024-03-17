import React, { useState } from 'react';

import AddProductBtn from '../../components/buttons/addProduct';
import SearchBarComponent from '../../components/searchBar';
import TabComponent from '../../components/buttons/tabComponent';
import VehicleTable from '../../components/productTable';

function ProductPage() {
    const [selectedTab, setSelectedTab] = useState('all');
    const [searchTerm, setSearchTerm] = useState('');

    return (
        <div>
            <AddProductBtn />
            <h1>Vehicle</h1>
            <SearchBarComponent onSearch={(term) => setSearchTerm(term)} />
            <TabComponent
                activeTab={selectedTab}
                onChange={setSelectedTab}
            />
            <VehicleTable selectedTab={selectedTab} searchTerm={searchTerm} />
        </div>
    );
}

export default ProductPage;
