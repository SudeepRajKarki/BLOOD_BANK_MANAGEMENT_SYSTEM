import React from "react";
import { Link } from "react-router-dom";

const PublicFooter = () => {
  return (
    // Add top shadow here
    <footer className="bg-[#DAADAD] text-gray-800 shadow-[0_-4px_10px_rgba(0,0,0,0.15)]">
      {/* Main Footer Content */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 grid grid-cols-1 md:grid-cols-3 gap-8">

        {/* Brand Info */}
        <div>
          <h2 className="text-2xl font-bold text-red-700 font-serif mb-2 flex items-center gap-2">
            <img src="/logo.png" alt="RedAid Logo" className="w-10 h-10" />
            <span className="font-serif">RedAid</span>
          </h2>
          <p className="text-sm leading-relaxed">
            A digital platform connecting blood donors and receivers to ensure faster and safer blood availability.
          </p>
        </div>

        {/* Quick Links */}
        <div>
          <h3 className="text-lg font-semibold text-red-700 mb-3">Quick Links</h3>
          <ul className="space-y-2 text-sm">
            <li>
              <Link
                to="/"
                className="block px-3 py-2 rounded-md hover:bg-red-50 hover:text-red-700 transition-colors duration-200"
              >
                Home
              </Link>
            </li>
            <li>
              <Link
                to="/register"
                className="block px-3 py-2 rounded-md hover:bg-red-50 hover:text-red-700 transition-colors duration-200"
              >
                Register
              </Link>
            </li>
            <li>
              <Link
                to="/login"
                className="block px-3 py-2 rounded-md hover:bg-red-50 hover:text-red-700 transition-colors duration-200"
              >
                Login
              </Link>
            </li>
            <li>
              <Link
                to="/about"
                className="block px-3 py-2 rounded-md hover:bg-red-50 hover:text-red-700 transition-colors duration-200"
              >
                About
              </Link>
            </li>
          </ul>
        </div>

        {/* Contact Info */}
        <div>
          <h3 className="text-lg font-semibold text-red-700 mb-3">Contact Us</h3>
          <ul className="space-y-2 text-sm">
            <li className="px-3 py-1">
              <span className="font-medium">Email:</span>{" "}
              bloodbankmanagementsystem061@gmail.com
            </li>
            <li className="px-3 py-1">
              <span className="font-medium">Phone:</span> +977-9800000000
            </li>
            <li className="px-3 py-1">
              <span className="font-medium">Location:</span> Kathmandu, Nepal
            </li>
          </ul>
        </div>
      </div>

      {/* Footer Bottom */}
      <div className="bg-[#DAADAD] py-4 text-center text-sm text-gray-700 border-t border-red-200/30">
        © {new Date().getFullYear()}{" "}
        <span className="font-semibold text-red-700">RedAid</span>. All Rights Reserved.
      </div>
    </footer>
  );
};

export default PublicFooter;
