import React from 'react'
import AddProductBtn from '../../components/buttons/addProduct'
import SearchBarComponent from '../../components/searchBar'
import TabComponent from '../../components/buttons/tabComponent'
import VehicleTable from '../../components/productTable'

function ProductPage() {
    return (
        <div>
            <AddProductBtn />
            <h1>Vehicle</h1>
            <SearchBarComponent />
            <TabComponent />
            <VehicleTable />
        </div>
    )
}

export default ProductPage
