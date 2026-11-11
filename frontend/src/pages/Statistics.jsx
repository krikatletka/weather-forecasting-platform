import { useState } from "react";
import weatherData from "../data/weatherData";
import CitySelector from "../components/CitySelector";

function Statistics({ selectedCity, setSelectedCity }) {
  const [period, setPeriod] = useState("7");

  const cityAccuracy = weatherData.accuracy[selectedCity.id];
  const cityForecast = weatherData.forecasts[selectedCity.id];

  const statistics =
    period === "24"
      ? cityForecast.map((item, index) => {
          const differences = [1, -1, 0, 2, -1, 1];
          const actual =
            item.temperature + differences[index % differences.length];

          return {
            date: item.time,
            predicted: item.temperature,
            actual,
            difference: Math.abs(item.temperature - actual),
          };
        })
      : cityAccuracy.daily.slice(-Number(period)).map((item) => ({
          ...item,
          difference: Math.abs(item.predicted - item.actual),
        }));

  const averagePredicted =
    statistics.reduce((sum, item) => sum + item.predicted, 0) /
    statistics.length;

  const averageActual =
    statistics.reduce((sum, item) => sum + item.actual, 0) / statistics.length;

  const averageDifference =
    statistics.reduce((sum, item) => sum + item.difference, 0) /
    statistics.length;

  return (
    <main className="home-page">
      <div className="page-heading">
        <div>
          <p className="page-label">АНАЛІЗ ПОГОДИ</p>
          <h1>Статистика</h1>
          <p>Порівняння прогнозованих та фактичних показників</p>
        </div>

        <CitySelector
          cities={weatherData.cities}
          selectedCity={selectedCity}
          setSelectedCity={setSelectedCity}
        />
      </div>

      <div className="statistics-controls">
        <label htmlFor="statistics-period">Період:</label>

        <select
          id="statistics-period"
          value={period}
          onChange={(event) => setPeriod(event.target.value)}
        >
          <option value="24">24 години</option>
          <option value="7">7 днів</option>
          <option value="30">30 днів</option>
        </select>
      </div>

      <div className="statistics-summary">
        <div className="statistic-box">
          <span>🌤️</span>
          <p>Середній прогноз</p>
          <h2>{averagePredicted.toFixed(1)}°C</h2>
        </div>

        <div className="statistic-box">
          <span>🌡️</span>
          <p>Фактична температура</p>
          <h2>{averageActual.toFixed(1)}°C</h2>
        </div>

        <div className="statistic-box">
          <span>📊</span>
          <p>Середнє відхилення</p>
          <h2>{averageDifference.toFixed(1)}°C</h2>
        </div>
      </div>

      <div className="accuracy-card">
        <div className="section-heading">
          <div>
            <h2>Прогнозовано vs Фактично</h2>
            <p>
              {selectedCity.name} — порівняння температури за вибраний період
            </p>
          </div>
        </div>

        <div className="statistics-table">
          <div className="statistics-table-row table-header">
            <span>{period === "24" ? "Час" : "Дата"}</span>
            <span>Прогнозовано</span>
            <span>Фактично</span>
            <span>Відхилення</span>
          </div>

          {statistics.map((item, index) => (
            <div className="statistics-table-row" key={index}>
              <span>{item.date}</span>
              <span>{item.predicted}°C</span>
              <span>{item.actual}°C</span>
              <span className="difference-value">{item.difference}°C</span>
            </div>
          ))}
        </div>
      </div>
    </main>
  );
}

export default Statistics;
