import React, { useState, useEffect } from "react";
import axios from "axios";
import toast, { Toaster } from "react-hot-toast";

const Receiver = () => {
  const [requests, setRequests] = useState([]);
  const [matchedDonors, setMatchedDonors] = useState({});
  const [expandedRequest, setExpandedRequest] = useState(null);

  const fetchRequests = async () => {
    try {
      const res = await axios.get("http://localhost:8000/api/requests", {
        headers: { Authorization: `Bearer ${localStorage.getItem("token")}` },
      });
      setRequests(res.data);
    } catch (err) {
      console.error(err);
      toast.error("Failed to fetch your requests.");
    }
  };

  const fetchMatchedDonors = async (requestId) => {
    try {
      const res = await axios.get(
        `http://localhost:8000/api/requests/${requestId}/matched-donors`,
        { headers: { Authorization: `Bearer ${localStorage.getItem("token")}` } }
      );
      setMatchedDonors((prev) => ({ ...prev, [requestId]: res.data.matched_donors }));
      // Update request with donation progress if available
      if (res.data.donated_quantity_ml !== undefined) {
        setRequests((prev) => prev.map(req => 
          req.id === requestId 
            ? { ...req, donated_quantity_ml: res.data.donated_quantity_ml, remaining_quantity_ml: res.data.remaining_quantity_ml }
            : req
        ));
      }
    } catch (err) {
      console.error(err);
      toast.error("Failed to fetch matched donors.");
    }
  };

  useEffect(() => {
    fetchRequests();
  }, []);

  const toggleRequestDetails = (requestId) => {
    if (expandedRequest === requestId) {
      setExpandedRequest(null);
    } else {
      setExpandedRequest(requestId);
      if (!matchedDonors[requestId]) fetchMatchedDonors(requestId);
    }
  };

  const getPriorityColor = (priority) => {
    switch (priority) {
      case "High": return "text-red-600 font-bold";
      case "Medium": return "text-yellow-600 font-semibold";
      case "Low": return "text-green-600";
      default: return "text-gray-600";
    }
  };

  const getStatusColor = (status) => {
    switch (status) {
      case "Approved": return "text-green-600 font-semibold";
      case "Rejected": return "text-red-600 font-semibold";
      case "Pending": return "text-yellow-600 font-semibold";
      default: return "text-gray-600";
    }
  };

  return (
    <div className="p-6 max-w-4xl mx-auto">
      <Toaster position="top-center" reverseOrder={false} />

      <h2 className="text-3xl font-bold mb-6 text-center">Your Blood Requests</h2>

      {requests.length === 0 ? (
        <p className="text-gray-500 text-center py-8">
          No requests yet. Create your first blood request in the form above.
        </p>
      ) : (
        <div className="space-y-4">
          {requests.map((req) => (
            <div
              key={req.id}
              className="border border-gray-200 rounded-xl p-4 hover:shadow-md transition-shadow"
            >
              <div className="flex justify-between items-start">
                <div className="flex-1">
                  <div className="flex items-center gap-4 mb-2">
                    <p className="text-lg font-semibold">{req.blood_type} : {req.quantity_ml} ml</p>
                    <span className={getPriorityColor(req.priority)}>Priority: {req.priority}</span>
                    <span className={getStatusColor(req.status)}>Status: {req.status}</span>
                  </div>
                  {req.notification_sent_to === "donors" && req.donated_quantity_ml !== undefined && (
                    <div className="mb-2 p-2 bg-green-50 border border-green-200 rounded">
                      <p className="text-sm font-semibold text-green-800">
                        Donation Progress: {req.donated_quantity_ml} ml donated / {req.quantity_ml} ml requested
                      </p>
                      <p className="text-xs text-green-700">
                        Remaining: {req.remaining_quantity_ml} ml
                      </p>
                      <div className="w-full bg-gray-200 rounded-full h-2 mt-1">
                        <div 
                          className="bg-green-600 h-2 rounded-full transition-all" 
                          style={{ width: `${Math.min(100, (req.donated_quantity_ml / req.quantity_ml) * 100)}%` }}
                        ></div>
                      </div>
                    </div>
                  )}
                  <p className="text-sm text-gray-600 mb-1"><strong>Reason:</strong> {req.reason}</p>
                  {req.location && <p className="text-sm text-gray-600 mb-1"><strong>Location:</strong> {req.location}</p>}
                  <p className="text-xs text-gray-400">Requested on: {new Date(req.created_at).toLocaleString()}</p>
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
                    <div><strong>Request ID:</strong> #{req.id}</div>
                    <div><strong>Priority:</strong> <span className={getPriorityColor(req.priority)}>{req.priority}</span></div>
                    <div><strong>Status:</strong> <span className={getStatusColor(req.status)}>{req.status}</span></div>
                    {req.location && <div><strong>Location:</strong> {req.location}</div>}
                  </div>

                  <div className="mb-4 p-3 rounded-lg bg-blue-50 border border-blue-200">
                    <p className="text-sm font-semibold text-blue-800">
                      <strong>Notification Status:</strong>{" "}
                      {req.notification_sent_to === "admin" ? (
                        <span className="text-blue-600">✓ Sent to Admin for approval</span>
                      ) : (
                        <span className="text-green-600">✓ Sent to {req.donor_matches_count || 0} Donor(s)</span>
                      )}
                    </p>
                    {req.notification_sent_to === "admin" && <p className="text-xs text-blue-600 mt-1">Admin will review your request.</p>}
                    {req.notification_sent_to === "donors" && <p className="text-xs text-green-600 mt-1">Donors have been notified.</p>}
                  </div>

                  {req.notification_sent_to === "donors" && (
                    <div className="mt-4">
                      <h4 className="font-semibold mb-2">
                        Matched Donors ({matchedDonors[req.id]?.length || req.donor_matches_count || 0})
                      </h4>
                      {req.donated_quantity_ml !== undefined && (
                        <div className="mb-3 p-2 bg-blue-50 border border-blue-200 rounded">
                          <p className="text-sm font-semibold text-blue-800">
                            Progress: {req.donated_quantity_ml} ml / {req.quantity_ml} ml
                          </p>
                          <p className="text-xs text-blue-700">
                            Still needed: {req.remaining_quantity_ml} ml
                          </p>
                        </div>
                      )}
                      {matchedDonors[req.id] !== undefined ? (
                        matchedDonors[req.id]?.length > 0 ? (
                          <div className="space-y-2">
                            {matchedDonors[req.id].map((donor, index) => (
                              <div key={donor.donor_id || index} className="bg-gray-50 p-3 rounded-xl border border-gray-200">
                                <div className="flex justify-between items-center">
                                  <div>
                                    <p className="font-medium">{donor.donor_name || "Unknown Donor"}</p>
                                    {donor.donor_email && <p className="text-sm text-gray-600">Email: {donor.donor_email}</p>}
                                    {donor.donor_phone && <p className="text-sm text-gray-600">Phone: {donor.donor_phone}</p>}
                                    {donor.donor_location && <p className="text-sm text-gray-600">Location: {donor.donor_location}</p>}
                                    {donor.distance_km != null && <p className="text-sm text-gray-600">Distance: {donor.distance_km} km</p>}
                                  </div>
                                  <div className="text-right">
                                    <p className="text-sm"><strong>Status:</strong> <span className={getStatusColor(donor.status)}>{donor.status}</span></p>
                                    {donor.match_score && (
                                      <div>
                                        <p className="text-xs font-semibold text-gray-700">Match Score: {donor.match_score}/100</p>
                                        <div className="w-20 h-2 bg-gray-200 rounded-full mt-1">
                                          <div className="h-2 rounded-full bg-blue-500" style={{ width: `${donor.match_score}%` }}></div>
                                        </div>
                                      </div>
                                    )}
                                  </div>
                                </div>
                                {donor.scheduled_at && <p className="text-sm text-gray-600 mt-1">Scheduled: {new Date(donor.scheduled_at).toLocaleString()}</p>}
                              </div>
                            ))}
                          </div>
                        ) : (
                          <p className="text-gray-500 text-sm">No donors matched yet. System is processing matches.</p>
                        )
                      ) : (
                        <p className="text-gray-500 text-sm italic">Loading matched donors...</p>
                      )}
                    </div>
                  )}
                </div>
              )}
            </div>
          ))}
        </div>
      )}
    </div>
  );
};

export default Receiver;
