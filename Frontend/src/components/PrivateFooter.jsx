import React, { useContext } from "react";
import { Link } from "react-router-dom";
import { AuthContext } from "../Context/AuthContext";

const PrivateFooter = () => {
  const { role } = useContext(AuthContext);

  // Dynamic Home route based on role
  const homeRoute =
    role === "admin"
      ? "/admind"
      : role === "donor"
      ? "/donordashboard"
      : role === "receiver"
      ? "/receiverd"
      : "/";

  return (
    <footer className="bg-[#DAADAD] text-gray-800 shadow-[0_-4px_10px_rgba(0,0,0,0.15)]">
      {/* Main Footer Content */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 grid grid-cols-1 md:grid-cols-3 gap-8 items-center">
        
        {/* Brand */}
        <div className="text-center md:text-left">
          <h2 className="text-2xl font-bold text-red-700 font-serif mb-2">
            RedAid
          </h2>
          <p className="text-sm">
            Manage your activities and stay updated through our blood bank system.
          </p>
        </div>

        {/* Navigation Links */}
        <div className="flex flex-wrap justify-center gap-4 text-sm font-medium">
          <Link to={homeRoute} className="hover:text-red-700 transition-colors">
            Home
          </Link>

          <Link to="/profile" className="hover:text-red-700 transition-colors">
            Profile
          </Link>
        </div>

        {/* Footer Info */}
        <div className="text-center md:text-right text-sm text-gray-700">
          <p>
            © {new Date().getFullYear()}{" "}
            <span className="text-red-700 font-semibold">BBMS</span>. All rights
            reserved.
          </p>
        </div>
      </div>
    </footer>
  );
};

export default PrivateFooter;
