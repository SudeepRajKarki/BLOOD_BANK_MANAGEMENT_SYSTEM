import { Fragment } from "react";

export default function ViewReportModal({ report, onClose }) {
  if (!report) return null;

  // 🔐 Safely parse JSON fields (backend stores as strings)
  let donors = [];
  let byType = {};
  try {
    donors = report.donors ? JSON.parse(report.donors) : [];
    byType = report.by_type ? JSON.parse(report.by_type) : {};
  } catch (e) {
    console.error("Failed to parse report JSON:", e);
  }

  const campaign = report.campaign || {};

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
      <div className="bg-white rounded-lg max-w-4xl w-full max-h-[90vh] overflow-hidden flex flex-col">
        {/* Header */}
        <div className="p-6 border-b">
          <div className="flex justify-between items-center">
            <h2 className="text-xl font-bold text-gray-800">
              Campaign Report #{report.campaign_id || report.id}
            </h2>
            <button
              onClick={onClose}
              className="text-gray-500 hover:text-gray-800 text-2xl font-light"
              aria-label="Close"
            >
              &times;
            </button>
          </div>
          <p className="text-sm text-gray-500 mt-1">
            Generated on {new Date(report.created_at).toLocaleString()}
          </p>
        </div>

        {/* Scrollable Content */}
        <div className="p-6 overflow-y-auto flex-grow">
          <div className="space-y-6">
            {/* Campaign Info */}
            <section className="bg-blue-50 p-4 rounded-lg">
              <h3 className="font-semibold text-lg text-blue-800 mb-2">📍 Campaign Details</h3>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm">
                <div><span className="font-medium">Location:</span> {campaign.location || '—'}</div>
                <div><span className="font-medium">Date:</span> {campaign.date || '—'}</div>
                <div><span className="font-medium">Status:</span> <span className="px-2 py-0.5 bg-blue-100 text-blue-800 rounded">{campaign.status || 'Completed'}</span></div>
              </div>
            </section>

            {/* Summary */}
            <section>
              <h3 className="font-semibold text-lg mb-3">📊 Summary</h3>
              <div className="text-2xl font-bold text-green-700">
                {report.total_quantity_ml || 0} <span className="text-base font-normal">ml collected</span>
              </div>
            </section>

            {/* Blood Type Breakdown */}
            {Object.keys(byType).length > 0 && (
              <section>
                <h3 className="font-semibold text-lg mb-3">🩸 Collected by Blood Type</h3>
                <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                  {Object.entries(byType).map(([type, qty]) => (
                    <div
                      key={type}
                      className="border rounded-lg p-3 bg-gray-50 text-center shadow-sm"
                    >
                      <div className="font-medium text-blue-700">{type}</div>
                      <div className="text-lg font-semibold text-gray-800">{qty} ml</div>
                    </div>
                  ))}
                </div>
              </section>
            )}

            {/* Donor List */}
            {donors.length > 0 ? (
              <section>
                <h3 className="font-semibold text-lg mb-3">🧑‍🤝‍🧑 Donors ({donors.length})</h3>
                <div className="overflow-x-auto">
                  <table className="min-w-full text-sm divide-y divide-gray-200">
                    <thead className="bg-gray-100">
                      <tr>
                        <th className="py-2 px-3 text-left">Name</th>
                        <th className="py-2 px-3 text-left">Blood Type</th>
                        <th className="py-2 px-3 text-right">Quantity</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y">
                      {donors.map((donor, index) => (
                        <tr key={index} className="hover:bg-gray-50">
                          <td className="py-2 px-3 font-medium">
                            {donor.name || `Donor #${donor.donor_id}`}
                          </td>
                          <td className="py-2 px-3 font-mono">{donor.blood_type || '—'}</td>
                          <td className="py-2 px-3 text-right">{donor.quantity_ml} ml</td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              </section>
            ) : (
              <p className="text-gray-500 italic">No donor records found.</p>
            )}

            {/* Raw Report (Fallback/Debug) */}
            {process.env.NODE_ENV === 'development' && report.report_text && (
              <section>
                <details className="bg-gray-100 p-3 rounded">
                  <summary className="cursor-pointer font-medium text-gray-600">
                    📝 Raw Report Text (Dev Only)
                  </summary>
                  <pre className="text-xs mt-2 p-2 bg-white rounded overflow-auto max-h-32">
                    {report.report_text}
                  </pre>
                </details>
              </section>
            )}
          </div>
        </div>

        {/* Footer */}
        <div className="p-4 border-t bg-gray-50 text-right">
          <button
            onClick={onClose}
            className="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            Close
          </button>
        </div>
      </div>
    </div>
  );
}