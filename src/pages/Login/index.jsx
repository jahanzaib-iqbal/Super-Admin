import React, { useState } from "react";
import "./styles.css";
import loginImage from "../../assets/TravelixBanner.png";
// import { ReactComponent as BlueUnderline } from "../../assets/underlineBlue.svg";
import BlueUnderline from "../../assets/underlineBlue.svg";

import { Link } from "react-router-dom";


const LoginPage = () => {
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [rememberMe, setRememberMe] = useState(false);

  const handleLogin = (e) => {
    e.preventDefault();
    // Logic for handling login
    console.log("Email:", email);
    console.log("Password:", password);
    console.log("Remember Me:", rememberMe);
  };

  return (
    <>
      <div className="login-page-container">
        <div className="login-page">
          <div className="login-form-container">
            <div className="login-form">
              <div className="login-heading-container">
                <h1 className="login-section-heading">Login</h1>
                {/* <BlueUnderline /> */}
                <img src={BlueUnderline}/>
              </div>

              <form className="login-box" onSubmit={handleLogin}>
                <div class="user-box">
                  <input
                    type="email"
                    id="email"
                    placeholder=""
                    value={email}
                    onChange={(e) => setEmail(e.target.value)}
                    required
                  />
                  <label>Email</label>
                </div>

                {/* ---Password--- */}

                <div class="user-box">
                  <input
                    type="password"
                    id="password"
                    placeholder=""
                    value={password}
                    onChange={(e) => setPassword(e.target.value)}
                    required
                  />
                  <label>Password</label>
                </div>

                <div class="remember-check">
                  <input
                    type="checkbox"
                    id="rememberMe"
                    checked={rememberMe}
                    onChange={(e) => setRememberMe(e.target.checked)}
                  />
                  <label htmlFor="rememberMe">Remember Me</label>
                </div>
                <div className="login-btn-container">
                  <button type="submit" className="login-btn-submit">
                    Login
                  </button>
                </div>
              </form>

              <div className="login-extra-links">
                <Link to="#">Forget Password</Link>
                <span> | </span>
                <Link to="/signup">Create New Account</Link>
              </div>
            </div>
          </div>
          <div className="login-image-container">
            <img src={loginImage} />
          </div>
        </div>
      </div>
    </>
  );
};

export default LoginPage;
