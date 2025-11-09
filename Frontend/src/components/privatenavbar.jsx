import React, { useContext, useEffect, useState, useRef } from "react";
import { Link, NavLink, useNavigate } from "react-router-dom";
import { AuthContext } from "../Context/AuthContext";
import axios from "axios";
import { Bell } from "lucide-react";
import toast, { Toaster } from "react-hot-toast";

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

  // ✅ Updated logout with confirmation toast
  const handleLogout = () => {
    toast((t) => (
      <div className="flex flex-col gap-2">
        <span>Are you sure you want to logout?</span>
        <div className="flex gap-2 justify-end mt-1">
          <button
            onClick={() => {
              logout();
              navigate("/login", { replace: true });
              toast.dismiss(t.id);
            }}
            className="bg-red-600 text-white px-3 py-1 rounded-md hover:bg-red-700"
          >
            Yes
          </button>
          <button
            onClick={() => toast.dismiss(t.id)}
            className="bg-gray-200 text-gray-800 px-3 py-1 rounded-md hover:bg-gray-300"
          >
            Cancel
          </button>
        </div>
      </div>
    ), {
      duration: 5000
    });
  };

  if (!role) return null;

  const unreadCount = notifications.filter((n) => !n.is_read).length;

  return (
    <>
      {/* ✅ Add Toaster */}
      <Toaster position="top-center" reverseOrder={false} />

      <nav className="bg-[#DAADAD] shadow-md relative">
        <div className="max-w-7xl mx-auto px-4 flex justify-between items-center h-16">
          {/* ✅ Dynamic Home Link */}
          <Link to={homeRoute} className="flex items-center gap-2 text-xl font-bold text-red-600">
            <img src="/logo.png" alt="RedAid Logo" className="w-10 h-10" />
            <span className="font-serif">RedAid</span>
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
                          className={`p-3 text-sm cursor-pointer hover:bg-gray-100 ${!n.is_read ? "bg-red-50" : ""
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
    </>
  );
};

export default PrivateNavbar;
