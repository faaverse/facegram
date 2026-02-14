import { useEffect, useState } from "react";
import axios from "axios";

export default function Profile() {
  const [user, setUser] = useState(null);
  const [message, setMessage] = useState("Loading...");

  useEffect(() => {
    const fetchMe = async () => {
      try {
        const res = await axios.get(
          "http://127.0.0.1:8000/api/auth/me",
          {
            headers: {
              Authorization: `Bearer ${localStorage.getItem("token")}`,
              Accept: "application/json",
            },
          }
        );

        setUser(res.data.user);
        setMessage("");
      } catch (err) {
        console.log(err);
        
        setMessage("Harap login kembali");
      }
    };

    fetchMe();
  }, []);

  if (message) return <p>{message}</p>;

  return (
    <div>
      <h2>My Profile</h2>

      <p><b>Nama:</b> {user.name}</p>
      <p><b>Username:</b> {user.username}</p>
      <p><b>Bio:</b> {user.bio}</p>
      <p><b>Private:</b> {user.is_private ? "Yes" : "No"}</p>
    </div>
  );
}
