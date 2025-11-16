import { useState, useContext } from "react";
import { useNavigate, Link } from "react-router-dom";
import api from "../api/axios";
import { AuthContext } from "../Context/AuthContext";
import toast, { Toaster } from "react-hot-toast";

export default function Login() {
  const navigate = useNavigate();
  const { login } = useContext(AuthContext);
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [loading, setLoading] = useState(false);
  const [showPassword, setShowPassword] = useState(false);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);

    try {
      const res = await api.post("/login", { email, password });
      const { token, user } = res.data;
      login(token, user.role);

      // ✅ Save a flag to show toast only once
      localStorage.setItem("justLoggedIn", "true");

      let redirectTo = "/";
      if (user.role === "admin") redirectTo = "/admind";
      else if (user.role === "donor") redirectTo = "/donordashboard";
      else if (user.role === "receiver") redirectTo = "/receiverd";

      // redirect after login
      setTimeout(() => navigate(redirectTo, { replace: true }), 500);
    } catch (err) {
      const message =
        err.response?.data?.message ||
        err.response?.data?.error ||
        "Invalid credentials or server error.";
      toast.error(message);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="flex items-center justify-center min-h-[calc(100vh-80px)] bg-[#DAADAD] font-sans text-gray-800 pb-12">
      <Toaster position="top-center" reverseOrder={false} />

      <div className="relative w-full max-w-md p-8 bg-gray-200 rounded-3xl shadow-lg">
        {loading && (
          <div className="absolute inset-0 flex items-center justify-center bg-white/70 backdrop-blur-sm z-10 rounded-3xl">
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
              <span className="font-medium">Signing in...</span>
            </div>
          </div>
        )}

        <h2 className="text-3xl font-bold text-center mb-6 text-red-600">
          Login
        </h2>

        <form onSubmit={handleSubmit} className="space-y-4">
          <input
            type="email"
            placeholder="Email"
            value={email}
            onChange={(e) => setEmail(e.target.value)}
            required
            className="w-full px-4 py-2 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-red-500 bg-white"
          />

          <div className="relative">
            <input
              type={showPassword ? "text" : "password"}
              placeholder="Password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              required
              className="w-full px-4 py-2 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-red-500 bg-white pr-10"
            />
            <button
              type="button"
              onClick={() => setShowPassword(!showPassword)}
              className="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-600 hover:text-red-600 focus:outline-none"
            >
              {showPassword ? "Hide" : "Show"}
            </button>
          </div>

          <button
            type="submit"
            disabled={loading}
            className={`w-full py-3 font-semibold rounded-xl shadow-md transition-transform transform ${
              loading
                ? "bg-red-400 cursor-not-allowed"
                : "bg-red-600 hover:bg-red-700 hover:scale-105 text-white"
            }`}
          >
            {loading ? "Logging In..." : "Login"}
          </button>
        </form>

        <div className="mt-6 text-sm text-center text-gray-700">
          <Link
            to="/forgot-password"
            className="text-red-600 hover:underline font-medium"
          >
            Forgot Password?
          </Link>
          <p className="mt-2">
            No account yet?{" "}
            <Link
              to="/register"
              className="text-red-600 hover:underline font-medium"
            >
              Register Now
            </Link>
          </p>
        </div>
      </div>
    </div>
  );
}
