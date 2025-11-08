import { useState, useContext } from "react";
import { useNavigate, Link } from "react-router-dom";
import api from "../api/axios";
import { AuthContext } from "../Context/AuthContext";

export default function Login() {
  const navigate = useNavigate();
  const { login } = useContext(AuthContext);
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState("");

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError(""); 
    setIsLoading(true);

    try {
      const res = await api.post("/login", { email, password });
      login(res.data.token, res.data.user.role);
      setTimeout(() => {
        navigate("/", { replace: true });
      }, 300);

    } catch (err) {
      setError(
        err.response?.data?.message || "Invalid credentials or server error."
      );
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className="flex items-center justify-center min-h-screen bg-gray-50 p-4">
      <div className="w-full max-w-md p-8 bg-white rounded-xl shadow-lg border border-gray-100">
        <h2 className="text-2xl font-bold text-center text-gray-800 mb-6">Login</h2>
        {error && (
          <div className="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
            {error}
          </div>
        )}

        {/* Form */}
        <form onSubmit={handleSubmit} className="space-y-4">
          <div>
            <input
              type="email"
              placeholder="Email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              className={`w-full px-4 py-3 border rounded-lg focus:ring-2 focus:outline-none transition ${
                error
                  ? "border-red-300 focus:ring-red-500"
                  : "border-gray-300 focus:ring-blue-500"
              }`}
              required
            />
          </div>

          <div>
            <input
              type="password"
              placeholder="Password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              className={`w-full px-4 py-3 border rounded-lg focus:ring-2 focus:outline-none transition ${
                error
                  ? "border-red-300 focus:ring-red-500"
                  : "border-gray-300 focus:ring-blue-500"
              }`}
              required
            />
          </div>

          <button
            type="submit"
            disabled={isLoading}
            className={`w-full py-3 font-semibold rounded-lg transition-colors ${
              isLoading
                ? "bg-blue-400 cursor-not-allowed"
                : "bg-blue-500 hover:bg-blue-600 text-white"
            }`}
          >
            {isLoading ? (
              <span className="flex items-center justify-center">
                <svg
                  className="animate-spin -ml-1 mr-2 h-4 w-4 text-white"
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
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.644z"
                  ></path>
                </svg>
                Signing In...
              </span>
            ) : (
              "Login"
            )}
          </button>
        </form>

        {/* Links */}
        <div className="mt-6 text-center space-y-3">
          <div>
            <Link
              to="/forgot-password"
              className="text-blue-500 hover:text-blue-700 text-sm font-medium"
            >
              Forgot Password?
            </Link>
          </div>
          <div className="text-sm text-gray-600">
            No Account Yet?{" "}
            <Link
              to="/register"
              className="text-blue-500 hover:text-blue-700 font-medium"
            >
              Register Now
            </Link>
          </div>
        </div>
      </div>
    </div>
  );
}