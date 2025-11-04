import React from 'react';
import { Link, NavLink } from 'react-router-dom';

const Public = () => {
  return (
    <nav className="bg-[#DAADAD]">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex justify-between h-16 items-center">
          {/* Logo */}
          <Link to="/" className="text-xl font-bold text-red-600">
            BBMS
          </Link>

          {/* Menu Links */}
          <div className="hidden md:flex space-x-6">
            <NavLink
              to="/"
              className={({ isActive }) =>
                isActive ? 'text-red-600 font-semibold' : 'text-gray-700 hover:text-red-600'
              }
            >
              Home
            </NavLink>
            <NavLink
              to="/register"
              className={({ isActive }) =>
                isActive ? 'text-red-600 font-semibold' : 'text-gray-700 hover:text-red-600'
              }
            >
              Register
            </NavLink>
            <NavLink
              to="/login"
              className={({ isActive }) =>
                isActive ? 'text-red-600 font-semibold' : 'text-gray-700 hover:text-red-600'
              }
            >
              Login
            </NavLink>
          </div>

          {/* Mobile Hamburger */}
          <div className="md:hidden">
            <button
              type="button"
              className="text-gray-700 hover:text-red-600 focus:outline-none"
              // Add onClick logic to toggle mobile menu
            >
              <svg className="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 6h16M4 12h16M4 18h16" />
              </svg>
            </button>
          </div>
        </div>
      </div>
    </nav>
  );
};

export default Public;
