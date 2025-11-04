import React from "react";
import { motion } from "framer-motion";

const fadeInUp = {
  hidden: { opacity: 0, y: 40 },
  visible: { opacity: 1, y: 0, transition: { duration: 0.6 } },
};

const LandingPage = () => {
  const features = [
    {
      title: "Donor Requests",
      icon: "🩸",
      desc: "Receive requests from nearby receivers in real-time to save lives efficiently.",
    },
    {
      title: "Receiver Search",
      icon: "🔍",
      desc: "Search for available blood in nearby blood banks or from donors quickly.",
    },
    {
      title: "Blood Availability Alerts",
      icon: "⚡",
      desc: "Get notified when your blood type is in demand for timely donations.",
    },
    {
      title: "Profile Management",
      icon: "👤",
      desc: "Manage your details, blood type, and donation history easily.",
    },
    {
      title: "Donation History Tracking",
      icon: "📋",
      desc: "View your past donations and requests for a transparent record.",
    },
    {
      title: "Participate in Campaign",
      icon: "📅",
      desc: "Join donation campaigns and contribute to organized blood drives.",
    },
  ];

  return (
    <div className="font-sans text-gray-800 bg-[#DAADAD] min-h-screen">
      {/* Hero Section */}
      <section className="py-32">
        <div className="max-w-6xl mx-auto bg-gray-200 rounded-3xl shadow-lg p-10 flex flex-col md:flex-row items-center gap-10">
          {/* Text */}
          <motion.div
            className="md:w-1/2"
            initial="hidden"
            animate="visible"
            variants={fadeInUp}
          >
            <h1 className="text-5xl md:text-6xl font-bold leading-tight mb-6 text-red-600">
              Donate Blood, Save Lives
            </h1>
            <p className="text-lg md:text-xl mb-8">
              Connect donors with receivers and save lives efficiently through our platform.
            </p>
            <div className="flex gap-4 flex-wrap">
              <a
                href="/register"
                className="bg-red-600 text-white px-8 py-3 rounded-xl font-semibold shadow-lg hover:scale-105 transition transform"
              >
                Get Started
              </a>
              <a
                href="#features"
                className="border border-red-600 text-red-600 px-8 py-3 rounded-xl font-semibold hover:bg-red-600 hover:text-white transition"
              >
                Learn More
              </a>
            </div>
          </motion.div>

          {/* Image */}
          <motion.div
            className="md:w-1/2 flex justify-center"
            initial={{ opacity: 0, x: 100 }}
            animate={{ opacity: 1, x: 0 }}
            transition={{ duration: 0.8 }}
          >
            <img
              src="/blood.jpg"
              alt="Blood Donation Illustration"
              className="w-full max-w-md rounded-3xl shadow-xl object-cover"
            />
          </motion.div>
        </div>
      </section>

      {/* Features Section */}
      <section id="features" className="py-24">
        <div className="max-w-6xl mx-auto bg-gray-200 rounded-3xl shadow-lg p-10 text-center">
          <h2 className="text-4xl font-bold mb-12 text-red-600">Features</h2>
          <div className="grid md:grid-cols-3 gap-8">
            {features.map((feature, i) => (
              <motion.div
                key={i}
                className="p-8 bg-white border rounded-2xl hover:shadow-2xl transition transform hover:-translate-y-2"
                initial={{ opacity: 0, y: 40 }}
                whileInView={{ opacity: 1, y: 0 }}
                viewport={{ once: true }}
                transition={{ duration: 0.5, delay: i * 0.2 }}
              >
                <div className="text-5xl mb-4">{feature.icon}</div>
                <h3 className="text-xl font-semibold mb-2 text-red-600">
                  {feature.title}
                </h3>
                <p>{feature.desc}</p>
              </motion.div>
            ))}
          </div>
        </div>
      </section>
      <section
        id="how-it-works"
        className="py-24"
      >
        <div className="max-w-6xl mx-auto bg-gray-200 rounded-3xl shadow-lg p-10 text-center">
          <h2 className="text-4xl font-bold mb-12 text-red-600">How It Works</h2>
          <div className="grid md:grid-cols-3 gap-10">
            {[
              {
                step: "Sign Up",
                desc: "Create an account as a donor or receiver to start using the system.",
              },
              {
                step: "Connect",
                desc: "Donors receive requests, and receivers search for available blood or request nearby donors.",
              },
              {
                step: "Save Lives",
                desc: "Efficient blood distribution ensures patients receive blood when they need it most.",
              },
            ].map((item, i) => (
              <motion.div
                key={i}
                className="p-8 bg-white border rounded-2xl hover:shadow-2xl transition transform hover:-translate-y-2"
                initial={{ opacity: 0, y: 40 }}
                whileInView={{ opacity: 1, y: 0 }}
                viewport={{ once: true }}
                transition={{ duration: 0.5, delay: i * 0.2 }}
              >
                <h3 className="text-xl font-semibold mb-2 text-red-600">{item.step}</h3>
                <p>{item.desc}</p>
              </motion.div>
            ))}
          </div>
        </div>
      </section>

      {/* CTA Section */}
      <section className="py-24">
        <div className="max-w-4xl mx-auto bg-gray-200 rounded-3xl shadow-lg p-12 text-center">
          <h2 className="text-4xl font-bold mb-4 text-red-600">
            Ready to Make a Difference?
          </h2>
          <p className="mb-6 font-serif text-lg">
            Join now and help save lives through our Blood Bank Management System.
          </p>
          <a
            href="/register"
            className="bg-red-600 text-white px-8 py-4 rounded-xl font-semibold shadow-lg hover:bg-red-700 transition"
          >
            Sign Up Today
          </a>
        </div>
      </section>
    </div>
  );
};

export default LandingPage;
