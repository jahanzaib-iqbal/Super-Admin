import React from 'react';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faFrown, faMeh, faSmile, faStar, faExclamationCircle } from '@fortawesome/free-solid-svg-icons';
import './style.css'; // Make sure to create a corresponding CSS file

const feedbackData = [
    {
        id: 1,
        comment: "Loved the car and the service! Everything was perfect and I couldn't have asked for more.",
        rating: 5,
        sentiment: 'positive',
        urgent: false
    },
    // ... more feedback objects
];

const FeedbackSummary = () => {
    const getSentimentIcon = (sentiment) => {
        const icons = {
            positive: faSmile,
            neutral: faMeh,
            negative: faFrown
        };
        return icons[sentiment] || faMeh;
    };

    const renderStars = (rating) => {
        return [...Array(5)].map((_, index) => (
            <FontAwesomeIcon key={index} icon={faStar} className={`star ${index < rating ? 'filled' : ''}`} />
        ));
    };

    return (
        <div className="feedback-summary-container">
            {feedbackData.map(feedback => (
                <div key={feedback.id} className="feedback-card">
                    {feedback.urgent && <div className="urgent-flag"><FontAwesomeIcon icon={faExclamationCircle} /></div>}
                    <div className="feedback-sentiment">
                        <FontAwesomeIcon icon={getSentimentIcon(feedback.sentiment)} />
                    </div>
                    <div className="feedback-body">
                        <p className="feedback-comment">{feedback.comment.substring(0, 100)}...</p>
                        <div className="feedback-rating">{renderStars(feedback.rating)}</div>
                    </div>
                    <div className="feedback-actions">
                        <button className="details-button">More details</button>
                    </div>
                </div>
            ))}
        </div>
    );
};

export default FeedbackSummary;
