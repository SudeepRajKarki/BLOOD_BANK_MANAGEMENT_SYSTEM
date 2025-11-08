// components/PrivateNavbar.jsx
import React, { useContext,useEffect } from "react";
import { Link, NavLink, useNavigate } from "react-router-dom";
import { AuthContext } from "../Context/AuthContext"; 

const PrivateNavbar = () => { 
  const { role, logout } = useContext(AuthContext); 
  const navigate = useNavigate();

  const handleLogout = () => {
    logout(); 
    navigate("/login", { replace: true });
  };
 
  if (!role) return null;

  return (
    <nav className="bg-[#DAADAD] shadow-md">
      <div className="max-w-7xl mx-auto px-4 flex justify-between items-center h-16">
        <Link to="/" className="text-xl font-bold text-red-600">
          BBMS
        </Link>

        <div className="flex space-x-6">

          {role === "admin" && (
            <>
            <NavLink
            to="/"
            className={({ isActive }) =>
              isActive ? "text-red-600 font-semibold" : "text-gray-700 hover:text-red-600"
            }
          >
            Home
          </NavLink>

              <NavLink to="/adminCampaign" className="text-gray-700 hover:text-red-600">
                Campaigns
              </NavLink>
              <NavLink to="/adminInventory" className="text-gray-700 hover:text-red-600">
                Inventory
              </NavLink>
            </>
          )}

          {role === "receiver" && (

            <>
              <NavLink
               to="/receiverd"
               className={({ isActive }) =>
               isActive ? "text-red-600 font-semibold" : "text-gray-700 hover:text-red-600"
               }
               >
                Home
              </NavLink>

              <NavLink to="/receiveri" className="text-gray-700 hover:text-red-600">
                Request
              </NavLink>
              
            </>
          )}

          {role === "donor" && (
            <>  
                <NavLink
               to="/donordashboard"
               className={({ isActive }) =>
               isActive ? "text-red-600 font-semibold" : "text-gray-700 hover:text-red-600"
               }
               >
                Home
              </NavLink>  
              <NavLink to="/donorcampaigns" className="text-gray-700 hover:text-red-600">
                Campaigns
              </NavLink>
              <NavLink to="/requests" className="text-gray-700 hover:text-red-600">
                Requests
              </NavLink>
             
            </>
          )}
           <NavLink to="/profile" className="text-gray-700 hover:text-red-600">
                Profile
           </NavLink>
          
          <button
            onClick={handleLogout}
            className="text-gray-700 hover:text-red-600 font-semibold"
          >
            Logout
          </button>
        </div>
      </div>
    </nav>
  );
};

export default PrivateNavbar;