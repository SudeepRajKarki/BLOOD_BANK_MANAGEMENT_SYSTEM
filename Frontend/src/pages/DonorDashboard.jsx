import { useState, useEffect } from "react";
import api from "../api/axios";

export default function DonorDashboard() {
  const [matches, setMatches] = useState([]);
  const [campaigns, setCampaigns] = useState([]);
  const [donations, setDonations] = useState([]);
  const [loading, setLoading] = useState(true);
  const [msg, setMsg] = useState("");

  // Fetch donor-related data
  useEffect(() => {
    fetchMatches();
    fetchCampaigns();
    fetchDonations();
  }, []);

  // 1️⃣ Fetch donor matches
  const fetchMatches = async () => {
    try {
      const res = await api.get("/donor/matches");
      setMatches(res.data);
    } catch (err) {
      console.error(err);
      setMsg("Failed to load matches.");
    }
  };

  // 2️⃣ Fetch ongoing campaigns
  const fetchCampaigns = async () => {
    try {
      const res = await api.get("/active-campaigns");
      setCampaigns(res.data);
    } catch (err) {
      console.error(err);
      setMsg("Failed to load campaigns.");
    }
  };

  // 3️⃣ Fetch donor's donation history
  const fetchDonations = async () => {
    try {
      const res = await api.get("/donations");
      setDonations(res.data);
    } catch (err) {
      console.error(err);
      setMsg("Failed to load donation history.");
    } finally {
      setLoading(false);
    }
  };

  // Accept a donor match
  const handleAccept = async (matchId) => {
    try {
      await api.post(`/donor/matches/${matchId}/accept`, {
        quantity_ml: 500, // default donation amount
      });
      setMsg("✅ Match accepted.");
      fetchMatches();
      fetchDonations();
    } catch (err) {
      console.error(err);
      setMsg("❌ Failed to accept match.");
    }
  };

  // Decline a donor match
  const handleDecline = async (matchId) => {
    try {
      await api.post(`/donor/matches/${matchId}/decline`);
      setMsg("❌ Match declined.");
      fetchMatches();
    } catch (err) {
      console.error(err);
      setMsg("❌ Failed to decline match.");
    }
  };

  // Register for a campaign
const handleRegister = async (campaign) => {
  try {
    const payload = {
      campaign_id: campaign.id,            // for campaign donation
      blood_type: selectedBloodType,       // must be valid
      quantity_ml: parseInt(quantity) || 500, // must be integer 250-500
    };

    const res = await api.post("/donate", payload);
    alert("Registered successfully!");
  } catch (err) {
    console.error(err.response?.data); // <-- shows which field failed
    alert("Failed to register: " + (err.response?.data?.message || err.message));
  }
};


  if (loading) return <p className="p-6">Loading donor dashboard...</p>;

  return (
    <div className="max-w-6xl mx-auto mt-10 p-4 space-y-8">
      <h1 className="text-2xl font-bold mb-4">🩸 Donor Dashboard</h1>
      {msg && <p className="text-sm text-red-600">{msg}</p>}

      {/* Matches Section */}
      <div>
        <h2 className="text-xl font-semibold mb-2">Your Matches</h2>
        {matches.length === 0 ? (
          <p>No pending matches.</p>
        ) : (
          <div className="space-y-2">
            {matches.map((m) => (
              <div
                key={m.id}
                className="p-4 bg-white shadow rounded flex justify-between items-center"
              >
                <div>
                  <p>
                    Blood Type: <span className="font-semibold">{m.blood_type}</span>
                  </p>
                  <p>Quantity: {m.quantity_ml} ml</p>
                  <p>Reason: {m.reason}</p>
                  <p>Status: {m.status}</p>
                </div>
                {m.status === "Pending" && (
                  <div className="space-x-2">
                    <button
                      onClick={() => handleAccept(m.id)}
                      className="bg-green-600 text-white px-3 py-1 rounded"
                    >
                      Accept
                    </button>
                    <button
                      onClick={() => handleDecline(m.id)}
                      className="bg-red-600 text-white px-3 py-1 rounded"
                    >
                      Decline
                    </button>
                  </div>
                )}
              </div>
            ))}
          </div>
        )}
      </div>

      {/* Campaigns Section */}
      <div>
        <h2 className="text-xl font-semibold mb-2">Ongoing Campaigns</h2>
        {campaigns.length === 0 ? (
          <p>No ongoing campaigns.</p>
        ) : (
          <div className="grid gap-4 md:grid-cols-2">
            {campaigns.map((c) => (
              <div
                key={c.id}
                className="p-4 bg-white shadow rounded flex flex-col justify-between"
              >
                <div>
                  <h3 className="font-semibold text-lg">{c.location}</h3>
                  <p className="text-sm text-gray-600">Date: {c.date}</p>
                  {c.description && (
                    <p className="text-sm text-gray-500 mt-1">{c.description}</p>
                  )}
                </div>
                <button
                  onClick={() => handleRegisterCampaign(c.id)}
                  className="mt-3 bg-red-600 hover:bg-red-700 text-white py-2 px-4 rounded-lg"
                >
                  Register to Donate
                </button>
              </div>
            ))}
          </div>
        )}
      </div>

      {/* Donation History Section */}
      <div>
        <h2 className="text-xl font-semibold mb-2">Donation History</h2>
        {donations.length === 0 ? (
          <p>No donations yet.</p>
        ) : (
          <div className="space-y-2">
            {donations.map((d) => (
              <div
                key={d.id}
                className="p-4 bg-white shadow rounded flex justify-between items-center"
              >
                <div>
                  <p>
                    Blood Type: <span className="font-semibold">{d.blood_type}</span>
                  </p>
                  <p>Quantity: {d.quantity_ml} ml</p>
                  <p>Date: {d.donation_date}</p>
                  {d.campaign_id && <p>Campaign ID: {d.campaign_id}</p>}
                </div>
                <span
                  className={`px-3 py-1 rounded-full text-sm ${
                    d.verified ? "bg-green-100 text-green-800" : "bg-yellow-100 text-yellow-800"
                  }`}
                >
                  {d.verified ? "Verified" : "Pending"}
                </span>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}
