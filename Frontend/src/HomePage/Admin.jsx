import React, { useEffect, useState, useContext } from "react";
import { AuthContext } from "../Context/AuthContext";
import api from "../api/axios";
import toast, { Toaster } from "react-hot-toast";
import {
    LineChart,
    Line,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
    ResponsiveContainer,
    ScatterChart,
    Scatter,
    ZAxis,
} from "recharts";

const Admin = () => {
    const { token } = useContext(AuthContext);

    const [stats, setStats] = useState({
        donations: 0,
        requests: 0,
        campaigns: 0,
        top_donors: [],
    });
    const [loading, setLoading] = useState(false);
    const [forecastResult, setForecastResult] = useState(null);
    const [targetedCampaigns, setTargetedCampaigns] = useState(null);

    // Fetch dashboard stats
    const fetchDashboardData = async () => {
        setLoading(true);
        try {
            const res = await api.get("/dashboard", {
                headers: { Authorization: `Bearer ${token}` },
            });
            setStats(res.data);
        } catch (err) {
            toast.error(err.response?.data?.message || "Failed to load dashboard data");
        } finally {
            setLoading(false);
        }
    };

    // AI Demand Forecast
    const handleForecast = async () => {
        toast.loading("Running AI demand forecast...");
        try {
            const res = await api.post("/ai/demand-forecast", {}, {
                headers: { Authorization: `Bearer ${token}` },
            });
            setForecastResult(res.data);
            toast.dismiss();
            toast.success("Forecast completed!");
        } catch (err) {
            toast.dismiss();
            toast.error("AI forecast failed.");
        }
    };

    // AI Campaign Targeting
    const handleTargeting = async () => {
        toast.loading("Analyzing campaign targeting...");
        try {
            const res = await api.post("/ai/campaign-targeting", {}, {
                headers: { Authorization: `Bearer ${token}` },
            });
            setTargetedCampaigns(res.data);
            toast.dismiss();
            toast.success("Campaign targeting completed!");
        } catch (err) {
            toast.dismiss();
            toast.error("AI campaign targeting failed.");
        }
    };

    useEffect(() => {
        fetchDashboardData();
    }, []);

    return (
        <div className="container mx-auto p-6 font-sans text-gray-800 relative">
            <Toaster position="top-center" reverseOrder={false} />

            {loading && (
                <div className="absolute inset-0 flex items-center justify-center bg-white/60 backdrop-blur-sm z-10">
                    <div className="flex items-center space-x-3 text-red-600">
                        <svg className="animate-spin h-6 w-6 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                            <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <span className="font-medium">Loading dashboard...</span>
                    </div>
                </div>
            )}

            <h2 className="text-3xl font-bold mb-6 text-center text-red-600">Admin Dashboard</h2>

            {/* === Stats Section === */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div className="bg-red-100 p-6 rounded-xl shadow-md text-center">
                    <h3 className="text-xl font-semibold text-red-700">Total Donations</h3>
                    <p className="text-3xl font-bold mt-2">{stats.donations}</p>
                </div>

                <div className="bg-yellow-100 p-6 rounded-xl shadow-md text-center">
                    <h3 className="text-xl font-semibold text-yellow-700">Blood Requests</h3>
                    <p className="text-3xl font-bold mt-2">{stats.requests}</p>
                </div>

                <div className="bg-green-100 p-6 rounded-xl shadow-md text-center">
                    <h3 className="text-xl font-semibold text-green-700">Campaigns</h3>
                    <p className="text-3xl font-bold mt-2">{stats.campaigns}</p>
                </div>
            </div>

            {/* === AI Insights Section === */}
            <div className="bg-white p-6 rounded-xl shadow-md mb-8">
                <h3 className="text-xl font-semibold mb-4 text-gray-700">🧠 AI Insights</h3>

                <div className="flex flex-wrap gap-4 mb-4">
                    <button onClick={handleForecast} className="bg-red-600 hover:bg-red-700 text-white px-5 py-2 rounded-lg shadow">Run Demand Forecast</button>
                    <button onClick={handleTargeting} className="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg shadow">Run Campaign Targeting</button>
                </div>

                {/* === Forecast Results === */}
                {forecastResult && (
                    <div className="bg-red-50 p-5 rounded-xl shadow-inner mb-6">
                        <h4 className="font-semibold text-red-700 mb-3 text-lg">📊 Demand Forecast</h4>

                        {forecastResult.summary ? (
                            <p className="text-gray-700 mb-4">{forecastResult.summary}</p>
                        ) : (
                            <p className="text-gray-600 mb-4">No forecast summary available.</p>
                        )}

                        {forecastResult.forecast_table?.length > 0 ? (
                            <div className="w-full h-80 mb-4">
                                <ResponsiveContainer>
                                    <LineChart data={forecastResult.forecast_table}>
                                        <CartesianGrid strokeDasharray="3 3" />
                                        <XAxis dataKey="ds" tick={{ fontSize: 12 }} />
                                        <YAxis />
                                        <Tooltip />
                                        <Line
                                            type="monotone"
                                            dataKey="yhat"
                                            stroke="#ef4444"
                                            strokeWidth={2}
                                            dot={false}
                                        />
                                    </LineChart>
                                </ResponsiveContainer>
                                <p className="text-gray-600 mt-2 text-sm">
                                    Note: <strong>yhat</strong> → predicted blood units required for that date
                                </p>
                            </div>
                        ) : (
                            <p className="text-gray-600">No forecast table data available.</p>
                        )}
                    </div>
                )}

                {/* === Campaign Targeting Results === */}
                {targetedCampaigns ? (
                    <div className="bg-blue-50 p-5 rounded-xl shadow-inner border border-blue-200">
                        <h4 className="font-semibold text-blue-700 text-lg mb-3">🎯 Campaign Targeting</h4>

                        {targetedCampaigns.summary ? (
                            <p className="text-gray-800 mb-4">{targetedCampaigns.summary}</p>
                        ) : (
                            <p className="text-gray-600 mb-4">No targeting summary available.</p>
                        )}

                        {targetedCampaigns.recommended_locations?.length > 0 ? (
                            <div className="mb-4">
                                <h5 className="font-semibold text-blue-600 mb-2">📍 Recommended Locations:</h5>
                                <ul className="list-disc list-inside space-y-1 text-gray-700">
                                    {targetedCampaigns.recommended_locations.map((loc, idx) => (
                                        <li key={idx}>{loc}</li>
                                    ))}
                                </ul>
                            </div>
                        ) : (
                            <p className="text-gray-600 mb-4">No specific locations recommended.</p>
                        )}

                        {targetedCampaigns.centers?.length > 0 ? (
                            <div className="overflow-x-auto mt-4">
                                <h5 className="font-semibold text-blue-600 mb-2">📊 Cluster Centers:</h5>
                                <div className="w-full h-80 mb-4">
                                    <ResponsiveContainer>
                                        <ScatterChart>
                                            <CartesianGrid strokeDasharray="3 3" />
                                            <XAxis type="number" dataKey="0" name="Turnout" label={{ value: 'Avg Turnout', position: 'insideBottom', offset: -5 }} />
                                            <YAxis type="number" dataKey="1" name="Shortage" label={{ value: 'Avg Shortage', angle: -90, position: 'insideLeft' }} />
                                            <ZAxis range={[60, 200]} />
                                            <Tooltip cursor={{ strokeDasharray: '3 3' }} formatter={(value, name) => [value.toFixed(2), name]} />
                                            <Scatter
                                                name="Cluster Centers"
                                                data={targetedCampaigns.centers.map((c, i) => ({ 0: c[0], 1: c[1], cluster: i + 1 }))}
                                                fill="#2563eb"
                                            />
                                        </ScatterChart>
                                    </ResponsiveContainer>
                                </div>

                                <table className="min-w-full text-sm border border-gray-300 rounded-lg">
                                    <thead className="bg-blue-100 text-blue-700">
                                        <tr>
                                            <th className="p-2 border">Cluster</th>
                                            <th className="p-2 border">Turnout (avg)</th>
                                            <th className="p-2 border">Shortages (avg)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {targetedCampaigns.centers.map((center, i) => (
                                            <tr key={i} className="hover:bg-blue-50">
                                                <td className="p-2 border text-center">{i + 1}</td>
                                                <td className="p-2 border text-center">{center[0].toFixed(1)}</td>
                                                <td className="p-2 border text-center">{center[1]?.toFixed(1) || "–"}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        ) : (
                            <p className="text-gray-600">No cluster center data available.</p>
                        )}
                    </div>
                ) : null}
            </div>

            {/* === Top Donors Section === */}
            <div className="bg-white p-6 rounded-xl shadow-md">
                <h3 className="text-xl font-semibold mb-4 text-gray-700">Top Donors</h3>
                {stats.top_donors.length > 0 ? (
                    <table className="w-full border border-gray-200 text-left text-sm">
                        <thead>
                            <tr className="bg-gray-100 text-gray-600">
                                <th className="p-3 border-b">Donor ID</th>
                                <th className="p-3 border-b">Name</th>
                                <th className="p-3 border-b">Total Donations</th>
                            </tr>
                        </thead>
                        <tbody>
                            {stats.top_donors.map((donor, index) => (
                                <tr key={index} className="hover:bg-gray-50">
                                    <td className="p-3 border-b">{donor.donor_id}</td>
                                    <td className="p-3 border-b">{donor.donor?.name}</td>
                                    <td className="p-3 border-b">{donor.total}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                ) : (
                    <p className="text-gray-600">No donor data available.</p>
                )}
            </div>
        </div>
    );
};

export default Admin;
