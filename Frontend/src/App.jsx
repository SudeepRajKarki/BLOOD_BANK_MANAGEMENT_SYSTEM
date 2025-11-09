// App.js
import React, { useContext } from "react";
import { BrowserRouter as Router, Routes, Route, Navigate } from "react-router-dom";
import { AuthContext } from "./Context/AuthContext";
import AdminRoute from "./components/AdminRoutes";
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
import ManageCampaigns from "./pages/AdminCampaign";
import DonorCampaigns from "./pages/DonorCampaigns";
import ReceiverInventory from "./pages/ReceiverSearch";
import Receiver from "./HomePage/Receiver";
import RequestApprove from "./pages/RequestApprove";
import Admin from "./HomePage/Admin";
import Donor from "./HomePage/Donor";
import DonorRequest from "./pages/DonorRequest";
import PrivateFooter from "./components/PrivateFooter";
import PublicFooter from "./components/PublicFooter";

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
            <Route path="/admind" element={<AdminRoute><Admin/></AdminRoute>}/>
            <Route path="/adminInventory" element={<AdminRoute><AdminInventory/></AdminRoute>}/>
            <Route path="/adminCampaign" element={<AdminRoute><ManageCampaigns/></AdminRoute>}/>
            <Route path="/requestApprove" element={<AdminRoute><RequestApprove/></AdminRoute>}/>
            <Route path="/donorcampaigns" element={<DonorCampaigns/>}/>
            <Route path="/donorRequests" element={<DonorRequest/>}/>
            <Route path="/donordashboard" element={<Donor/>}/>
            <Route path="/receiveri" element={<ReceiverInventory/>}/>
            <Route path="/receiverd" element={<Receiver />} />
          </>
        ) : null}

        <Route path="*" element={<Navigate to={token ? "/" : "/"} replace />} />
      </Routes>
      {token ? <PrivateFooter /> : <PublicFooter />}
    </Router>
  );
}

export default App;