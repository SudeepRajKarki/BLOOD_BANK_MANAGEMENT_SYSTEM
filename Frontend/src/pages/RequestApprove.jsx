import React, { useEffect, useState, useContext } from "react";
import { AuthContext } from "../Context/AuthContext";
import api from "../api/axios";
import toast, { Toaster } from "react-hot-toast";

const RequestApprove = () => {
  const { token } = useContext(AuthContext);
  const [requests, setRequests] = useState([]);
  const [loading, setLoading] = useState(false);
  const [expandedId, setExpandedId] = useState(null);

  const fetchRequests = async () => {
    setLoading(true);
    try {
      const res = await api.get("/admin/requests", {
        headers: { Authorization: `Bearer ${token}` },
      });
      setRequests(Array.isArray(res.data) ? res.data : []);
    } catch (err) {
      toast.error(err.response?.data?.error || "Failed to fetch requests");
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchRequests();
  }, []);

  const handleApprove = async (id) => {
    try {
      const res = await api.post(
        `/admin/requests/${id}/approve`,
        {},
        { headers: { Authorization: `Bearer ${token}` } }
      );
      toast.success(res.data.message || "Request approved successfully!");
      fetchRequests();
    } catch (err) {
      toast.error(err.response?.data?.message || "Approval failed");
    }
  };

  const handleDeny = async (id) => {
    try {
      const res = await api.post(
        `/admin/requests/${id}/deny`,
        {},
        { headers: { Authorization: `Bearer ${token}` } }
      );
      toast.error(res.data.message || "Request denied");
      fetchRequests();
    } catch (err) {
      toast.error(err.response?.data?.message || "Failed to deny request");
    }
  };

  const toggleExpand = (id) => {
    setExpandedId(expandedId === id ? null : id);
  };

  return (
    <div className="relative container mx-auto p-4 font-sans text-gray-800">
      <Toaster position="top-center" reverseOrder={false} />

      {loading && (
        <div className="absolute inset-0 flex items-center justify-center bg-white/70 backdrop-blur-sm z-20">
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
            <span className="font-medium">Loading requests...</span>
          </div>
        </div>
      )}

      <h2 className="text-2xl font-bold mb-4 text-red-600 text-center">
        Blood Requests
      </h2>

      {!loading && requests.length === 0 && (
        <p className="text-center text-gray-600">No requests found.</p>
      )}

      <div className="space-y-4 mt-6">
        {requests.map((req) => {
          const isExpanded = expandedId === req.id;
          return (
            <div
              key={req.id}
              className="border border-gray-300 p-4 rounded-2xl shadow-md bg-gray-100"
            >
              <div className="flex justify-between items-center">
                <div className="text-sm md:text-base">
                  <p>
                    <strong>Request ID:</strong> {req.id}
                  </p>
                  <p>
                    <strong>Receiver:</strong> {req.receiver?.name || "N/A"}
                  </p>
                  <p>
                    <strong>Blood Type:</strong> {req.blood_type}
                  </p>
                  <p>
                    <strong>Quantity:</strong> {req.quantity_ml} ml
                  </p>
                  <p>
                    <strong>Priority:</strong> {req.priority}
                  </p>
                  <p>
                    <strong>Status:</strong>{" "}
                    <span
                      className={`${
                        req.status === "Pending"
                          ? "text-yellow-600"
                          : req.status === "Approved"
                          ? "text-green-600"
                          : "text-red-600"
                      } font-medium`}
                    >
                      {req.status}
                    </span>
                  </p>
                </div>

                <div className="flex flex-col space-y-2 items-end">
                  {req.status === "Pending" && (
                    <div className="space-x-2">
                      <button
                        onClick={() => handleApprove(req.id)}
                        className="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-xl transition-transform transform hover:scale-105 shadow"
                      >
                        Approve
                      </button>
                      <button
                        onClick={() => handleDeny(req.id)}
                        className="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-xl transition-transform transform hover:scale-105 shadow"
                      >
                        Deny
                      </button>
                    </div>
                  )}

                  <button
                    onClick={() => toggleExpand(req.id)}
                    className="text-sm text-blue-600 hover:underline mt-2"
                  >
                    {isExpanded ? "Hide Details ▲" : "View Details ▼"}
                  </button>
                </div>
              </div>

              {/* Expanded details */}
              {isExpanded && (
                <div className="mt-4 border-t border-gray-300 pt-3 text-sm text-gray-700 space-y-1">
                  <p>
                    <strong>Receiver Email:</strong>{" "}
                    {req.receiver?.email || "N/A"}
                  </p>
                  <p>
                    <strong>Receiver Phone:</strong>{" "}
                    {req.receiver?.phone || "N/A"}
                  </p>
                  <p>
                    <strong>Receiver location:</strong>{" "}
                    {req.receiver?.location || "N/A"}
                  </p>
                  <p>
                    <strong>Request Date:</strong>{" "}
                    {new Date(req.created_at).toLocaleString()}
                  </p>
                  {req.reason && (
                    <p>
                      <strong>Reason:</strong> {req.reason}
                    </p>
                  )}
                  {req.inventory_info && (
                    <p>
                      <strong>Available Inventory:</strong>{" "}
                      {req.inventory_info.quantity_ml} ml
                    </p>
                  )}
                </div>
              )}
            </div>
          );
        })}
      </div>
    </div>
  );
};

export default RequestApprove;
