// components/Public.jsx
import React, { useState } from 'react';
import { Link, NavLink } from 'react-router-dom';

const Public = () => {
  const [isMenuOpen, setIsMenuOpen] = useState(false);

  const toggleMenu = () => setIsMenuOpen(!isMenuOpen);

  return (
    <nav className="bg-[#DAADAD] shadow-md sticky top-0 z-50">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div className="flex justify-between h-16 items-center">
          <Link to="/" className="text-xl font-bold text-red-600 flex items-center">
            <span className="font-serif">RedAid</span>
          </Link>

          <div className="hidden md:flex space-x-6 items-center">
            <NavLink
              to="/"
              className={({ isActive }) =>
                `px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200 ${
                  isActive
                    ? 'text-red-700 bg-red-100 font-semibold'
                    : 'text-gray-700 hover:text-red-600 hover:bg-red-50'
                }`
              }
            >
              Home
            </NavLink>
            <NavLink
              to="/register"
              className={({ isActive }) =>
                `px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200 ${
                  isActive
                    ? 'text-red-700 bg-red-100 font-semibold'
                    : 'text-gray-700 hover:text-red-600 hover:bg-red-50'
                }`
              }
            >
              Register
            </NavLink>
            <NavLink
              to="/login"
              className={({ isActive }) =>
                `px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200 ${
                  isActive
                    ? 'text-red-700 bg-red-100 font-semibold'
                    : 'text-gray-700 hover:text-red-600 hover:bg-red-50'
                }`
              }
            >
              Login
            </NavLink>
          </div>

          <div className="md:hidden flex items-center">
            <button
              type="button"
              onClick={toggleMenu}
              className="text-gray-700 hover:text-red-600 focus:outline-none focus:ring-2 focus:ring-red-500 rounded-md p-2"
              aria-expanded={isMenuOpen}
              aria-label="Toggle navigation menu"
            >
              <svg
                className="h-6 w-6"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
                xmlns="http://www.w3.org/2000/svg"
              >
                {isMenuOpen ? (
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                ) : (
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6h16M4 12h16M4 18h16" />
                )}
              </svg>
            </button>
          </div>
        </div>
      </div>

      {isMenuOpen && (
        <div className="md:hidden">
          <div className="px-2 pt-2 pb-3 space-y-1 sm:px-3 bg-white border-t border-red-200 shadow-lg animate-fadeIn">
            <NavLink
              to="/"
              className={({ isActive }) =>
                `block px-3 py-2 rounded-md text-base font-medium transition-colors ${
                  isActive
                    ? 'text-red-700 bg-red-100 font-semibold'
                    : 'text-gray-700 hover:text-red-600 hover:bg-red-50'
                }`
              }
              onClick={() => setIsMenuOpen(false)}
            >
              Home
            </NavLink>
            <NavLink
              to="/register"
              className={({ isActive }) =>
                `block px-3 py-2 rounded-md text-base font-medium transition-colors ${
                  isActive
                    ? 'text-red-700 bg-red-100 font-semibold'
                    : 'text-gray-700 hover:text-red-600 hover:bg-red-50'
                }`
              }
              onClick={() => setIsMenuOpen(false)}
            >
              Register
            </NavLink>
            <NavLink
              to="/login"
              className={({ isActive }) =>
                `block px-3 py-2 rounded-md text-base font-medium transition-colors ${
                  isActive
                    ? 'text-red-700 bg-red-100 font-semibold'
                    : 'text-gray-700 hover:text-red-600 hover:bg-red-50'
                }`
              }
              onClick={() => setIsMenuOpen(false)}
            >
              Login
            </NavLink>
          </div>
        </div>
      )}
    </nav>
  );
};

export default Public;