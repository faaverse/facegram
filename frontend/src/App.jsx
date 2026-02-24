import Register from "./page/Register";
import Login from "./page/Login";
import { createBrowserRouter } from "react-router";
import { RouterProvider } from "react-router/dom";
import Profile from "./page/Profile";
import Home from "./page/Home";

const router = createBrowserRouter([
  {
    path: "/register",
    element: <Register />,
  },
  {
    path: "/",
    element: <Login />,
  },
  {
    path: "/profile",
    element: <Profile />,
  },
  {
    path: "/home",
    element: <Home />,
  },
]);
function App() {
  return (
    <div>
      <RouterProvider router={router} />,
    </div>
  );
}

export default App;
