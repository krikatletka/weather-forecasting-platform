import weatherData from "../data/weatherData";

import CitySelector from "../components/CitySelector";
import WeatherCard from "../components/WeatherCard";
import ForecastCard from "../components/ForecastCard";
import AccuracyCard from "../components/AccuracyCard";

function Home({
  cities,
  selectedCity,
  setSelectedCity,
  currentWeather,
  weatherLoading,
  weatherError,
}) {
  const weatherId = selectedCity?.weatherDataId;

  const selectedForecast = weatherId ? weatherData.forecasts[weatherId] : null;

  const selectedAccuracy = weatherId ? weatherData.accuracy[weatherId] : null;

  const hasForecast = Boolean(selectedForecast?.length);
  const hasAccuracy = Boolean(selectedAccuracy?.daily?.length);

  return (
    <main className="home-page">
      <div className="page-heading">
        <div>
          <p className="page-label">ПОГОДНИЙ МОНІТОРИНГ</p>
          <h1>Огляд погоди</h1>
          <p>Поточні показники та прогнозування погоди</p>
        </div>

        <CitySelector
          cities={cities}
          selectedCity={selectedCity}
          setSelectedCity={setSelectedCity}
        />
      </div>

      {weatherLoading && (
        <p className="weather-status">Завантаження погодних даних...</p>
      )}

      {weatherError && <p className="weather-error">{weatherError}</p>}

      <WeatherCard
        city={selectedCity}
        forecast={selectedForecast || []}
        currentWeather={currentWeather}
      />

      {hasForecast && (
        <ForecastCard
          forecast={selectedForecast}
          windSpeed={selectedCity.wind}
        />
      )}

      {hasAccuracy && (
        <AccuracyCard accuracy={selectedAccuracy.daily.slice(-5)} />
      )}

      {!hasForecast && !hasAccuracy && (
        <p className="weather-status">
          Для цього міста поки немає тестових прогнозів і статистики.
        </p>
      )}
    </main>
  );
}

export default Home;
