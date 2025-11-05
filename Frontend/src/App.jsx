// App.js
import React, { useContext } from "react";
import { BrowserRouter as Router, Routes, Route, Navigate } from "react-router-dom";
import { AuthContext } from "./Context/AuthContext";
import Public from "./components/PublicNavbar";
import PrivateNavbar from "./components/PrivateNavbar"
import LandingPage from "./pages/LandingPage";
import Login from "./pages/Login";
import Register from "./pages/Register";
import VerifyEmail from "./pages/VerifyEmail";
import ForgotPassword from "./pages/ForgotPassword";
import ResetPassword from "./pages/ResetPassword";
import Dashboard from "./pages/Dashboard";
import Profile from "./pages/Profile";
import AdminInventory from "./pages/AdminInventory";

function App() {
  const { token } = useContext(AuthContext); 

  return (
    <Router>
      {token ? <PrivateNavbar /> : <Public />}

      <Routes>
        {!token ? (
          <>
            <Route path="/" element={<LandingPage />} />
            <Route path="/login" element={<Login />} />
            <Route path="/register" element={<Register />} />
            <Route path="/verify-email" element={<VerifyEmail />} />
            <Route path="/forgot-password" element={<ForgotPassword />} />
            <Route path="/reset-password" element={<ResetPassword />} />
          </>
        ) : null}

        {token ? (
          <>
            <Route path="/" element={<Dashboard />} />
            <Route path="/profile" element={<Profile/>}/>
            <Route path="/adminInventory" element={<AdminInventory/>}/>

          </>
        ) : null}

        <Route path="*" element={<Navigate to={token ? "/" : "/"} replace />} />
      </Routes>
    </Router>
  );
}

export default App;