import React, { useState } from 'react'
import './style.css'
function SwapBtn({ onChangeTimeFrame }) {
    const [active, SetActive] = useState('Daily');
    const changeActiveBtn = (buttonName) => {
        onChangeTimeFrame(buttonName)
        SetActive(buttonName)
    }
    return (
        <div>
            <div className="swap-btn-container">
                <button className={`item ${active === 'Daily' ? 'active' : ''}`} onClick={() => changeActiveBtn('Daily')}>
                    <span>Daily</span>
                </button>
                <button className={`item ${active === 'Weekly' ? 'active' : ''}`} onClick={() => changeActiveBtn('Weekly')}>
                    <span>Weekly</span>
                </button>
                <button className={`item ${active === 'Yearly' ? 'active' : ''}`} onClick={() => changeActiveBtn('Monthly')}>
                    <span>Monthly</span>
                </button>
            </div>
        </div>
    )
}

export default SwapBtn
