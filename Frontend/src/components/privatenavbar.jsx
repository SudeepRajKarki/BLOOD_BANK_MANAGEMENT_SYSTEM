import React, { useContext, useEffect, useState, useRef } from "react";
import { Link, NavLink, useNavigate } from "react-router-dom";
import { AuthContext } from "../Context/AuthContext";
import axios from "axios";
import { Bell } from "lucide-react";

const PrivateNavbar = () => {
  const { role, logout, token } = useContext(AuthContext);
  const navigate = useNavigate();
  const [notifications, setNotifications] = useState([]);
  const [dropdownOpen, setDropdownOpen] = useState(false);
  const dropdownRef = useRef(null);

  // ✅ Dynamic home route based on role
  const homeRoute =
    role === "admin"
      ? "/admind"
      : role === "donor"
      ? "/donordashboard"
      : role === "receiver"
      ? "/receiverd"
      : "/";

  // Fetch notifications
  const fetchNotifications = async () => {
    try {
      const res = await axios.get("http://localhost:8000/api/notifications", {
        headers: { Authorization: `Bearer ${token}` },
      });
      setNotifications(res.data);
    } catch (error) {
      console.error("Failed to load notifications:", error);
    }
  };

  useEffect(() => {
    if (token) fetchNotifications();
    const interval = setInterval(fetchNotifications, 30000);
    return () => clearInterval(interval);
  }, [token]);

  useEffect(() => {
    const handleClickOutside = (e) => {
      if (dropdownRef.current && !dropdownRef.current.contains(e.target)) {
        setDropdownOpen(false);
      }
    };
    document.addEventListener("mousedown", handleClickOutside);
    return () => document.removeEventListener("mousedown", handleClickOutside);
  }, []);

  const handleNotificationClick = async (notification) => {
    try {
      await axios.put(
        `http://localhost:8000/api/notifications/${notification.id}`,
        { is_read: true },
        { headers: { Authorization: `Bearer ${token}` } }
      );
    } catch (err) {
      console.error("Failed to mark as read:", err);
    }

    setDropdownOpen(false);

    switch (notification.type) {
      case "donation_confirmed":
      case "donation_declined":
      case "request_approved":
      case "request_denied":
        navigate("/receiverd");
        break;
      case "donation_request":
        navigate("/donorRequests");
        break;
      case "email":
        navigate("/profile");
        break;
      case "request_approval":
        navigate("/requestApprove");
        break;
      default:
        navigate("/");
    }
  };

  const handleLogout = () => {
    logout();
    navigate("/login", { replace: true });
  };

  if (!role) return null;

  const unreadCount = notifications.filter((n) => !n.is_read).length;

  return (
    <nav className="bg-[#DAADAD] shadow-md relative">
      <div className="max-w-7xl mx-auto px-4 flex justify-between items-center h-16">
        {/* ✅ Dynamic Home Link */}
        <Link to={homeRoute} className="text-xl font-bold text-red-600">
          RedAid
        </Link>

        <div className="flex space-x-6 items-center">
          {role === "admin" && (
            <>
              <NavLink
                to="/admind"
                className={({ isActive }) =>
                  isActive
                    ? "text-red-600 font-semibold"
                    : "text-gray-700 hover:text-red-600"
                }
              >
                Home
              </NavLink>
              <NavLink to="/requestApprove" className="text-gray-700 hover:text-red-600">
                Requests
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
                  isActive
                    ? "text-red-600 font-semibold"
                    : "text-gray-700 hover:text-red-600"
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
                  isActive
                    ? "text-red-600 font-semibold"
                    : "text-gray-700 hover:text-red-600"
                }
              >
                Home
              </NavLink>
              <NavLink to="/donorRequests" className="text-gray-700 hover:text-red-600">
                Requests
              </NavLink>
              <NavLink to="/donorcampaigns" className="text-gray-700 hover:text-red-600">
                Campaigns
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

          {/* Notifications Bell */}
          <div className="relative" ref={dropdownRef}>
            <button
              onClick={() => setDropdownOpen(!dropdownOpen)}
              className="relative text-gray-700 hover:text-red-600"
            >
              <Bell size={24} />
              {unreadCount > 0 && (
                <span className="absolute -top-1 -right-1 bg-red-600 text-white text-xs rounded-full px-1.5">
                  {unreadCount}
                </span>
              )}
            </button>

            {dropdownOpen && (
              <div className="absolute right-0 mt-2 w-80 bg-white shadow-lg rounded-md border border-gray-200 z-50">
                <div className="p-2 font-semibold border-b">Notifications</div>
                <div className="max-h-64 overflow-y-auto">
                  {notifications.length === 0 ? (
                    <p className="p-3 text-gray-500 text-sm text-center">
                      No notifications
                    </p>
                  ) : (
                    notifications.map((n) => (
                      <div
                        key={n.id}
                        onClick={() => handleNotificationClick(n)}
                        className={`p-3 text-sm cursor-pointer hover:bg-gray-100 ${
                          !n.is_read ? "bg-red-50" : ""
                        }`}
                      >
                        <p>{n.message}</p>
                        <span className="text-xs text-gray-500">
                          {n.type.replace("_", " ")}
                        </span>
                      </div>
                    ))
                  )}
                </div>
              </div>
            )}
          </div>
        </div>
      </div>
    </nav>
  );
};

export default PrivateNavbar;
