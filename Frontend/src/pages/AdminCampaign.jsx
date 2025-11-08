import { useState, useEffect } from "react";
import api from "../api/axios";
import ViewReportModal from "../components/ViewReportModal";

export default function ManageCampaigns() {
  const [campaigns, setCampaigns] = useState([]);
  const [loading, setLoading] = useState(true);
  const [selectedReport, setSelectedReport] = useState(null);
  const [showCreateModal, setShowCreateModal] = useState(false);
  const [newCampaign, setNewCampaign] = useState({
    location: "",
    date: "",
    status: "Ongoing",
    description: "",
  });

  // Fetch campaigns on mount
  useEffect(() => {
    fetchCampaigns();
  }, []);

  const fetchCampaigns = async () => {
    try {
      const res = await api.get("/campaigns");
      setCampaigns(res.data);
    } catch (err) {
      console.error("Failed to load campaigns:", err);
      alert("Failed to load campaigns. Please try again.");
    } finally {
      setLoading(false);
    }
  };

  // Create new campaign
  const handleCreate = async () => {
    if (!newCampaign.location || !newCampaign.date) {
      alert("Please fill in Location and Date fields.");
      return;
    }

    try {
      await api.post("/campaign", newCampaign);
      setShowCreateModal(false);
      setNewCampaign({ location: "", date: "", status: "Ongoing", description: "" });
      fetchCampaigns(); // Refresh list
    } catch (err) {
      alert(
        "Failed to create campaign: " +
          (err.response?.data?.message || "Unknown error")
      );
    }
  };

  // Delete campaign
  const handleDelete = async (id) => {
    if (!confirm("Are you sure you want to delete this campaign?")) return;

    try {
      await api.delete(`/campaigns/${id}`);
      fetchCampaigns(); // Refresh list
    } catch (err) {
      alert("Failed to delete campaign.");
    }
  };

  // View report
  const handleViewReport = async (campaignId) => {
    try {
      const res = await api.get("/campaign-reports");
      const report = res.data.find((r) => r.campaign_id == campaignId);

      if (report) {
        setSelectedReport(report);
      } else {
        alert("No report found for this campaign.");
      }
    } catch (err) {
      console.error("Report fetch error:", err);
      alert(
        "Failed to load report: " +
          (err.response?.data?.message || "Unknown error")
      );
    }
  };

  // Close report modal
  const handleCloseReport = () => setSelectedReport(null);

  // ====== RENDER ======

  if (loading) {
    return (
      <div className="p-6 max-w-6xl mx-auto">
        <div className="flex justify-between items-center mb-6">
          <h1 className="text-2xl font-bold">Manage Campaigns</h1>
          <button
            onClick={() => setShowCreateModal(true)}
            className="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition"
          >
            + New Campaign
          </button>
        </div>
        <div className="animate-pulse bg-gray-100 rounded-lg p-6">
          <div className="h-8 bg-gray-300 rounded w-1/4 mb-4"></div>
          <div className="space-y-3">
            {[...Array(4)].map((_, i) => (
              <div key={i} className="h-10 bg-gray-300 rounded"></div>
            ))}
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="p-6 max-w-6xl mx-auto">
      {/* Header */}
      <div className="flex justify-between items-center mb-6">
        <h1 className="text-2xl font-bold">Manage Campaigns</h1>
        <button
          onClick={() => setShowCreateModal(true)}
          className="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition flex items-center gap-2"
        >
          <span>+</span> New Campaign
        </button>
      </div>

      {/* Campaign Table */}
      <div className="overflow-x-auto bg-white rounded-lg shadow">
        <table className="min-w-full divide-y divide-gray-200">
          <thead className="bg-gray-50">
            <tr>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Location
              </th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Date
              </th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Status
              </th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Description
              </th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Actions
              </th>
            </tr>
          </thead>
          <tbody className="bg-white divide-y divide-gray-200">
            {campaigns.length === 0 ? (
              <tr>
                <td colSpan="5" className="px-6 py-4 text-center text-gray-500">
                  No campaigns found. Create one to get started!
                </td>
              </tr>
            ) : (
              campaigns.map((campaign) => (
                <CampaignRow
                  key={campaign.id}
                  campaign={campaign}
                  onDelete={handleDelete}
                  onViewReport={handleViewReport}
                  onRefresh={fetchCampaigns}
                />
              ))
            )}
          </tbody>
        </table>
      </div>

      {/* Create Campaign Modal */}
      {showCreateModal && (
        <CreateCampaignModal
          onClose={() => setShowCreateModal(false)}
          onCreate={handleCreate}
          newCampaign={newCampaign}
          setNewCampaign={setNewCampaign}
        />
      )}

      {/* Report Modal */}
      {selectedReport && (
        <ViewReportModal report={selectedReport} onClose={handleCloseReport} />
      )}
    </div>
  );
}

// ====== REUSABLE COMPONENTS ======

// Campaign Row Component
function CampaignRow({ campaign, onDelete, onViewReport, onRefresh }) {
  const [isEditing, setIsEditing] = useState(false);
  const [editForm, setEditForm] = useState({
    location: campaign.location,
    date: campaign.date,
    status: campaign.status,
    description: campaign.description,
  });

  const handleUpdate = async () => {
    try {
      await api.put(`/campaigns/${campaign.id}`, editForm);
      setIsEditing(false);
      onRefresh();
    } catch (err) {
      alert("Update failed. Please try again.");
    }
  };

  const handleCancel = () => {
    setIsEditing(false);
    setEditForm({
      location: campaign.location,
      date: campaign.date,
      status: campaign.status,
      description: campaign.description,
    });
  };

  if (isEditing) {
    return (
      <tr className="bg-blue-50">
        <td className="px-6 py-4">
          <input
            value={editForm.location}
            onChange={(e) =>
              setEditForm({ ...editForm, location: e.target.value })
            }
            className="border border-gray-300 rounded px-3 py-2 w-full focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </td>
        <td className="px-6 py-4">
          <input
            type="date"
            value={editForm.date}
            onChange={(e) => setEditForm({ ...editForm, date: e.target.value })}
            className="border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </td>
        <td className="px-6 py-4">
          <select
            value={editForm.status}
            onChange={(e) =>
              setEditForm({ ...editForm, status: e.target.value })
            }
            className="border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option value="Ongoing">Ongoing</option>
            <option value="Completed">Completed</option>
            <option value="Cancelled">Cancelled</option>
          </select>
        </td>
        <td className="px-6 py-4">
          <textarea
            value={editForm.description}
            onChange={(e) =>
              setEditForm({ ...editForm, description: e.target.value })
            }
            rows={2}
            className="border border-gray-300 rounded px-3 py-2 w-full focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </td>
        <td className="px-6 py-4 space-x-2">
          <button
            onClick={handleUpdate}
            className="text-green-600 hover:text-green-800 font-medium"
          >
            Save
          </button>
          <button
            onClick={handleCancel}
            className="text-gray-600 hover:text-gray-800 font-medium"
          >
            Cancel
          </button>
        </td>
      </tr>
    );
  }

  return (
    <tr className="hover:bg-gray-50 transition-colors">
      <td className="px-6 py-4 font-medium text-gray-900">{campaign.location}</td>
      <td className="px-6 py-4 text-gray-600">{campaign.date}</td>
      <td className="px-6 py-4">
        <span
          className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
            campaign.status === "Ongoing"
              ? "bg-green-100 text-green-800"
              : campaign.status === "Completed"
              ? "bg-blue-100 text-blue-800"
              : "bg-red-100 text-red-800"
          }`}
        >
          {campaign.status}
        </span>
      </td>
      <td className="px-6 py-4 text-gray-600">{campaign.description}</td>
      <td className="px-6 py-4 space-x-3">
        <button
          onClick={() => setIsEditing(true)}
          className="text-blue-600 hover:text-blue-800 font-medium"
        >
          Edit
        </button>
        <button
          onClick={() => onDelete(campaign.id)}
          className="text-red-600 hover:text-red-800 font-medium"
        >
          Delete
        </button>
        {campaign.status === "Completed" && (
          <button
            onClick={() => onViewReport(campaign.id)}
            className="text-purple-600 hover:text-purple-800 font-medium"
          >
            View Report
          </button>
        )}
      </td>
    </tr>
  );
}

// Create Campaign Modal Component
function CreateCampaignModal({ onClose, onCreate, newCampaign, setNewCampaign }) {
  const handleChange = (e) => {
    const { name, value } = e.target;
    setNewCampaign((prev) => ({ ...prev, [name]: value }));
  };

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
      <div className="bg-white rounded-xl shadow-xl w-full max-w-md p-6">
        <div className="flex justify-between items-center mb-4">
          <h2 className="text-xl font-bold text-gray-800">Create New Campaign</h2>
          <button
            onClick={onClose}
            className="text-gray-500 hover:text-gray-800 text-2xl font-light"
            aria-label="Close"
          >
            &times;
          </button>
        </div>

        <div className="space-y-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Location
            </label>
            <input
              type="text"
              name="location"
              value={newCampaign.location}
              onChange={handleChange}
              className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
              placeholder="e.g., Kathmandu"
            />
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Date
            </label>
            <input
              type="date"
              name="date"
              value={newCampaign.date}
              onChange={handleChange}
              className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Status
            </label>
            <select
              name="status"
              value={newCampaign.status}
              onChange={handleChange}
              className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <option value="Ongoing">Ongoing</option>
              <option value="Completed">Completed</option>
              <option value="Cancelled">Cancelled</option>
            </select>
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Description
            </label>
            <textarea
              name="description"
              value={newCampaign.description}
              onChange={handleChange}
              rows={3}
              className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
              placeholder="Brief description of the campaign..."
            />
          </div>
        </div>

        <div className="mt-6 flex justify-end space-x-3">
          <button
            onClick={onClose}
            className="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-300"
          >
            Cancel
          </button>
          <button
            onClick={onCreate}
            className="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500"
          >
            Create Campaign
          </button>
        </div>
      </div>
    </div>
  );
}