import React, { useState } from "react";
import axios from "axios";
import toast, { Toaster } from "react-hot-toast";

const bloodTypes = ["A+", "A-", "B+", "B-", "AB+", "AB-", "O+", "O-"];
const locations = [
  "Kathmandu", "Lalitpur", "Bhaktapur", "Pokhara", "Butwal", "Biratnagar",
  "Hetauda", "Dharan", "Janakpur", "Birgunj", "Nepalgunj", "Mahendranagar", "Chitwan"
];

const ReceiverSearch = () => {
  const [bloodType, setBloodType] = useState("");
  const [quantity, setQuantity] = useState("");
  const [reason, setReason] = useState("");
  const [location, setLocation] = useState("");
  const [loading, setLoading] = useState(false);

  const handleRequest = async () => {
    if (!bloodType || !quantity || !reason) {
      toast.error("Please fill all required fields!");
      return;
    }
    if (parseInt(quantity) < 1) {
      toast.error("Quantity must be at least 1 ml!");
      return;
    }

    setLoading(true);
    try {
      const res = await axios.post(
        "http://localhost:8000/api/request",
        {
          blood_type: bloodType,
          quantity_ml: parseInt(quantity),
          reason,
          location: location || null,
        },
        { headers: { Authorization: `Bearer ${localStorage.getItem("token")}` } }
      );

      toast.success(res.data.message || "Request created successfully!");
      setBloodType("");
      setQuantity("");
      setReason("");
      setLocation("");
    } catch (err) {
      console.error(err);
      toast.error(
        err.response?.data?.message || "Something went wrong. Please try again."
      );
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="p-6 max-w-4xl mx-auto">
      <Toaster position="top-center" reverseOrder={false} />
      <h2 className="text-3xl font-bold mb-6 text-center">Request Blood</h2>

      <div className="bg-white rounded-xl shadow-md p-6 mb-8 border border-gray-100">
        <div className="mb-4">
          <label className="block text-sm font-medium text-gray-700 mb-1">
            Blood Type <span className="text-red-500">*</span>
          </label>
          <select
            className="w-full p-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            value={bloodType}
            onChange={(e) => setBloodType(e.target.value)}
          >
            <option value="">Select Blood Type</option>
            {bloodTypes.map((type) => (
              <option key={type} value={type}>{type}</option>
            ))}
          </select>
        </div>

        <div className="mb-4">
          <label className="block text-sm font-medium text-gray-700 mb-1">
            Quantity (ml) <span className="text-red-500">*</span>
          </label>
          <input
            type="number"
            min="1"
            className="w-full p-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            value={quantity}
            onChange={(e) => setQuantity(e.target.value)}
            placeholder="e.g., 150"
          />
        </div>

        <div className="mb-4">
          <label className="block text-sm font-medium text-gray-700 mb-1">
            Reason <span className="text-red-500">*</span>
          </label>
          <textarea
            className="w-full p-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            value={reason}
            onChange={(e) => setReason(e.target.value)}
            rows="3"
            placeholder="e.g., surgery, accident..."
          />
          <p className="text-xs text-gray-400 mt-1">
            The system automatically determines priority based on reason.
          </p>
        </div>

        <div className="mb-4">
          <label className="block text-sm font-medium text-gray-700 mb-1">Location</label>
          <select
            className="w-full p-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            value={location}
            onChange={(e) => setLocation(e.target.value)}
          >
            <option value="">Select Location</option>
            {locations.map((loc) => (
              <option key={loc} value={loc}>{loc}</option>
            ))}
          </select>
          <p className="text-xs text-gray-400 mt-1">Helps match nearby donors.</p>
        </div>

        <button
          className={`w-full p-3 rounded-xl font-semibold text-white bg-red-500 hover:bg-red-600 transition-colors ${loading ? "opacity-50 cursor-not-allowed" : ""}`}
          onClick={handleRequest}
          disabled={loading}
        >
          {loading ? "Submitting..." : "Submit Request"}
        </button>
      </div>
    </div>
  );
};

export default ReceiverSearch;
