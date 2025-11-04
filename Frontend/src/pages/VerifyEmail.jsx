import { useEffect, useState } from "react";
import { useSearchParams } from "react-router-dom";
import api from "../api/axios";

export default function VerifyEmail() {
  const [searchParams] = useSearchParams();
  const [message, setMessage] = useState("");

  useEffect(() => {
    const token = searchParams.get("token");
    if (token) {
      api.get(`/verify-email?token=${token}`)
        .then(res => setMessage(res.data.message))
        .catch(err => setMessage("Invalid or expired token"));
    }
  }, []);

  return <div><h3>{message}</h3></div>;
}
