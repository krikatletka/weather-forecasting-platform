import { useState, useEffect } from "react";

import Header from "./components/Header";
import Sidebar from "./components/Sidebar";

import Home from "./pages/Home";
import Statistics from "./pages/Statistics";
import Alerts from "./pages/Alerts";
import Login from "./pages/Login";
import Register from "./pages/Register";

import weatherData from "./data/weatherData";

function App() {
  const [activePage, setActivePage] = useState("home");
  const [user, setUser] = useState(null);

  const [theme, setTheme] = useState(() => {
    return localStorage.getItem("weather-theme") || "dark";
  });

  const [selectedCity, setSelectedCity] = useState(weatherData.cities[0]);

  useEffect(() => {
    localStorage.setItem("weather-theme", theme);
  }, [theme]);

  useEffect(() => {
    if (user) {
      localStorage.setItem(`weather-city-${user.email}`, selectedCity.id);
    }
  }, [selectedCity, user]);

  function toggleTheme() {
    setTheme((currentTheme) => (currentTheme === "dark" ? "light" : "dark"));
  }

  function loadUserCity(email) {
    const savedCityId = localStorage.getItem(`weather-city-${email}`);

    const savedCity = weatherData.cities.find(
      (city) => city.id === Number(savedCityId),
    );

    setSelectedCity(savedCity || weatherData.cities[0]);
  }

  function handleLogin(email) {
    loadUserCity(email);
    setUser({ email });
    setActivePage("home");
  }

  function handleRegister(name, email) {
    loadUserCity(email);
    setUser({ name, email });
    setActivePage("home");
  }

  function handleLogout() {
    setUser(null);
    setSelectedCity(weatherData.cities[0]);
    setActivePage("home");
  }

  if (!user) {
    return (
      <div className="app" data-theme={theme}>
        <button
          className="theme-toggle auth-theme-toggle"
          onClick={toggleTheme}
        >
          {theme === "dark" ? "☀ Світла тема" : "☾ Темна тема"}
        </button>

        {activePage === "register" ? (
          <Register
            onRegister={handleRegister}
            goToLogin={() => setActivePage("login")}
          />
        ) : (
          <Login
            onLogin={handleLogin}
            goToRegister={() => setActivePage("register")}
          />
        )}
      </div>
    );
  }

  return (
    <div className="app" data-theme={theme}>
      <Header
        theme={theme}
        onToggleTheme={toggleTheme}
        user={user}
        onLogout={handleLogout}
      />

      <div className="app-layout">
        <Sidebar activePage={activePage} setActivePage={setActivePage} />

        <div className="content-area">
          {activePage === "home" && (
            <Home
              selectedCity={selectedCity}
              setSelectedCity={setSelectedCity}
            />
          )}

          {activePage === "statistics" && (
            <Statistics
              selectedCity={selectedCity}
              setSelectedCity={setSelectedCity}
            />
          )}

          {activePage === "alerts" && (
            <Alerts selectedCity={selectedCity} userEmail={user.email} />
          )}
        </div>
      </div>
    </div>
  );
}

export default App;
