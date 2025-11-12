import { useState, useEffect } from "react";
import api from "../api/axios";
import toast, { Toaster } from "react-hot-toast";

export default function DonerRequest() {
  const [matches, setMatches] = useState([]);
  const [loading, setLoading] = useState(true);
  const [expandedMatch, setExpandedMatch] = useState(null);

  useEffect(() => {
    fetchMatches();
  }, []);

  const fetchMatches = async () => {
    try {
      const res = await api.get("/donor-matches");
      setMatches(res.data);
    } catch (err) {
      console.error(err);
      toast.error("Failed to fetch matches.");
    } finally {
      setLoading(false);
    }
  };

  const toggleMatchDetails = (id) => {
    setExpandedMatch(expandedMatch === id ? null : id);
  };

  const handleAccept = async (matchId) => {
    try {
      const res = await api.post(`/donor-matches/${matchId}/accept`);
      const data = res.data;
      
      // Show success message with remaining quantity info if available
      if (data.remaining_quantity_ml !== undefined) {
        if (data.request_fulfilled) {
          toast.success("Match accepted! Request is now fulfilled.");
        } else {
          toast.success(`Match accepted! Remaining quantity needed: ${data.remaining_quantity_ml} ml.`);
        }
      } else {
        toast.success("Match accepted!");
      }
      
      fetchMatches(); // refresh the list
    } catch (err) {
      console.error(err);
      const errorMessage = err.response?.data?.message || err.response?.data?.error || "Failed to accept match.";
      
      // Handle specific error cases
      if (err.response?.data?.error === 'already_fulfilled') {
        toast.error("This request is already fulfilled. No more donations are needed.");
      } else if (err.response?.data?.error === 'ineligible') {
        toast.error(errorMessage);
      } else {
        toast.error(errorMessage);
      }
    }
  };

  const handleDecline = async (matchId) => {
    try {
      await api.post(`/donor-matches/${matchId}/decline`);
      toast.success("Match declined.");
      fetchMatches(); // refresh the list
    } catch (err) {
      console.error(err);
      toast.error("Failed to decline match.");
    }
  };

  if (loading) return <p className="p-6">Loading your matches...</p>;

  return (
    <div className="max-w-6xl mx-auto mt-10 p-4 space-y-6">
      {/* Toast container */}
      <Toaster position="top-center" reverseOrder={false} />

      <h1 className="text-3xl font-bold mb-6 text-red-600">🩸 Your Matches</h1>
      {matches.length === 0 ? (
        <p>No matches found.</p>
      ) : (
        <div className="max-h-[500px] overflow-y-auto space-y-3">
          {matches.map((m) => {
            const isExpanded = expandedMatch === m.id;
            const req = m.request;
            return (
              <div
                key={m.id}
                className="bg-white shadow-md rounded-lg p-4 border border-gray-200"
              >
                <div className="flex justify-between items-center">
                  <div>
                    <p>
                      <strong>Blood Type:</strong> {req?.blood_type || "N/A"}
                    </p>
                    <p>
                      <strong>Quantity:</strong> {req?.quantity_ml || "N/A"} ml
                    </p>
                    {m.donated_quantity_ml !== undefined && (
                      <div className="my-2 p-2 bg-blue-50 border border-blue-200 rounded">
                        <p className="text-sm font-semibold text-blue-800">
                          Donation Progress: {m.donated_quantity_ml} ml donated / {m.requested_quantity_ml} ml requested
                        </p>
                        <p className="text-xs text-blue-700">
                          Remaining: {m.remaining_quantity_ml} ml
                        </p>
                        <div className="w-full bg-gray-200 rounded-full h-2 mt-1">
                          <div 
                            className="bg-blue-600 h-2 rounded-full transition-all" 
                            style={{ width: `${Math.min(100, (m.donated_quantity_ml / m.requested_quantity_ml) * 100)}%` }}
                          ></div>
                        </div>
                      </div>
                    )}
                    <p>
                      <strong>Status:</strong>{" "}
                      <span
                        className={
                          m.status === "Accepted"
                            ? "text-green-600 font-semibold"
                            : m.status === "Declined"
                              ? "text-red-600 font-semibold"
                              : "text-yellow-600 font-medium"
                        }
                      >
                        {m.status || "N/A"}
                      </span>
                    </p>
                    <p>
                      <strong>Reason:</strong> {req?.reason || "N/A"}
                    </p>
                  </div>

                  <button
                    onClick={() => toggleMatchDetails(m.id)}
                    className="text-blue-600 hover:underline text-sm"
                  >
                    {isExpanded ? "Hide Details ▲" : "Show Details ▼"}
                  </button>
                </div>

                {isExpanded && (
                  <div className="mt-2 text-sm text-gray-700 border-t border-gray-200 pt-2 space-y-1">
                    <p>
                      <strong>Requested By:</strong> {req?.receiver?.name || "Unknown"}
                    </p>
                    <p>
                      <strong>Receiver Email:</strong> {req?.receiver?.email || "N/A"}
                    </p>
                    <p>
                      <strong>Receiver Phone:</strong> {req?.receiver?.phone || "N/A"}
                    </p>
                    <p>
                      <strong>Match Created At:</strong>{" "}
                      {new Date(m.created_at).toLocaleString()}
                    </p>

                    {/* Accept/Decline buttons only if status is Pending */}
                    {m.status === "Pending" && (
                      <div className="flex gap-2 mt-2">
                        <button
                          onClick={() => handleAccept(m.id)}
                          className="bg-green-600 hover:bg-green-700 text-white px-4 py-1 rounded"
                        >
                          Accept
                        </button>
                        <button
                          onClick={() => handleDecline(m.id)}
                          className="bg-red-600 hover:bg-red-700 text-white px-4 py-1 rounded"
                        >
                          Decline
                        </button>
                      </div>
                    )}
                  </div>
                )}
              </div>
            );
          })}
        </div>
      )}
    </div>
  );
}
