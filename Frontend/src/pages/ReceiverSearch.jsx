import React, { useState, useEffect } from "react";
import axios from "axios";

const bloodTypes = ["A+", "A-", "B+", "B-", "AB+", "AB-", "O+", "O-"];
const locations = [
  "Kathmandu",
  "Lalitpur",
  "Bhaktapur",
  "Pokhara",
  "Butwal",
  "Biratnagar",
  "Hetauda",
  "Dharan",
  "Janakpur",
  "Birgunj",
  "Nepalgunj",
  "Mahendranagar",
  "Chitwan",
];

const ReceiverSearch = () => {
  const [bloodType, setBloodType] = useState("");
  const [quantity, setQuantity] = useState("");
  const [reason, setReason] = useState("");
  const [location, setLocation] = useState("");
  const [requests, setRequests] = useState([]);
  const [message, setMessage] = useState("");
  const [messageType, setMessageType] = useState(""); // 'success' or 'error'
  const [loading, setLoading] = useState(false);
  const [matchedDonors, setMatchedDonors] = useState({}); // Store matched donors for each request
  const [expandedRequest, setExpandedRequest] = useState(null); // Track which request is expanded

  const fetchRequests = async () => {
    try {
      const res = await axios.get("http://localhost:8000/api/requests", {
        headers: { Authorization: `Bearer ${localStorage.getItem("token")}` },
      });
      setRequests(res.data);
    } catch (err) {
      console.error(err);
    }
  };

  const fetchMatchedDonors = async (requestId) => {
    try {
      const res = await axios.get(
        `http://localhost:8000/api/requests/${requestId}/matched-donors`,
        {
          headers: { Authorization: `Bearer ${localStorage.getItem("token")}` },
        }
      );
      setMatchedDonors((prev) => ({
        ...prev,
        [requestId]: res.data.matched_donors,
      }));
    } catch (err) {
      console.error("Error fetching matched donors:", err);
    }
  };

  useEffect(() => {
    fetchRequests();
  }, []);

  const handleRequest = async () => {
    if (!bloodType || !quantity || !reason) {
      setMessage("Please fill all required fields!");
      setMessageType("error");
      return;
    }

    if (parseInt(quantity) < 1) {
      setMessage("Quantity must be at least 1 ml!");
      setMessageType("error");
      return;
    }

    setLoading(true);
    setMessage("");
    setMessageType("");

    try {
      const res = await axios.post(
        "http://localhost:8000/api/request",
        {
          blood_type: bloodType,
          quantity_ml: parseInt(quantity),
          reason,
          location: location || null,
        },
        {
          headers: { Authorization: `Bearer ${localStorage.getItem("token")}` },
        }
      );

      // Use backend message
      setMessage(res.data.message || "Request created successfully!");
      setMessageType("success");

      // Refresh requests list
      await fetchRequests();

      // reset fields
      setBloodType("");
      setQuantity("");
      setReason("");
      setLocation("");

      // Clear message after 5 seconds
      setTimeout(() => {
        setMessage("");
        setMessageType("");
      }, 5000);
    } catch (err) {
      console.error(err);
      if (err.response?.status === 403) {
        setMessage("You are not authorized to make requests.");
      } else if (err.response?.data?.message) {
        setMessage(err.response.data.message);
      } else {
        setMessage("Something went wrong. Please try again.");
      }
      setMessageType("error");
    } finally {
      setLoading(false);
    }
  };

  const toggleRequestDetails = (requestId) => {
    if (expandedRequest === requestId) {
      setExpandedRequest(null);
    } else {
      setExpandedRequest(requestId);
      // Fetch matched donors if not already fetched
      if (!matchedDonors[requestId]) {
        fetchMatchedDonors(requestId);
      }
    }
  };

  const getPriorityColor = (priority) => {
    switch (priority) {
      case "High":
        return "text-red-600 font-bold";
      case "Medium":
        return "text-yellow-600 font-semibold";
      case "Low":
        return "text-green-600";
      default:
        return "text-gray-600";
    }
  };

  const getStatusColor = (status) => {
    switch (status) {
      case "Approved":
        return "text-green-600 font-semibold";
      case "Rejected":
        return "text-red-600 font-semibold";
      case "Pending":
        return "text-yellow-600 font-semibold";
      default:
        return "text-gray-600";
    }
  };

  return (
    <div className="p-4 max-w-4xl mx-auto">
      <h2 className="text-2xl font-bold mb-6">Request Blood</h2>

      {/* Request Form */}
      <div className="bg-white shadow-md rounded-lg p-6 mb-6">
        <div className="mb-4">
          <label className="block text-sm font-medium text-gray-700 mb-1">
            Blood Type: <span className="text-red-500">*</span>
          </label>
          <select
            className="border border-gray-300 rounded-md p-2 w-full focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            value={bloodType}
            onChange={(e) => setBloodType(e.target.value)}
          >
            <option value="">Select Blood Type</option>
            {bloodTypes.map((type) => (
              <option key={type} value={type}>
                {type}
              </option>
            ))}
          </select>
        </div>

        <div className="mb-4">
          <label className="block text-sm font-medium text-gray-700 mb-1">
            Quantity (ml): <span className="text-red-500">*</span>
          </label>
          <input
            type="number"
            min="1"
            className="border border-gray-300 rounded-md p-2 w-full focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            value={quantity}
            onChange={(e) => setQuantity(e.target.value)}
            placeholder="e.g., 150"
          />
        </div>

        <div className="mb-4">
          <label className="block text-sm font-medium text-gray-700 mb-1">
            Reason: <span className="text-red-500">*</span>
          </label>
          <textarea
            className="border border-gray-300 rounded-md p-2 w-full focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            value={reason}
            onChange={(e) => setReason(e.target.value)}
            rows="3"
            placeholder="Describe why you need blood (e.g., surgery, accident, medical treatment)..."
          />
          <p className="text-xs text-gray-500 mt-1">
            The system will automatically determine priority based on your reason.
          </p>
        </div>

        <div className="mb-4">
          <label className="block text-sm font-medium text-gray-700 mb-1">
            Location: <span className="text-gray-500">(Optional)</span>
          </label>
          <select
            className="border border-gray-300 rounded-md p-2 w-full focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            value={location}
            onChange={(e) => setLocation(e.target.value)}
          >
            <option value="">Select Location (Optional)</option>
            {locations.map((loc) => (
              <option key={loc} value={loc}>
                {loc}
              </option>
            ))}
          </select>
          <p className="text-xs text-gray-500 mt-1">
            Where the blood is needed. Helps match nearby donors.
          </p>
        </div>

        <button
          className={`bg-blue-500 hover:bg-blue-600 text-white p-3 rounded-md w-full font-semibold transition-colors ${
            loading ? "opacity-50 cursor-not-allowed" : ""
          }`}
          onClick={handleRequest}
          disabled={loading}
        >
          {loading ? "Submitting..." : "Submit Request"}
        </button>

        {message && (
          <div
            className={`mt-4 p-3 rounded-md ${
              messageType === "success"
                ? "bg-green-100 text-green-800 border border-green-300"
                : "bg-red-100 text-red-800 border border-red-300"
            }`}
          >
            {message}
          </div>
        )}
      </div>

      {/* Requests List */}
      <div className="bg-white shadow-md rounded-lg p-6">
        <h3 className="text-xl font-bold mb-4">Your Blood Requests</h3>
        {requests.length === 0 ? (
          <p className="text-gray-500 text-center py-8">
            No requests yet. Create your first blood request above.
          </p>
        ) : (
          <div className="space-y-4">
            {requests.map((req) => (
              <div
                key={req.id}
                className="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow"
              >
                <div className="flex justify-between items-start">
                  <div className="flex-1">
                    <div className="flex items-center gap-4 mb-2">
                      <p className="text-lg font-semibold">
                        {req.blood_type} - {req.quantity_ml} ml
                      </p>
                      <span className={getPriorityColor(req.priority)}>
                        Priority: {req.priority}
                      </span>
                      <span className={getStatusColor(req.status)}>
                        {req.status}
                      </span>
                    </div>
                    <p className="text-sm text-gray-600 mb-1">
                      <strong>Reason:</strong> {req.reason}
                    </p>
                    {req.location && (
                      <p className="text-sm text-gray-600 mb-1">
                        <strong>Location:</strong> {req.location}
                      </p>
                    )}
                    <p className="text-xs text-gray-500">
                      Requested on:{" "}
                      {new Date(req.created_at).toLocaleString()}
                    </p>
                  </div>
                  <button
                    className="text-blue-500 hover:text-blue-700 text-sm font-medium"
                    onClick={() => toggleRequestDetails(req.id)}
                  >
                    {expandedRequest === req.id ? "Hide Details" : "View Details"}
                  </button>
                </div>

                {/* Expanded Details */}
                {expandedRequest === req.id && (
                  <div className="mt-4 pt-4 border-t border-gray-200">
                    <h4 className="font-semibold mb-2">Request Details</h4>
                    <div className="grid grid-cols-2 gap-2 text-sm mb-4">
                      <div>
                        <strong>Request ID:</strong> #{req.id}
                      </div>
                      <div>
                        <strong>Priority:</strong>{" "}
                        <span className={getPriorityColor(req.priority)}>
                          {req.priority}
                        </span>
                      </div>
                      <div>
                        <strong>Status:</strong>{" "}
                        <span className={getStatusColor(req.status)}>
                          {req.status}
                        </span>
                      </div>
                      {req.location && (
                        <div>
                          <strong>Location:</strong> {req.location}
                        </div>
                      )}
                    </div>

                    {/* Notification Status */}
                    <div className="mb-4 p-3 rounded-md bg-blue-50 border border-blue-200">
                      <p className="text-sm font-semibold text-blue-800">
                        <strong>Notification Status:</strong>{" "}
                        {req.notification_sent_to === "admin" ? (
                          <span className="text-blue-600">
                            ✓ Request sent to Admin for inventory approval
                          </span>
                        ) : (
                          <span className="text-green-600">
                            ✓ Request sent to {req.donor_matches_count || 0} Donor(s)
                          </span>
                        )}
                      </p>
                      {req.notification_sent_to === "admin" && (
                        <p className="text-xs text-blue-600 mt-1">
                          Blood is available in inventory. Admin will review and approve your request.
                        </p>
                      )}
                      {req.notification_sent_to === "donors" && (
                        <p className="text-xs text-green-600 mt-1">
                          Blood was not available in inventory. Donors have been notified and can respond to your request.
                        </p>
                      )}
                    </div>

                    {/* Matched Donors Section */}
                    {req.notification_sent_to === "donors" && (
                      <div className="mt-4">
                        <h4 className="font-semibold mb-2">
                          Matched Donors ({matchedDonors[req.id]?.length || req.donor_matches_count || 0})
                        </h4>
                        {matchedDonors[req.id] !== undefined ? (
                          matchedDonors[req.id] && matchedDonors[req.id].length > 0 ? (
                            <div className="space-y-2">
                              {matchedDonors[req.id].map((donor, index) => (
                                <div
                                  key={donor.donor_id || index}
                                  className="bg-gray-50 p-3 rounded border border-gray-200"
                                >
                                  <div className="flex justify-between items-center">
                                    <div>
                                      <p className="font-medium">
                                        {donor.donor_name || "Unknown Donor"}
                                      </p>
                                      {donor.donor_location && (
                                        <p className="text-sm text-gray-600">
                                          Location: {donor.donor_location}
                                        </p>
                                      )}
                                      {donor.distance_km !== null && donor.distance_km !== undefined && (
                                        <p className="text-sm text-gray-600">
                                          Distance: {donor.distance_km} km
                                        </p>
                                      )}
                                    </div>
                                    <div className="text-right">
                                      <p className="text-sm">
                                        <strong>Status:</strong>{" "}
                                        <span className={getStatusColor(donor.status)}>
                                          {donor.status}
                                        </span>
                                      </p>
                                      {donor.match_score && (
                                        <div>
                                          <p className="text-xs font-semibold text-gray-700">
                                            Match Score: {donor.match_score}/100
                                          </p>
                                          <div className="w-20 h-2 bg-gray-200 rounded-full mt-1">
                                            <div
                                              className="h-2 rounded-full bg-blue-500"
                                              style={{
                                                width: `${donor.match_score}%`,
                                              }}
                                            ></div>
                                          </div>
                                        </div>
                                      )}
                                    </div>
                                  </div>
                                  {donor.scheduled_at && (
                                    <p className="text-sm text-gray-600 mt-1">
                                      Scheduled:{" "}
                                      {new Date(donor.scheduled_at).toLocaleString()}
                                    </p>
                                  )}
                                </div>
                              ))}
                            </div>
                          ) : (
                            <p className="text-gray-500 text-sm">
                              No donors matched yet. The system is still processing donor matches.
                            </p>
                          )
                        ) : (
                          <p className="text-gray-500 text-sm italic">
                            Loading matched donors...
                          </p>
                        )}
                      </div>
                    )}
                    {req.notification_sent_to === "admin" && (
                      <div className="mt-4 p-3 rounded-md bg-gray-50 border border-gray-200">
                        <p className="text-sm text-gray-600">
                          This request was sent to admin for inventory approval.
                        </p>
                      </div>
                    )}
                  </div>
                )}
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
};

export default ReceiverSearch;
