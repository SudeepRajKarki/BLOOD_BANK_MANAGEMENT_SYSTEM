import { useEffect, useState } from "react";
import { useSearchParams, useNavigate } from "react-router-dom";
import api from "../api/axios";

export default function VerifyEmail() {
  const [searchParams] = useSearchParams();
  const [message, setMessage] = useState("Verifying your email...");
  const navigate = useNavigate();

  useEffect(() => {
    const token = searchParams.get("token");
    if (token) {
      api
        .get(`/verify-email?token=${token}`)
        .then((res) => setMessage(res.data.message))
        .catch(() => setMessage("Invalid or expired verification link."));
    } else {
      setMessage("No verification token found.");
    }
  }, []);

  return (
    <div className="flex items-center justify-center min-h-screen bg-[#DAADAD] font-sans text-gray-800">
      <div className="w-full max-w-md bg-gray-200 rounded-3xl shadow-lg p-8 text-center">
        <h2 className="text-3xl font-bold mb-6 text-red-600">Email Verification</h2>

        <p
          className={`text-lg mb-8 ${
            message.includes("Invalid") || message.includes("expired")
              ? "text-red-700"
              : "text-green-700"
          }`}
        >
          {message}
        </p>

        <button
          onClick={() => navigate("/login")}
          className="px-8 py-3 bg-red-600 text-white rounded-xl font-semibold shadow-md hover:bg-red-700 hover:scale-105 transition-transform"
        >
          Go to Login
        </button>
      </div>
    </div>
  );
}
