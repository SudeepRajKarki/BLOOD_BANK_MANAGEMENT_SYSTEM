import PrivateNavbar from "../components/privatenavbar";

function Dashboard() {
   const userRole = localStorage.getItem("role");
  return (
    <>
      <PrivateNavbar role={userRole} />
      {/* Dashboard content here */}
    </>
  );
}
export default Dashboard
