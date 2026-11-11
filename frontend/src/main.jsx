import React from "react";
import ReactDOM from "react-dom/client";
import App from "./App";

import "./styles/global.css";
import "./styles/header.css";
import "./styles/sidebar.css";
import "./styles/layout.css";
import "./styles/home.css";
import "./styles/statistics.css";
import "./styles/alerts.css";
import "./styles/auth.css";
import "./styles/responsive.css";

ReactDOM.createRoot(document.getElementById("root")).render(
  <React.StrictMode>
    <App />
  </React.StrictMode>,
);
