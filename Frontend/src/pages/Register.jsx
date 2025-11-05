import { useState } from "react";
import api from "../api/axios";

export default function Register() {
  const [form, setForm] = useState({
    name: "",
    email: "",
    password: "",
    role: "donor",
    blood_group: "",
    location: "",
  });

  const handleChange = (e) =>
    setForm({ ...form, [e.target.name]: e.target.value });

   const handleSubmit = async (e) => {
    e.preventDefault();
     try {
    const res = await api.post("/register", form);
    alert(res.data.message);
     } catch (err) {
      if (err.response?.status === 422) {
      const errors = err.response.data.errors;
      if (errors.email) {
        alert(errors.email[0]); 
      } else if (errors.password) {
        alert(errors.password[0]);
      } else {
        alert("Validation failed. Please check your input.");
      }
    } else if (err.response?.data?.error) {
      alert(err.response.data.error);
    } else {
      alert("Registration failed. Please try again.");
    }
  }
};



  return (
    <div className="flex items-center justify-center min-h-screen bg-gray-50">
     <div className="w-full max-w-md p-6 bg-white rounded-lg shadow-md">
      <h2 className="text-2xl font-semibold text-gray-800 text-center mb-6">Register</h2>
       <form onSubmit={handleSubmit} className="space-y-4">
        <input
         name="name"
         placeholder="Name"
         onChange={handleChange}
         required
         className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-400"
       />
       <input
         name="email"
         placeholder="Email"
         onChange={handleChange}
         required
         className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-400"
       />
       <input
        name="password"
        type="password"
        placeholder="Password"
        onChange={handleChange}
        required
        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-400"
       />
       <select
        name="role"
        onChange={handleChange}
        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-400"
       >
        <option value="donor">Donor</option>
        <option value="receiver">Receiver</option>
       </select>
       <input
        name="blood_group"
        placeholder="Blood Group"
        onChange={handleChange}
        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-400"
       />
       <input
        name="location"
        placeholder="Location"
        onChange={handleChange}
        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-400"
       />
       <button
        type="submit"
        className="w-full py-2 bg-blue-500 text-white font-medium rounded-md hover:bg-blue-600 transition-colors"
       >
        Register
       </button>
    </form>
      <p className="mt-4 text-sm text-center text-gray-600">
        Already have an account?{" "}
       <a href="/login" className="text-blue-500 hover:underline">
        Login
       </a>
     </p>
   </div>
 </div>
  );
}
