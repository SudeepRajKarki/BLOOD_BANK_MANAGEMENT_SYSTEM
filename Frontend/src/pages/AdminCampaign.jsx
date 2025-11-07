import { useState, useEffect } from "react";
import api from "../api/axios";
import ViewReportModal from "../components/ViewReportModal"; // ✅ Import

export default function ManageCampaigns() {
  const [campaigns, setCampaigns] = useState([]);
  const [loading, setLoading] = useState(true);
  const [editingId, setEditingId] = useState(null);
  const [editForm, setEditForm] = useState({ location: '', date: '', status: '', description: '' });
  const [selectedReport, setSelectedReport] = useState(null);

  useEffect(() => {
    fetchCampaigns();
  }, []);

  const fetchCampaigns = () => {
    api.get('/campaigns')
      .then(res => {
        setCampaigns(res.data);
        setLoading(false);
      })
      .catch(err => {
        alert('Failed to load campaigns');
        setLoading(false);
      });
  };

  const handleCreate = () => {
    const location = prompt("Location");
    const date = prompt("Date (YYYY-MM-DD)");
    const status = prompt("Status (Ongoing/Completed/Cancelled)", "Ongoing");
    const description = prompt("Description (optional)");
    
    if (!location || !date) return;

    api.post('/campaign', { location, date, status, description })
      .then(() => {
        alert('Campaign created!');
        fetchCampaigns();
      })
      .catch(err => alert('Failed to create: ' + (err.response?.data?.message || 'Unknown error')));
  };

  const handleUpdate = (id) => {
    api.put(`/campaigns/${id}`, editForm)
      .then(() => {
        setEditingId(null);
        fetchCampaigns();
      })
      .catch(err => alert('Update failed'));
  };

  const handleDelete = (id) => {
    if (!confirm('Delete this campaign?')) return;
    api.delete(`/campaigns/${id}`)
      .then(() => fetchCampaigns())
      .catch(err => alert('Delete failed'));
  };

  // ✅ Fixed: no manual campaign merge needed — API already includes it via ->with('campaign')
  const handleViewReport = (campaignId) => {
    api.get('/campaign-reports')
      .then(res => {
        // Use == for safe ID comparison (string vs number)
        const report = res.data.find(r => r.campaign_id == campaignId);
        if (report) {
          setSelectedReport(report); // ✅ report already has .campaign
        } else {
          alert('No report found for this campaign.');
        }
      })
      .catch(err => {
        console.error('Report fetch error:', err);
        alert('Failed to load report: ' + (err.response?.data?.message || 'Unknown error'));
      });
  };

  const handleCloseReport = () => {
    setSelectedReport(null);
  };

  if (loading) return <div className="p-6">Loading campaigns...</div>;

  return (
    <div className="p-6 max-w-6xl mx-auto">
      <div className="flex justify-between items-center mb-6">
        <h1 className="text-2xl font-bold">Manage Campaigns</h1>
        <button
          onClick={handleCreate}
          className="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700"
        >
          + New Campaign
        </button>
      </div>

      <div className="overflow-x-auto">
        <table className="min-w-full border">
          <thead className="bg-gray-50">
            <tr>
              <th className="py-3 px-4 text-left">Location</th>
              <th className="py-3 px-4 text-left">Date</th>
              <th className="py-3 px-4 text-left">Status</th>
              <th className="py-3 px-4 text-left">Description</th>
              <th className="py-3 px-4 text-left">Actions</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-gray-200">
            {campaigns.map(campaign => (
              <tr key={campaign.id}>
                {editingId === campaign.id ? (
                  <>
                    <td className="py-3 px-4">
                      <input
                        value={editForm.location}
                        onChange={e => setEditForm({...editForm, location: e.target.value})}
                        className="border rounded p-1 w-full"
                      />
                    </td>
                    <td className="py-3 px-4">
                      <input
                        type="date"
                        value={editForm.date}
                        onChange={e => setEditForm({...editForm, date: e.target.value})}
                        className="border rounded p-1"
                      />
                    </td>
                    <td className="py-3 px-4">
                      <select
                        value={editForm.status}
                        onChange={e => setEditForm({...editForm, status: e.target.value})}
                        className="border rounded p-1"
                      >
                        <option value="Ongoing">Ongoing</option>
                        <option value="Completed">Completed</option>
                        <option value="Cancelled">Cancelled</option>
                      </select>
                    </td>
                    <td className="py-3 px-4">
                      <textarea
                        value={editForm.description}
                        onChange={e => setEditForm({...editForm, description: e.target.value})}
                        className="border rounded p-1 w-full"
                        rows="2"
                      />
                    </td>
                    <td className="py-3 px-4">
                      <button
                        onClick={() => handleUpdate(campaign.id)}
                        className="text-green-600 mr-2"
                      >
                        Save
                      </button>
                      <button
                        onClick={() => setEditingId(null)}
                        className="text-gray-600"
                      >
                        Cancel
                      </button>
                    </td>
                  </>
                ) : (
                  <>
                    <td className="py-3 px-4 font-medium">{campaign.location}</td>
                    <td className="py-3 px-4">{campaign.date}</td>
                    <td className="py-3 px-4">
                      <span className={`px-2 py-1 rounded-full text-xs ${
                        campaign.status === 'Ongoing' ? 'bg-green-100 text-green-800' :
                        campaign.status === 'Completed' ? 'bg-blue-100 text-blue-800' :
                        'bg-red-100 text-red-800'
                      }`}>
                        {campaign.status}
                      </span>
                    </td>
                    <td className="py-3 px-4">{campaign.description}</td>
                    <td className="py-3 px-4">
                      <div className="space-x-2">
                        <button
                          onClick={() => {
                            setEditingId(campaign.id);
                            setEditForm({
                              location: campaign.location,
                              date: campaign.date,
                              status: campaign.status,
                              description: campaign.description
                            });
                          }}
                          className="text-blue-600"
                        >
                          Edit
                        </button>
                        <button
                          onClick={() => handleDelete(campaign.id)}
                          className="text-red-600"
                        >
                          Delete
                        </button>
                        {campaign.status === 'Completed' && (
                          <button
                            onClick={() => handleViewReport(campaign.id)}
                            className="text-purple-600"
                          >
                            View Report
                          </button>
                        )}
                      </div>
                    </td>
                  </>
                )}
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      {/* ✅ Render the dedicated modal component — remove old inline modal */}
      {selectedReport && (
        <ViewReportModal
          report={selectedReport}
          onClose={handleCloseReport}
        />
      )}
    </div>
  );
}