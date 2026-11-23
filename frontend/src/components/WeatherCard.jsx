import { useState, useEffect } from "react";

function WeatherCard({ city, forecast = [], currentWeather = null }) {
  const [currentTime, setCurrentTime] = useState(new Date());

  useEffect(() => {
    const timer = setInterval(() => {
      setCurrentTime(new Date());
    }, 30000);

    return () => clearInterval(timer);
  }, []);

  const currentHour = currentTime.getHours();

  const currentForecast = forecast.find((item) => {
    return item.time && Number(item.time.slice(0, 2)) === currentHour;
  });

  // Визначення джерела погодних даних
  const hasApiData = Boolean(currentWeather);

  const temperature = hasApiData
    ? Number(currentWeather.temperature)
    : (currentForecast?.temperature ?? city.temperature ?? null);

  const humidity = hasApiData ? currentWeather.humidity : city.humidity;

  const wind = hasApiData ? currentWeather.wind_speed : city.wind;

  const pressure = hasApiData ? currentWeather.surface_pressure : city.pressure;

  const precipitation = hasApiData
    ? currentWeather.precipitation
    : city.precipitation;

  // Перетворення погодного коду API на іконку
  function getWeatherIcon(code) {
    if (code === 0) return "☀️";
    if ([1, 2].includes(code)) return "⛅";
    if (code === 3) return "☁️";
    if ([45, 48].includes(code)) return "🌫️";
    if ([51, 53, 55, 56, 57].includes(code)) return "🌦️";

    if ([61, 63, 65, 66, 67, 80, 81, 82].includes(code)) {
      return "🌧️";
    }

    if ([71, 73, 75, 77, 85, 86].includes(code)) {
      return "❄️";
    }

    if ([95, 96, 99].includes(code)) {
      return "⛈️";
    }

    return "🌤️";
  }

  const icon = hasApiData
    ? getWeatherIcon(Number(currentWeather.weather_code))
    : (currentForecast?.icon ?? city.icon ?? "🌤️");

  function getCondition(weatherIcon) {
    if (weatherIcon === "☀️") return "Ясно";
    if (weatherIcon === "⛅") return "Мінлива хмарність";
    if (weatherIcon === "☁️") return "Хмарно";
    if (weatherIcon === "🌫️") return "Туман";
    if (weatherIcon === "🌦️") return "Мряка";
    if (weatherIcon === "🌧️") return "Дощ";
    if (weatherIcon === "❄️") return "Сніг";
    if (weatherIcon === "⛈️") return "Гроза";

    return city.condition || "Невідомо";
  }

  const feelsLike = hasApiData
    ? temperature
    : currentForecast
      ? temperature
      : (city.feelsLike ?? temperature);

  // Перетворення напрямку вітру з градусів у сторони світу
  function getWindDirection(degrees) {
    if (degrees === null || degrees === undefined) {
      return "";
    }

    const directions = ["Пн", "ПнСх", "Сх", "ПдСх", "Пд", "ПдЗх", "Зх", "ПнЗх"];

    const index = Math.round(Number(degrees) / 45) % 8;

    return directions[index];
  }

  const windDirection = hasApiData
    ? getWindDirection(currentWeather.wind_direction)
    : city.windDirection || "";

  function formatValue(value, digits = 0) {
    if (value === null || value === undefined || value === "") {
      return "—";
    }

    const number = Number(value);

    if (!Number.isFinite(number)) {
      return "—";
    }

    return digits > 0 ? number.toFixed(digits) : number;
  }

  return (
    <div className="weather-card">
      <div className="weather-card-top">
        <div>
          <p className="card-label">ПОТОЧНА ПОГОДА</p>
          <h2>{city.name}</h2>
          <p className="weather-condition">{getCondition(icon)}</p>
        </div>

        <div className="weather-icon">{icon}</div>
      </div>

      <div className="temperature">
        {formatValue(temperature, 1)}°<span>C</span>
      </div>

      <p className="feels-like">
        Відчувається як {formatValue(feelsLike, 1)}°C
      </p>

      <div className="weather-details">
        <div className="weather-detail">
          <span>💧</span>
          <div>
            <small>Вологість</small>
            <strong>{formatValue(humidity)}%</strong>
          </div>
        </div>

        <div className="weather-detail">
          <span>🌬️</span>
          <div>
            <small>Вітер</small>
            <strong>
              {formatValue(wind)} км/год {windDirection}
            </strong>
          </div>
        </div>

        <div className="weather-detail">
          <span>🌡️</span>
          <div>
            <small>Тиск</small>
            <strong>{formatValue(pressure)} гПа</strong>
          </div>
        </div>

        <div className="weather-detail">
          <span>🌧️</span>
          <div>
            <small>Опади</small>
            <strong>{formatValue(precipitation, 2)} мм</strong>
          </div>
        </div>
      </div>

      {hasApiData && currentWeather.recorded_at && (
        <p className="weather-data-source">
          Дані з бази:{" "}
          {new Date(currentWeather.recorded_at).toLocaleString("uk-UA")}
        </p>
      )}
    </div>
  );
}

export default WeatherCard;
