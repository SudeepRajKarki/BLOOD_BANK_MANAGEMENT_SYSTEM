function Unauthorized() {
  return (
    <div className="flex flex-col items-center justify-center min-h-screen bg-gray-100">
      <h1 className="text-4xl font-bold text-red-600 mb-4">Access Denied 🚫</h1>
      <p className="text-gray-700 mb-6">You are not authorized to view this page.</p>
      <a
        href="/"
        className="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"
      >
        Go Back Home
      </a>
    </div>
  );
}
export default Unauthorized
