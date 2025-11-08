import React, { useState, useEffect,useContext } from "react";
import { useNavigate } from "react-router-dom";
import api from "../api/axios"; 
import {AuthContext} from '../Context/AuthContext'

export default function Profile() {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);
  const navigate = useNavigate();
  const { updateRole } = useContext(AuthContext);

  useEffect(() => {
    const token = localStorage.getItem("token");
    if (!token) {
      navigate("/login");
      return;
    }

    api
      .get('/profile')
      .then((res) => {
        setUser(res.data.user);
        setLoading(false);
      })
      .catch(() => {
        localStorage.removeItem("token");
        navigate("/login");
      });
  }, [navigate]);

  const handleRoleSwitch = async (newRole) => {
    if (!window.confirm(`Switch to ${newRole}? You’ll only see ${newRole} features.`)) return;

    try {
      await api.post('/profile/switch-role', { role: newRole }); 
      updateRole(newRole); 
      setUser((prev) => ({ ...prev, role: newRole }));
      alert("Role switched successfully!");
    } catch (err) {
      alert("Failed to switch role. Please try again.");
    }
  };

  const handleDeleteAccount = async () => {
  const password = prompt("⚠️ Enter your password to confirm account deletion:");
  if (!password) return;

  try {
    await api.post('/profile/delete-account', { password }); 

    localStorage.removeItem("token");
    alert("Your account has been deleted.");
    navigate("/");
   } catch (err) {
    const message = err.response?.data?.message || "Failed to delete account.";
    alert(message);
    }
  };
  if (loading) return <div className="p-6 text-center">Loading profile...</div>;

  return (
  <div className="max-w-lg mx-auto p-6">
    <h1 className="text-2xl font-bold mb-6">Your Profile</h1>
    <div className="bg-white p-5 rounded shadow">
      <div className="space-y-4">
        <InfoItem label="Name" value={user.name} />
        <InfoItem label="Email" value={user.email} />
        <InfoItem label="Blood Type" value={user.blood_type || "Not set"} />

        <InfoItem 
          label="Role" 
          value={user.role.charAt(0).toUpperCase() + user.role.slice(1)} 
        />

        {user.role !== 'admin' && (
          <div className="pt-4 border-t">
            <h3 className="font-medium mb-2">Switch Role</h3>
            {user.role === "donor" ? (
              <button
                onClick={() => handleRoleSwitch("receiver")}
                className="text-blue-600 hover:underline"
              >
                Become a Receiver
              </button>
            ) : (
              <button
                onClick={() => handleRoleSwitch("donor")}
                className="text-blue-600 hover:underline"
              >
                Become a Donor
              </button>
            )}
          </div>
        )}
      </div>

      {user.role !== 'admin' && (
        <div className="mt-8 pt-6 border-t border-red-200">
          <h3 className="text-red-600 font-semibold mb-2">Delete Account</h3>
          <p className="text-sm text-gray-600 mb-3">
            Permanently delete your account and all your data.
          </p>
          <button
            onClick={handleDeleteAccount}
            className="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600 text-sm"
          >
            Delete My Account
          </button>
        </div>
      )}
     </div>
    </div>
   );
 }

function InfoItem({ label, value }) {
  return (
    <div>
      <span className="text-gray-500 text-sm">{label}:</span>{" "}
      <span className="font-medium">{value}</span>
    </div>
  );
}