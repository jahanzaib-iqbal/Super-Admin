import React from 'react';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faQuoteRight, faStar as faStarSolid } from '@fortawesome/free-solid-svg-icons';
import { faStar as faStarRegular } from '@fortawesome/free-regular-svg-icons';
import './style.css'; // Make sure to create a corresponding CSS file

// Dummy feedback data
const feedback = {
    clientPhoto: 'https://t4.ftcdn.net/jpg/03/64/21/11/360_F_364211147_1qgLVxv1Tcq0Ohz3FawUfrtONzz8nq3e.jpg', // Placeholder image URL
    clientName: 'Jane Doe',
    clientDesignation: 'Frequent Renter',
    rating: 4,
    comment: 'The vehicle was clean and well-maintained. Had a pleasant experience with the rental service. Would recommend!'
};
const FeedbackCard = () => {
    return (
        <div className="feedback-card">
            <div className="feedback-card-header">
                <FontAwesomeIcon icon={faQuoteRight} className="quote-icon" />
            </div>
            <div className="client-info">
                <div className="client-photo" style={{ backgroundImage: `url(${feedback.clientPhoto})` }} />
                <h3 className="client-name">{feedback.clientName}</h3>
                <p className="client-designation">{feedback.clientDesignation}</p>
                <div className="client-rating">
                    {[...Array(5)].map((_, index) => (
                        <FontAwesomeIcon
                            key={index}
                            icon={index < feedback.rating ? faStarSolid : faStarRegular}
                            className="star"
                        />
                    ))}
                </div>
            </div>
            <div className="feedback-content">
                <p>{feedback.comment}</p>
            </div>
        </div>
    );
};

export default FeedbackCard;
