import React from "react";
import { Link } from "react-router-dom";

const PrivateFooter = ({ role }) => {
  const roleLinks = {
    admin: [
      { to: "/adminCampaign", label: "Manage Campaigns" },
      { to: "/manage-inventory", label: "Inventory" },
      { to: "/users", label: "Users" },
    ],
    donor: [
      { to: "/campaigns", label: "Campaigns" },
      { to: "/requests", label: "Requests" },
    ],
    receiver: [
      { to: "/requests", label: "Requests" },
      { to: "/search", label: "Search" },
    ],
  };

  return (
    <footer className="bg-[#DAADAD] text-gray-800 py-6">
      <div className="max-w-7xl mx-auto px-4 flex flex-col md:flex-row justify-between items-center gap-3 md:gap-0">
        <div className="text-lg font-bold text-red-700">BBMS</div>

        <div className="flex flex-col md:flex-row md:space-x-4 text-sm items-center gap-2">
          <Link to="/" className="hover:text-red-600">Home</Link>

          {roleLinks[role]?.map((link) => (
            <Link key={link.to} to={link.to} className="hover:text-red-600">
              {link.label}
            </Link>
          ))}

          <Link to="/profile" className="hover:text-red-600">Profile</Link>
        </div>

        <div className="text-xs text-gray-700 mt-3 md:mt-0">
          © {new Date().getFullYear()} BBMS
        </div>
      </div>
    </footer>
  );
};

export default PrivateFooter;
