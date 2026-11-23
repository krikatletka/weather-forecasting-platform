import { useState, useEffect } from "react";

import Header from "./components/Header";
import Sidebar from "./components/Sidebar";

import Home from "./pages/Home";
import Statistics from "./pages/Statistics";
import Alerts from "./pages/Alerts";
import Login from "./pages/Login";
import Register from "./pages/Register";

import weatherData from "./data/weatherData";

const API_URL = "http://localhost:8000/api";

function App() {
  const [activePage, setActivePage] = useState("home");

  const [user, setUser] = useState(() => {
    const savedUser = localStorage.getItem("weather-user");
    return savedUser ? JSON.parse(savedUser) : null;
  });

  const [token, setToken] = useState(() =>
    localStorage.getItem("weather-token"),
  );

  const [theme, setTheme] = useState(() => {
    return localStorage.getItem("weather-theme") || "dark";
  });

  const [cities, setCities] = useState(
    weatherData.cities.map((city) => ({
      ...city,
      weatherDataId: city.id,
      apiCity: false,
    })),
  );

  const [citiesLoaded, setCitiesLoaded] = useState(false);
  const [selectedCity, setSelectedCity] = useState(weatherData.cities[0]);

  const [weatherResult, setWeatherResult] = useState(null);
  const [weatherRequestError, setWeatherRequestError] = useState(null);

  const currentWeather =
    weatherResult?.cityId === selectedCity?.id ? weatherResult.data : null;

  const weatherError =
    weatherRequestError?.cityId === selectedCity?.id
      ? weatherRequestError.message
      : "";

  const weatherLoading =
    citiesLoaded && selectedCity?.apiCity && !currentWeather && !weatherError;

  // Отримання повідомлення про помилку від Laravel
  async function getErrorMessage(response) {
    const result = await response.json();

    if (result.errors) {
      return Object.values(result.errors).flat().join("\n");
    }

    return result.message || "Сталася помилка. Спробуйте ще раз.";
  }

  // Отримання списку міст із Laravel API
  useEffect(() => {
    async function fetchCities() {
      try {
        const response = await fetch(`${API_URL}/cities`);

        if (!response.ok) {
          throw new Error("Не вдалося отримати список міст");
        }

        const result = await response.json();

        const apiCities = result.data.map((apiCity) => {
          const mockCity = weatherData.cities.find(
            (city) => city.name.toLowerCase() === apiCity.name.toLowerCase(),
          );

          return {
            ...mockCity,
            ...apiCity,
            weatherDataId: mockCity?.id ?? null,
            apiCity: true,
          };
        });

        setCities(apiCities);

        setSelectedCity((currentCity) => {
          const savedCityName = user
            ? localStorage.getItem(`weather-city-${user.email}`)
            : null;

          return (
            apiCities.find((city) => city.name === savedCityName) ||
            apiCities.find((city) => city.name === currentCity.name) ||
            apiCities[0]
          );
        });
      } catch (error) {
        console.error("Помилка завантаження міст:", error);

        setCities(
          weatherData.cities.map((city) => ({
            ...city,
            weatherDataId: city.id,
            apiCity: false,
          })),
        );
      } finally {
        setCitiesLoaded(true);
      }
    }

    fetchCities();
  }, []);

  // Отримання погоди для вибраного міста
  useEffect(() => {
    if (!citiesLoaded || !selectedCity?.apiCity) {
      return;
    }

    const controller = new AbortController();

    async function fetchWeather() {
      try {
        const response = await fetch(`${API_URL}/weather/${selectedCity.id}`, {
          signal: controller.signal,
        });

        if (!response.ok) {
          throw new Error("Не вдалося отримати погодні дані");
        }

        const result = await response.json();

        if (!controller.signal.aborted) {
          setWeatherResult({
            cityId: selectedCity.id,
            data: result.weather,
          });

          setWeatherRequestError(null);
        }
      } catch (error) {
        if (error.name !== "AbortError" && !controller.signal.aborted) {
          console.error("Помилка завантаження погоди:", error);

          setWeatherRequestError({
            cityId: selectedCity.id,
            message: error.message,
          });
        }
      }
    }

    fetchWeather();

    return () => controller.abort();
  }, [selectedCity, citiesLoaded]);

  // Збереження теми
  useEffect(() => {
    localStorage.setItem("weather-theme", theme);
  }, [theme]);

  // Збереження вибраного міста
  useEffect(() => {
    if (user && citiesLoaded && selectedCity) {
      localStorage.setItem(`weather-city-${user.email}`, selectedCity.name);
    }
  }, [selectedCity, user, citiesLoaded]);

  function toggleTheme() {
    setTheme((currentTheme) => (currentTheme === "dark" ? "light" : "dark"));
  }

  function loadUserCity(email) {
    const savedCity = localStorage.getItem(`weather-city-${email}`);

    const cityByName = cities.find((city) => city.name === savedCity);

    const cityByOldId = weatherData.cities.find(
      (city) => city.id === Number(savedCity),
    );

    const city =
      cityByName ||
      cities.find((item) => item.name === cityByOldId?.name) ||
      cities[0];

    setSelectedCity(city || weatherData.cities[0]);
  }

  // Збереження авторизованого користувача
  function saveUser(userData, userToken) {
    setUser(userData);
    setToken(userToken);

    localStorage.setItem("weather-user", JSON.stringify(userData));
    localStorage.setItem("weather-token", userToken);

    loadUserCity(userData.email);
    setActivePage("home");
  }

  // Вхід через Laravel API
  async function handleLogin(email, password) {
    try {
      const response = await fetch(`${API_URL}/login`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Accept: "application/json",
        },
        body: JSON.stringify({ email, password }),
      });

      if (!response.ok) {
        throw new Error(await getErrorMessage(response));
      }

      const result = await response.json();

      saveUser(result.user, result.token);
    } catch (error) {
      alert(error.message);
    }
  }

  // Реєстрація через Laravel API
  async function handleRegister(name, email, password, confirmPassword) {
    try {
      const response = await fetch(`${API_URL}/register`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Accept: "application/json",
        },
        body: JSON.stringify({
          name,
          email,
          password,
          password_confirmation: confirmPassword,
        }),
      });

      if (!response.ok) {
        throw new Error(await getErrorMessage(response));
      }

      const result = await response.json();

      saveUser(result.user, result.token);
    } catch (error) {
      alert(error.message);
    }
  }

  // Вихід через Laravel API
  async function handleLogout() {
    try {
      if (token) {
        await fetch(`${API_URL}/logout`, {
          method: "POST",
          headers: {
            Accept: "application/json",
            Authorization: `Bearer ${token}`,
          },
        });
      }
    } catch (error) {
      console.error("Помилка виходу:", error);
    } finally {
      setUser(null);
      setToken(null);

      localStorage.removeItem("weather-user");
      localStorage.removeItem("weather-token");

      setSelectedCity(cities[0] || weatherData.cities[0]);
      setActivePage("home");
    }
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
              cities={cities}
              selectedCity={selectedCity}
              setSelectedCity={setSelectedCity}
              currentWeather={currentWeather}
              weatherLoading={weatherLoading}
              weatherError={weatherError}
            />
          )}

          {activePage === "statistics" && (
            <Statistics
              cities={cities}
              selectedCity={selectedCity}
              setSelectedCity={setSelectedCity}
            />
          )}

          {activePage === "alerts" && (
            <Alerts
              cities={cities}
              selectedCity={selectedCity}
              userEmail={user.email}
              token={token}
            />
          )}
        </div>
      </div>
    </div>
  );
}

export default App;
