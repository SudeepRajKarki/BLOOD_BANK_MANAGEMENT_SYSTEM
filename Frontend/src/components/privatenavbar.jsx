import React from "react";
import { Link, NavLink, useNavigate } from "react-router-dom";

const PrivateNavbar = ({ role }) => {
  const navigate = useNavigate();

  const handleLogout = () => {
    // 1. Clear authentication data
    localStorage.removeItem("token");
    localStorage.removeItem("role");
    localStorage.removeItem("user"); // if you store user info

    // 2. Redirect to login
    navigate("/login");
  };

  return (
    <nav className="bg-[#DAADAD] shadow-md">
      <div className="max-w-7xl mx-auto px-4 flex justify-between items-center h-16">
        {/* Logo */}
        <Link to="/" className="text-xl font-bold text-red-600">
          BBMS
        </Link>

        {/* Menu Items */}
        <div className="flex space-x-6">
          {/* Common to all */}
          <NavLink
            to="/"
            className={({ isActive }) =>
              isActive ? "text-red-600 font-semibold" : "text-gray-700 hover:text-red-600"
            }
          >
            Home
          </NavLink>

          {/* Admin options */}
          {role === "admin" && (
            <>
              <NavLink to="/manage-campaigns" className="text-gray-700 hover:text-red-600">
                Manage Campaigns
              </NavLink>
              <NavLink to="/manage-inventory" className="text-gray-700 hover:text-red-600">
                Manage Inventory
              </NavLink>
            </>
          )}

          {/* Receiver options */}
          {role === "receiver" && (
            <>
              <NavLink to="/requests" className="text-gray-700 hover:text-red-600">
                Requests
              </NavLink>
              <NavLink to="/search" className="text-gray-700 hover:text-red-600">
                Search
              </NavLink>
            </>
          )}

          {/* Donor options */}
          {role === "donor" && (
            <>
              <NavLink to="/campaigns" className="text-gray-700 hover:text-red-600">
                Campaigns
              </NavLink>
              <NavLink to="/requests" className="text-gray-700 hover:text-red-600">
                Requests
              </NavLink>
            </>
          )}

          {/* Common profile + logout */}
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
