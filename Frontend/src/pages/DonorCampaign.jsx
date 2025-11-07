import { useState, useEffect } from "react";
import api from "../api/axios";

export default function ActiveCampaigns() {
  const [campaigns, setCampaigns] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    api.get('/active-campaigns') // Only ongoing campaigns
      .then(res => {
        setCampaigns(res.data);
        setLoading(false);
      })
      .catch(() => {
        alert('Failed to load campaigns');
        setLoading(false);
      });
  }, []);

  if (loading) return <div className="p-6">Loading active campaigns...</div>;

  return (
    <div className="p-6 max-w-4xl mx-auto">
      <h1 className="text-2xl font-bold mb-6">Active Blood Donation Campaigns</h1>
      
      {campaigns.length === 0 ? (
        <div className="text-center py-10 text-gray-500">
          <p>No active campaigns at the moment.</p>
        </div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
          {campaigns.map(campaign => (
            <div key={campaign.id} className="border rounded-lg p-5 shadow">
              <div className="flex justify-between items-start">
                <div>
                  <h3 className="text-xl font-bold text-gray-800">{campaign.location}</h3>
                  <p className="text-gray-600 mt-1">📅 {campaign.date}</p>
                  <p className="text-sm text-gray-500 mt-2">{campaign.description}</p>
                  <span className="inline-block bg-green-100 text-green-800 text-xs font-semibold px-2 py-1 rounded-full mt-2">
                    Ongoing
                  </span>
                </div>
                <button
                  onClick={() => alert(`Campaign ID: ${campaign.id}\nJoining this campaign...`)}
                  className="bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-4 rounded"
                >
                  Join
                </button>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}