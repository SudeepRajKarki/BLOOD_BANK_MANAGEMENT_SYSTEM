import { useState, useEffect, useContext } from "react";
import api from "../api/axios";
import { AuthContext } from "../Context/AuthContext";
import toast, { Toaster } from "react-hot-toast";

export default function DonorCampaigns() {
  const { user } = useContext(AuthContext);
  const [campaigns, setCampaigns] = useState([]);
  const [loading, setLoading] = useState(true);
  const [registeredCampaigns, setRegisteredCampaigns] = useState([]);
  const [selectedCampaign, setSelectedCampaign] = useState(null);
  const [showPopup, setShowPopup] = useState(false);
  const [quantity, setQuantity] = useState(450);

  const userBloodType = user?.blood_type || "";

  useEffect(() => {
    fetchCampaigns();
    fetchRegisteredCampaigns();
  }, []);

  const fetchCampaigns = async () => {
    try {
      const res = await api.get("/active-campaigns");
      setCampaigns(res.data);
    } catch (err) {
      console.error(err);
      toast.error("❌ Failed to load campaigns.");
    } finally {
      setLoading(false);
    }
  };

  const fetchRegisteredCampaigns = async () => {
    try {
      const res = await api.get("/donations");
      setRegisteredCampaigns(res.data.map((r) => r.campaign_id));
    } catch (err) {
      console.error(err);
    }
  };

  const openPopup = (campaign) => {
    setSelectedCampaign(campaign);
    setQuantity(450);
    setShowPopup(true);
  };

  const closePopup = () => {
    setSelectedCampaign(null);
    setShowPopup(false);
  };

  const handleConfirmRegister = async () => {
    if (!selectedCampaign) return;

    try {
      await api.post("/donate", {
        campaign_id: selectedCampaign.id,
        blood_type: userBloodType,
        quantity_ml: quantity,
      });

      toast.success(`✅ Registered for campaign #${selectedCampaign.id}!`);
      setRegisteredCampaigns([...registeredCampaigns, selectedCampaign.id]);
      closePopup();
    } catch (err) {
      console.error(err.response?.data || err);
      const errorMessage = err.response?.data?.message || "Failed to register. Try again later.";
      toast.error(`❌ ${errorMessage}`);
    }
  };

  if (loading) return <p className="p-6">Loading campaigns...</p>;

  return (
    <div className="max-w-4xl mx-auto mt-10 p-4 space-y-6">
      <Toaster position="top-center" reverseOrder={false} />
      <h1 className="text-2xl font-bold mb-4">🩸 Ongoing Campaigns</h1>

      {campaigns.length === 0 ? (
        <p>No ongoing campaigns at the moment.</p>
      ) : (
        <div className="grid gap-4 md:grid-cols-2">
          {campaigns.map((c) => (
            <div
              key={c.id}
              className="p-4 rounded-xl shadow bg-white flex flex-col justify-between"
            >
              <div>
                <h2 className="font-semibold text-lg">Location: {c.location}</h2>
                <p className="text-sm text-gray-600">Date: {c.date}</p>
                {c.description && (
                  <p className="text-sm text-gray-500 mt-1">{c.description}</p>
                )}
              </div>

              {registeredCampaigns.includes(c.id) ? (
                <button
                  disabled
                  className="mt-4 bg-gray-400 text-white py-2 px-4 rounded-lg cursor-not-allowed"
                >
                  Already Registered
                </button>
              ) : (
                <button
                  onClick={() => openPopup(c)}
                  className="mt-4 bg-red-600 hover:bg-red-700 text-white py-2 px-4 rounded-lg"
                >
                  Register to Donate
                </button>
              )}
            </div>
          ))}
        </div>
      )}

      {showPopup && selectedCampaign && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50">
          <div className="bg-white p-6 rounded-lg w-96 shadow-lg relative">
            <h2 className="text-xl font-bold mb-3">Confirm Registration</h2>
            <p className="text-gray-700 mb-2">
              🩸 <b>Campaign:</b> {selectedCampaign.location}
            </p>
            <p className="text-gray-700 mb-2">
              📅 <b>Date:</b> {selectedCampaign.date}
            </p>
            {selectedCampaign.description && (
              <p className="text-gray-700 mb-4">
                💬 {selectedCampaign.description}
              </p>
            )}

            <div className="mb-3">
              <label className="block text-gray-700 mb-1">Quantity (ml):</label>
              <input
                type="number"
                value={quantity}
                onChange={(e) => setQuantity(+e.target.value)}
                min={100}
                max={500}
                className="border rounded w-full px-2 py-1"
              />
            </div>

            <div className="flex justify-end gap-3 mt-5">
              <button
                onClick={closePopup}
                className="bg-gray-400 hover:bg-gray-500 text-white px-4 py-2 rounded"
              >
                Cancel
              </button>
              <button
                onClick={handleConfirmRegister}
                className="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded"
              >
                Confirm
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
