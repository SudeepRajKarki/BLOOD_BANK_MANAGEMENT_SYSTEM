import { useState } from "react";
import api from "../api/axios";
import toast, { Toaster } from "react-hot-toast";

export default function ForgotPassword() {
  const [email, setEmail] = useState("");
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);

    try {
      const res = await api.post("/forgot-password", { email });
      toast.success(res.data.message || "Password reset link sent to your email.");
      setEmail("");
    } catch (err) {
      toast.error(
        err.response?.data?.message || "Error sending password reset email."
      );
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="flex items-center justify-center min-h-screen bg-[#DAADAD] font-sans text-gray-800 relative">
      {/* Toast container */}
      <Toaster position="top-center" reverseOrder={false} />

      {/* Loading overlay */}
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
            <span className="font-medium">Sending reset link...</span>
          </div>
        </div>
      )}

      {/* Card */}
      <div className="w-full max-w-md p-8 bg-gray-200 rounded-3xl shadow-lg">
        <h2 className="text-3xl font-bold text-center mb-4 text-red-600">
          Forgot Password
        </h2>
        <p className="text-gray-700 text-center mb-6 text-sm">
          Enter your registered email address, and we’ll send you a password reset link.
        </p>

        <form onSubmit={handleSubmit} className="space-y-4">
          <input
            type="email"
            placeholder="Enter your email"
            value={email}
            onChange={(e) => setEmail(e.target.value)}
            required
            className="w-full px-4 py-2 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-red-500 bg-white"
          />

          <button
            type="submit"
            disabled={loading}
            className={`w-full py-3 font-semibold rounded-xl shadow-md transition-transform transform ${
              loading
                ? "bg-red-400 cursor-not-allowed"
                : "bg-red-600 hover:bg-red-700 hover:scale-105 text-white"
            }`}
          >
            {loading ? "Sending..." : "Send Reset Link"}
          </button>
        </form>
      </div>
    </div>
  );
}
