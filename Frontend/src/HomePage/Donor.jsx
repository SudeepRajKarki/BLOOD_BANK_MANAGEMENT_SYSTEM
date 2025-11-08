import { useState, useEffect } from "react";
import api from "../api/axios";

export default function DonorDashboard() {
    const [donations, setDonations] = useState([]);
    const [loading, setLoading] = useState(true);
    const [msg, setMsg] = useState("");
    const [activeTab, setActiveTab] = useState("campaign"); // "campaign" or "request"

    useEffect(() => {
        fetchDonations();
    }, []);

    const fetchDonations = async () => {
        try {
            const res = await api.get("/donations"); // Make sure API returns request.receiver
            setDonations(res.data);
        } catch (err) {
            console.error(err);
            setMsg("Failed to load donation history.");
        } finally {
            setLoading(false);
        }
    };

    if (loading) return <p className="p-6">Loading donation history...</p>;

    const campaignDonations = donations.filter((d) => d.campaign_id);
    const requestDonations = donations.filter((d) => !d.campaign_id);

    return (
        <div className="max-w-6xl mx-auto mt-10 p-4 space-y-6">
            <h1 className="text-2xl font-bold mb-4">🩸 Donation History</h1>
            {msg && <p className="text-sm text-red-600">{msg}</p>}

            {/* Tabs */}
            <div className="flex gap-2 mb-4 border-b border-gray-300">
                <button
                    className={`px-4 py-2 ${activeTab === "campaign"
                        ? "border-b-2 border-red-600 font-semibold text-red-600"
                        : "text-gray-600 hover:text-red-600"
                        }`}
                    onClick={() => setActiveTab("campaign")}
                >
                    Campaign
                </button>
                <button
                    className={`px-4 py-2 ${activeTab === "request"
                        ? "border-b-2 border-red-600 font-semibold text-red-600"
                        : "text-gray-600 hover:text-red-600"
                        }`}
                    onClick={() => setActiveTab("request")}
                >
                    Requests
                </button>
            </div>

            {/* Donations List */}
            <div className="space-y-2">
                {(activeTab === "campaign" ? campaignDonations : requestDonations).map((d) => (
                    <div
                        key={d.id}
                        className="p-4 bg-white shadow rounded flex justify-between items-center"
                    >
                        <div>
                            <p>
                                Blood Type: <span className="font-semibold">{d.blood_type}</span>
                            </p>
                            <p>Quantity: {d.quantity_ml} ml</p>
                            <p>Date: {new Date(d.donation_date).toLocaleString()}</p>

                            {d.campaign_id ? (
                                <p>
                                    Campaign: {d.location ? `${d.location}` : ""}
                                </p>
                            ) : (
                                <p>
                                    Donated for Request by: {d.request?.receiver?.name || "Unknown"} (ID: {d.request_id})
                                </p>
                            )}
                        </div>

                        <span
                            className={`px-3 py-1 rounded-full text-sm ${
                                d.verified ? "bg-green-100 text-green-800" : "bg-yellow-100 text-yellow-800"
                            }`}
                        >
                            {d.verified ? "Verified" : "Pending"}
                        </span>
                    </div>
                ))}

                {(activeTab === "campaign" ? campaignDonations : requestDonations).length === 0 && (
                    <p className="text-gray-500">No donations in this category.</p>
                )}
            </div>
        </div>
    );
}
