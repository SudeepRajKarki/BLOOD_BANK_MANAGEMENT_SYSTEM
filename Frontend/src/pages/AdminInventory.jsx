import { useState, useEffect } from "react";
import api from "../api/axios";

const BLOOD_TYPES = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

export default function AdminInventory() {
  const [inventory, setInventory] = useState([]);
  const [loading, setLoading] = useState(true);
  const [editingId, setEditingId] = useState(null);
  const [showForm, setShowForm] = useState(false);
  const [formData, setFormData] = useState({
    blood_type: '',
    quantity_ml: '',
    location: ''
  });
  const [editForm, setEditForm] = useState({
    blood_type: '',
    quantity_ml: '',
    location: ''
  });

  useEffect(() => {
    fetchInventory();
  }, []);

  const fetchInventory = () => {
    api.get('/blood-inventory')
      .then(res => {
        setInventory(res.data);
        setLoading(false);
      })
      .catch(err => {
        console.error('Failed to fetch inventory:', err);
        alert('Failed to load inventory');
        setLoading(false);
      });
  };

  const handleCreate = () => {
    setFormData({ blood_type: '', quantity_ml: '', location: '' });
    setShowForm(true);
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    const { blood_type, quantity_ml, location } = formData;
    if (!blood_type || !quantity_ml || !location) {
      alert("All fields are required.");
      return;
    }

    api.post('/blood-inventory', {
      blood_type,
      quantity_ml: parseInt(quantity_ml),
      location
    })
      .then(() => {
        alert('Stock added successfully!');
        setShowForm(false);
        fetchInventory();
      })
      .catch(err => {
        const msg = err.response?.data?.message || 'Failed to add stock';
        alert(msg);
      });
  };

  const handleEdit = (item) => {
    setEditingId(item.id);
    setEditForm({
      blood_type: item.blood_type,
      quantity_ml: item.quantity_ml,
      location: item.location
    });
  };

  const handleUpdate = (id) => {
    api.put(`/blood-inventory/${id}`, editForm)
      .then(() => {
        setEditingId(null);
        fetchInventory();
      })
      .catch(err => {
        alert('Update failed: ' + (err.response?.data?.message || ''));
      });
  };

  const handleDelete = (id) => {
    if (!confirm('Are you sure you want to delete this inventory record?')) return;
    
    api.delete(`/blood-inventory/${id}`)
      .then(() => {
        alert('Deleted!');
        fetchInventory();
      })
      .catch(err => {
        alert('Delete failed: ' + (err.response?.data?.message || ''));
      });
  };

  if (loading) {
    return <div className="p-8 text-center">Loading blood inventory...</div>;
  }

  return (
    <div className="p-6 max-w-6xl mx-auto">
      {/* Header */}
      <div className="flex justify-between items-center mb-6">
        <h1 className="text-2xl font-bold text-gray-800">Manage Blood Inventory</h1>
        <button
          onClick={handleCreate}
          className="bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-lg shadow"
        >
          + Add Stock
        </button>
      </div>

      {/* Add Inventory Modal */}
      {showForm && (
        <div className="fixed inset-0 bg-black bg-opacity-40 flex justify-center items-center z-50">
          <div className="bg-white rounded-xl shadow-lg p-6 w-full max-w-md">
            <h2 className="text-xl font-semibold mb-4 text-gray-800">Add Blood Stock</h2>
            <form onSubmit={handleSubmit} className="space-y-4">
              <div>
                <label className="block text-gray-700 mb-1 font-medium">Blood Type</label>
                <select
                  value={formData.blood_type}
                  onChange={(e) => setFormData({ ...formData, blood_type: e.target.value })}
                  className="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-500"
                >
                  <option value="">Select Blood Type</option>
                  {BLOOD_TYPES.map(type => (
                    <option key={type} value={type}>{type}</option>
                  ))}
                </select>
              </div>

              <div>
                <label className="block text-gray-700 mb-1 font-medium">Quantity (ml)</label>
                <input
                  type="number"
                  min="1"
                  value={formData.quantity_ml}
                  onChange={(e) => setFormData({ ...formData, quantity_ml: e.target.value })}
                  className="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-500"
                  placeholder="Enter quantity in ml"
                />
              </div>

              <div>
                <label className="block text-gray-700 mb-1 font-medium">Location</label>
                <input
                  type="text"
                  value={formData.location}
                  onChange={(e) => setFormData({ ...formData, location: e.target.value })}
                  className="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-500"
                  placeholder="Enter location"
                />
              </div>

              <div className="flex justify-end space-x-3 mt-4">
                <button
                  type="button"
                  onClick={() => setShowForm(false)}
                  className="bg-gray-300 hover:bg-gray-400 text-gray-800 font-medium py-2 px-4 rounded-lg"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-lg"
                >
                  Save
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Inventory Table */}
      <div className="overflow-x-auto rounded-xl border shadow-sm bg-white">
        <table className="min-w-full">
          <thead className="bg-gray-100 border-b">
            <tr>
              <th className="py-3 px-4 text-left text-gray-700 font-semibold">Blood Type</th>
              <th className="py-3 px-4 text-left text-gray-700 font-semibold">Quantity (ml)</th>
              <th className="py-3 px-4 text-left text-gray-700 font-semibold">Location</th>
              <th className="py-3 px-4 text-left text-gray-700 font-semibold">Actions</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-gray-200">
            {inventory.map(item => (
              <tr key={item.id} className="hover:bg-gray-50 transition">
                {editingId === item.id ? (
                  <>
                    <td className="py-3 px-4">
                      <select
                        value={editForm.blood_type}
                        onChange={e => setEditForm({...editForm, blood_type: e.target.value})}
                        className="border rounded-lg px-2 py-1"
                      >
                        {BLOOD_TYPES.map(t => (
                          <option key={t} value={t}>{t}</option>
                        ))}
                      </select>
                    </td>
                    <td className="py-3 px-4">
                      <input
                        type="number"
                        value={editForm.quantity_ml}
                        onChange={e => setEditForm({...editForm, quantity_ml: e.target.value})}
                        className="border rounded-lg px-2 py-1 w-24"
                        min="1"
                      />
                    </td>
                    <td className="py-3 px-4">
                      <input
                        value={editForm.location}
                        onChange={e => setEditForm({...editForm, location: e.target.value})}
                        className="border rounded-lg px-2 py-1 w-32"
                      />
                    </td>
                    <td className="py-3 px-4">
                      <button
                        onClick={() => handleUpdate(item.id)}
                        className="text-green-600 hover:underline mr-3"
                      >
                        Save
                      </button>
                      <button
                        onClick={() => setEditingId(null)}
                        className="text-gray-600 hover:underline"
                      >
                        Cancel
                      </button>
                    </td>
                  </>
                ) : (
                  <>
                    <td className="py-3 px-4 font-medium text-gray-800">{item.blood_type}</td>
                    <td className="py-3 px-4 text-gray-700">{item.quantity_ml}</td>
                    <td className="py-3 px-4 text-gray-700">{item.location}</td>
                    <td className="py-3 px-4">
                      <button
                        onClick={() => handleEdit(item)}
                        className="text-blue-600 hover:underline mr-3"
                      >
                        Edit
                      </button>
                      <button
                        onClick={() => handleDelete(item.id)}
                        className="text-red-600 hover:underline"
                      >
                        Delete
                      </button>
                    </td>
                  </>
                )}
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
}
