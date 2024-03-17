// SearchBarComponent.jsx
import React from 'react';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faSearch } from '@fortawesome/free-solid-svg-icons';
import './SearchBarComponent.css';

const SearchBarComponent = ({ onSearch }) => {
    return (
        <div className="search-bar-container">
            <FontAwesomeIcon icon={faSearch} className="search-icon" />
            <input
                type="text"
                placeholder="Search vehicles"
                className="search-input"
                onChange={(e) => onSearch(e.target.value)}
            />
        </div>
    );
};

export default SearchBarComponent;
