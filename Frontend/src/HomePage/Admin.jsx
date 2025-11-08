import React, { useEffect, useState, useContext } from "react";
import { AuthContext } from "../Context/AuthContext";
import api from "../api/axios";
import toast, { Toaster } from "react-hot-toast";

const Admin = () => {
  const { token } = useContext(AuthContext);
  const [stats, setStats] = useState({
    donations: 0,
    requests: 0,
    campaigns: 0,
    top_donors: [],
  });
  const [loading, setLoading] = useState(false);

  const fetchDashboardData = async () => {
    setLoading(true);
    try {
      const res = await api.get("/dashboard", {
        headers: { Authorization: `Bearer ${token}` },
      });
      setStats(res.data);
    } catch (err) {
      toast.error(err.response?.data?.message || "Failed to load dashboard data");
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchDashboardData();
  }, []);

  return (
    <div className="container mx-auto p-6 font-sans text-gray-800 relative">
      <Toaster position="top-center" reverseOrder={false} />

      {loading && (
        <div className="absolute inset-0 flex items-center justify-center bg-white/60 backdrop-blur-sm z-10">
          <div className="flex items-center space-x-3 text-red-600">
            <svg
              className="animate-spin h-6 w-6 text-red-600"
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
            >
              <circle
                className="opacity-25"
                cx="12"
                cy="12"
                r="10"
                stroke="currentColor"
                strokeWidth="4"
              ></circle>
              <path
                className="opacity-75"
                fill="currentColor"
                d="M4 12a8 8 0 018-8v8H4z"
              ></path>
            </svg>
            <span className="font-medium">Loading dashboard...</span>
          </div>
        </div>
      )}

      <h2 className="text-3xl font-bold mb-6 text-center text-red-600">
        Admin Dashboard
      </h2>

      <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div className="bg-red-100 p-6 rounded-xl shadow-md text-center">
          <h3 className="text-xl font-semibold text-red-700">Total Donations</h3>
          <p className="text-3xl font-bold mt-2">{stats.donations}</p>
        </div>

        <div className="bg-yellow-100 p-6 rounded-xl shadow-md text-center">
          <h3 className="text-xl font-semibold text-yellow-700">Blood Requests</h3>
          <p className="text-3xl font-bold mt-2">{stats.requests}</p>
        </div>

        <div className="bg-green-100 p-6 rounded-xl shadow-md text-center">
          <h3 className="text-xl font-semibold text-green-700">Campaigns</h3>
          <p className="text-3xl font-bold mt-2">{stats.campaigns}</p>
        </div>
      </div>

      <div className="bg-white p-6 rounded-xl shadow-md">
        <h3 className="text-xl font-semibold mb-4 text-gray-700">Top Donors</h3>
        {stats.top_donors.length > 0 ? (
          <table className="w-full border border-gray-200 text-left text-sm">
            <thead>
              <tr className="bg-gray-100 text-gray-600">
                <th className="p-3 border-b">Donor ID</th>
                <th className="p-3 border-b">Name</th>
                <th className="p-3 border-b">Total Donations</th>
              </tr>
            </thead>
            <tbody>
              {stats.top_donors.map((donor, index) => (
                <tr key={index} className="hover:bg-gray-50">
                  <td className="p-3 border-b">{donor.donor_id}</td>
                  <td className="p-3 border-b">{donor.donor?.name}</td>
                  <td className="p-3 border-b">{donor.total}</td>
                </tr>
              ))}
            </tbody>
          </table>
        ) : (
          <p className="text-gray-600">No donor data available.</p>
        )}
      </div>
    </div>
  );
};

export default Admin;
