import { useState, useEffect } from "react";

function WeatherCard({ city, forecast }) {
  const [currentTime, setCurrentTime] = useState(new Date());

  useEffect(() => {
    const timer = setInterval(() => {
      setCurrentTime(new Date());
    }, 30000);

    return () => clearInterval(timer);
  }, []);

  const currentHour = currentTime.getHours();

  const currentForecast = forecast.find((item) => {
    return Number(item.time.slice(0, 2)) === currentHour;
  });

  const temperature = currentForecast
    ? currentForecast.temperature
    : city.temperature;

  const icon = currentForecast ? currentForecast.icon : city.icon;

  function getCondition(weatherIcon) {
    if (weatherIcon === "☀️") return "Ясно";
    if (weatherIcon === "⛅") return "Мінлива хмарність";
    if (weatherIcon === "☁️") return "Хмарно";
    if (weatherIcon === "🌧️") return "Дощ";
    return city.condition;
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
        {temperature}°<span>C</span>
      </div>

      <p className="feels-like">Відчувається як {city.feelsLike}°C</p>

      <div className="weather-details">
        <div className="weather-detail">
          <span>💧</span>
          <div>
            <small>Вологість</small>
            <strong>{city.humidity}%</strong>
          </div>
        </div>

        <div className="weather-detail">
          <span>🌬️</span>
          <div>
            <small>Вітер</small>
            <strong>{city.wind} км/год</strong>
          </div>
        </div>

        <div className="weather-detail">
          <span>🌡️</span>
          <div>
            <small>Тиск</small>
            <strong>{city.pressure} гПа</strong>
          </div>
        </div>

        <div className="weather-detail">
          <span>🌧️</span>
          <div>
            <small>Опади</small>
            <strong>{city.precipitation} мм</strong>
          </div>
        </div>
      </div>
    </div>
  );
}

export default WeatherCard;
