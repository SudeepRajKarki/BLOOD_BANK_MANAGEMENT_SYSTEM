import React, { useState, useEffect } from "react";
import axios from "axios";

const bloodTypes = ["A+", "A-", "B+", "B-", "AB+", "AB-", "O+", "O-"];

const ReceiverSearch = () => {
  const [bloodType, setBloodType] = useState("");
  const [quantity, setQuantity] = useState("");
  const [reason, setReason] = useState("");
  const [location, setLocation] = useState("");
  const [requests, setRequests] = useState([]);
  const [message, setMessage] = useState("");

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

  useEffect(() => {
    fetchRequests();
  }, []);

  const handleRequest = async () => {
    if (!bloodType || !quantity || !reason) {
      setMessage("Please fill all required fields!");
      return;
    }

    try {
      const res = await axios.post(
        "http://localhost:8000/api/request",
        {
          blood_type: bloodType,
          quantity_ml: parseInt(quantity),
          reason,
          location,
        },
        {
          headers: { Authorization: `Bearer ${localStorage.getItem("token")}` },
        }
      );

      setMessage(
        res.data.inventory_available
          ? "Request sent to admin for approval!"
          : "Inventory insufficient! Donor request sent."
      );

      // Refresh requests list
      fetchRequests();

      // reset fields
      setBloodType("");
      setQuantity("");
      setReason("");
      setLocation("");
    } catch (err) {
      console.error(err);
      if (err.response?.status === 403) {
        setMessage("You are not authorized to make requests.");
      } else {
        setMessage("Something went wrong. Try again.");
      }
    }
  };

  return (
    <div className="p-4 max-w-xl mx-auto">
      <h2 className="text-xl font-bold mb-4">Request Blood</h2>
      <div className="mb-2">
        <label>Blood Type:</label>
        <select
          className="border p-2 w-full"
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
      <div className="mb-2">
        <label>Quantity (ml):</label>
        <input
          type="number"
          className="border p-2 w-full"
          value={quantity}
          onChange={(e) => setQuantity(e.target.value)}
        />
      </div>
      <div className="mb-2">
        <label>Reason:</label>
        <textarea
          className="border p-2 w-full"
          value={reason}
          onChange={(e) => setReason(e.target.value)}
        ></textarea>
      </div>
      <div className="mb-4">
        <label>Location:</label>
        <input
          type="text"
          className="border p-2 w-full"
          value={location}
          onChange={(e) => setLocation(e.target.value)}
        />
      </div>
      <button
        className="bg-blue-500 text-white p-2 rounded w-full"
        onClick={handleRequest}
      >
        Submit Request
      </button>
      {message && <p className="mt-2 text-red-500">{message}</p>}

      <h3 className="text-lg font-bold mt-6 mb-2">Your Requests</h3>
      <ul>
        {requests.map((req) => (
          <li key={req.id} className="border p-2 mb-2 rounded">
            <p>
              <strong>Blood Type:</strong> {req.blood_type}
            </p>
            <p>
              <strong>Quantity:</strong> {req.quantity_ml} ml
            </p>
            <p>
              <strong>Reason:</strong> {req.reason}
            </p>
            <p>
              <strong>Priority:</strong> {req.priority}
            </p>
            <p>
              <strong>Status:</strong> {req.status}
            </p>
            {req.location && (
              <p>
                <strong>Location:</strong> {req.location}
              </p>
            )}
          </li>
        ))}
      </ul>
    </div>
  );
};

export default ReceiverSearch;
