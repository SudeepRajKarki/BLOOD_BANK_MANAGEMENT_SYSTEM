import React, { useState, useEffect, useContext } from "react";
import { useNavigate } from "react-router-dom";
import api from "../api/axios";
import { AuthContext } from '../Context/AuthContext';
import toast, { Toaster } from "react-hot-toast";

export default function Profile() {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);
  const [roleLoading, setRoleLoading] = useState(false);
  const [deleteLoading, setDeleteLoading] = useState(false);
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

  // Role Switch
  const handleRoleSwitch = (newRole) => {
    toast(
      (t) => (
        <div className="flex flex-col gap-2">
          <span>Switch to {newRole}? You’ll only see {newRole} features.</span>
          <div className="flex gap-2 justify-end mt-2">
            <button
              onClick={() => toast.dismiss(t.id)}
              className="px-3 py-1 bg-gray-300 rounded hover:bg-gray-400"
            >
              Cancel
            </button>
            <button
              onClick={async () => {
                toast.dismiss(t.id);
                await performRoleSwitch(newRole);
              }}
              className="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700"
            >
              Confirm
            </button>
          </div>
        </div>
      ),
      { duration: 5000 }
    );
  };

  const performRoleSwitch = async (newRole) => {
    try {
      setRoleLoading(true);
      const res = await api.post('/profile/switch-role', { role: newRole });
      updateRole(newRole);
      setUser((prev) => ({ ...prev, role: newRole }));
      toast.success(res.data.message || "Role switched successfully!");
    } catch (err) {
      const message = err.response?.data?.message || "Failed to switch role.";
      toast.error(message);
    } finally {
      setRoleLoading(false);
    }
  };

  // Delete Account
  const handleDeleteAccount = () => {
    toast(
      (t) => {
        let password = "";
        return (
          <div className="flex flex-col gap-2">
            <span className="font-semibold text-red-600">
              Enter password to delete your account
            </span>
            <input
              type="password"
              placeholder="Password"
              onChange={(e) => (password = e.target.value)}
              className="px-3 py-2 border rounded w-full"
            />
            <div className="flex gap-2 justify-end mt-2">
              <button
                onClick={() => toast.dismiss(t.id)}
                className="px-3 py-1 bg-gray-300 rounded hover:bg-gray-400"
              >
                Cancel
              </button>
              <button
                onClick={async () => {
                  if (!password) {
                    toast.error("Password is required!");
                    return;
                  }
                  toast.dismiss(t.id);
                  await performDeleteAccount(password);
                }}
                className="px-3 py-1 bg-red-600 text-white rounded hover:bg-red-700"
              >
                Delete
              </button>
            </div>
          </div>
        );
      },
      { duration: 10000 }
    );
  };

  const performDeleteAccount = async (password) => {
    try {
      setDeleteLoading(true);
      await api.post('/profile/delete-account', { password });
      localStorage.removeItem("token");

      toast.success("Your account has been deleted."); // show toast first
      setTimeout(() => {
        window.location.href = "/"; // force landing page after toast
      }, 800);
    } catch (err) {
      const message = err.response?.data?.message || "Failed to delete account.";
      toast.error(message);
    } finally {
      setDeleteLoading(false);
    }
  };


  if (loading) return <div className="p-6 text-center">Loading profile...</div>;

  return (
    <div className="max-w-lg mx-auto p-6 relative">
      <Toaster position="top-center" reverseOrder={false} />

      <h1 className="text-2xl font-bold mb-6">Your Profile</h1>
      <div className="bg-white p-5 rounded shadow">
        <div className="space-y-4">
          <InfoItem label="Name" value={user.name} />
          <InfoItem label="Email" value={user.email} />
          <InfoItem label="Blood Type" value={user.blood_type || "Not set"} />
          <InfoItem label="Role" value={user.role.charAt(0).toUpperCase() + user.role.slice(1)} />

          {user.role !== 'admin' && (
            <div className="pt-4 border-t">
              <h3 className="font-medium mb-2">Switch Role</h3>
              {user.role === "donor" ? (
                <button
                  disabled={roleLoading}
                  onClick={() => handleRoleSwitch("receiver")}
                  className="text-blue-600 hover:underline disabled:opacity-50"
                >
                  Become a Receiver
                </button>
              ) : (
                <button
                  disabled={roleLoading}
                  onClick={() => handleRoleSwitch("donor")}
                  className="text-blue-600 hover:underline disabled:opacity-50"
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
              disabled={deleteLoading}
              className="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600 text-sm disabled:opacity-50"
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
