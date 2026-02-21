import { useState } from "react";
import axios from "axios";

export default function Register() {
  const [form, setForm] = useState({
    name: "",
    bio: "",
    username: "",
    password: "",
  });

  const [message, setMessage] = useState("");

  const handleChange = (e) => {
    setForm({
      ...form,
      [e.target.name]: e.target.value,
    });
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setMessage("Loading...");

    try {
      const res = await axios.post(
        "http://127.0.0.1:8000/api/auth/register",
        form
      );

      console.log(res.data);
      setMessage("Register berhasil!");
    } catch (err) {
      console.log(err.response?.data);
      setMessage("Register gagal!");
    }
  };

  return (
    <div>
      <h2>Register</h2>

      <form onSubmit={handleSubmit}>
        <div>
          <label>Nama</label><br />
          <input type="text" name="name" value={form.name} onChange={handleChange} />
        </div>

        <div>
          <label>Bio</label><br />
          <textarea name="bio" value={form.bio} onChange={handleChange} />
        </div>
    
        <div>
          <label>Username</label><br />
          <input type="text" name="username" value={form.username} onChange={handleChange} />
        </div>

        <div>
          <label>Password</label><br />
          <input type="password" name="password" value={form.password} onChange={handleChange} />
        </div>

        <br />
        <button type="submit">Register</button>
      </form>

      {message && <p>{message}</p>}
    </div>
  );
}
