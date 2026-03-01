import axios from "axios";
import { useEffect, useState } from "react";

export default function Home() {
  const [posts, setPosts] = useState([]);
  const [loading, setLoading] = useState(true);

  const getPosts = async () => {
    try {
      const token = localStorage.getItem("token");

      const response = await axios.get("http://127.0.0.1:8000/api/auth/hp", {
        headers: {
          Authorization: `Bearer ${token}`,
          Accept: "application/json",
        },
      });

      console.log("Response:", response.data);
      setPosts(response.data.data || []);
    } catch (error) {
      console.log("Error fetching posts:", error);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    getPosts();
  }, []);

  const baseUrl = "http://127.0.0.1:8000/storage/";

  if (loading) {
    return (
      <div
        style={{
          padding: "20px",
          fontFamily: "system-ui, -apple-system, sans-serif",
        }}
      >
        <h1>Loading...</h1>
      </div>
    );
  }

  return (
    <div
      style={{
        padding: "20px",
        fontFamily: "system-ui, -apple-system, sans-serif",
      }}
    >
      <h1 style={{ fontSize: "24px", marginBottom: "20px" }}>Home</h1>

      {posts.length === 0 ? (
        <p>No posts yet</p>
      ) : (
        <div style={{ display: "flex", flexDirection: "column", gap: "30px" }}>
          {posts.map((post, index) => (
            <div
              key={index}
              style={{ border: "1px solid #ccc", padding: "15px" }}
            >
              <div style={{ marginBottom: "10px" }}>
                <span style={{ fontWeight: "bold" }}>{post.name}</span>
                <span style={{ color: "#666", marginLeft: "10px" }}>
                  @{post.username}
                </span>
              </div>

              <div style={{ marginBottom: "10px" }}>
                <div style={{ marginBottom: "10px" }}>
                  {post.attachments && post.attachments.length > 0 ? (
                    post.attachments.map((file, i) => (
                      <img
                        key={i}
                        src={`${baseUrl}${file.storage_path}`}
                        alt={post.caption}
                        style={{
                          maxWidth: "100%",
                          maxHeight: "400px",
                          objectFit: "contain",
                          border: "1px solid #eee",
                          marginBottom: "10px",
                        }}
                      />
                    ))
                  ) : (
                    <p>No Image</p>
                  )}
                </div>
              </div>

              <div style={{ marginBottom: "5px" }}>
                <p style={{ margin: "0 0 5px 0" }}>{post.caption}</p>
              </div>

              <div style={{ fontSize: "12px", color: "#666" }}>
                {new Date(post.created_at).toLocaleString()}
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
