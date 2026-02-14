import { useState } from "react";
import { useNavigate } from "react-router";
import axios from "axios";

export default function Login() {
  const navigate = useNavigate();
  const [form, setForm] = useState({ username: "", password: "" });
  const [message, setMessage] = useState("");

  const handleChange = (e) => {
    setForm({ ...form, [e.target.name]: e.target.value });
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setMessage("Loading...");

    try {
      const login = await axios.post(
        "http://127.0.0.1:8000/api/auth/login",
        form,
        { headers: { Accept: "application/json" } }
      );

      localStorage.setItem("token", login.data.token);

      navigate(`/profile`);
    } catch (err) {
      setMessage(err.response?.data?.message || "Login gagal");
    }
  };

  return (
    <div>
      <h2>Login</h2>

      <form onSubmit={handleSubmit}>
        <div>
          <label>Username</label><br />
          <input name="username" value={form.username} onChange={handleChange} />
        </div>

        <div>
          <label>Password</label><br />
          <input type="password" name="password" value={form.password} onChange={handleChange} />
        </div>

        <br />
        <button>Login</button>
      </form>

      {message && <p>{message}</p>}
    </div>
  );
}
