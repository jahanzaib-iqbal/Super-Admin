import React from 'react';
import { Doughnut } from 'react-chartjs-2';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faDollarSign } from '@fortawesome/free-solid-svg-icons';
import './style.css'; // Make sure to create a corresponding CSS file


const RevenueStatistics = ({ revenueData }) => {
    const options = {
        maintainAspectRatio: false,
        responsive: true,
        plugins: {
            legend: {
                display: true,
                position: 'bottom'
            }
        }
    };

    return (
        <div className="revenue-statistics">

            {revenueData.map((data, index) => (
                <div key={index} className="revenue-card">
                    <div className="revenue-card-header">
                        <FontAwesomeIcon icon={faDollarSign} /> {data.title}
                    </div>
                    <div className="revenue-chart-container">
                        <Doughnut data={data.chartData} options={options} />
                    </div>
                    <div className="revenue-footer">
                        <div className="total-revenue">Total: {data.currency}{data.total.toLocaleString()}</div>
                        <div className={`revenue-change ${data.change >= 0 ? 'positive' : 'negative'}`}>
                            {data.change.toFixed(2)}% {data.change >= 0 ? '↑' : '↓'}
                        </div>
                    </div>
                </div>
            ))}
        </div>
    );
};

export default RevenueStatistics;
