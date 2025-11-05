import React from "react";
import { Link } from "react-router-dom";

const PublicFooter = () => {
  return (
    <footer className="bg-[#DAADAD] text-gray-800 py-8">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 md:grid-cols-3 gap-8">
        {/* Left Section */}
        <div>
          <h2 className="text-xl font-bold text-red-700 mb-3">BBMS</h2>
          <p className="text-sm">
            A digital platform connecting blood donors and receivers to ensure faster and safer blood availability.
          </p>
        </div>

        {/* Quick Links */}
        <div>
          <h3 className="text-lg font-semibold text-red-700 mb-3">Quick Links</h3>
          <ul className="space-y-2 text-sm">
            <li><Link to="/" className="hover:text-red-600">Home</Link></li>
            <li><Link to="/register" className="hover:text-red-600">Register</Link></li>
            <li><Link to="/login" className="hover:text-red-600">Login</Link></li>
            <li><Link to="/about" className="hover:text-red-600">About</Link></li>
          </ul>
        </div>

        {/* Contact Info */}
        <div>
          <h3 className="text-lg font-semibold text-red-700 mb-3">Contact Us</h3>
          <ul className="space-y-2 text-sm">
            <li>Email: bloodbankmanagementsystem061@gmail.com</li>
            <li>Phone: +977-9800000000</li>
            <li>Location: Kathmandu, Nepal</li>
          </ul>
        </div>
      </div>

      <div className="border-t border-red-300 mt-8 pt-4 text-center text-sm text-gray-700">
        © {new Date().getFullYear()} BBMS All Rights Reserved.
      </div>
    </footer>
  );
};

export default PublicFooter;
