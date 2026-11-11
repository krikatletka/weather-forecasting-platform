import weatherData from "../data/weatherData";
import CitySelector from "../components/CitySelector";
import WeatherCard from "../components/WeatherCard";
import ForecastCard from "../components/ForecastCard";
import AccuracyCard from "../components/AccuracyCard";

function Home({ selectedCity, setSelectedCity }) {
  const selectedForecast = weatherData.forecasts[selectedCity.id];

  return (
    <main className="home-page">
      <div className="page-heading">
        <div>
          <p className="page-label">ПОГОДНИЙ МОНІТОРИНГ</p>
          <h1>Огляд погоди</h1>
          <p>Поточні показники та прогнозування погоди</p>
        </div>

        <CitySelector
          cities={weatherData.cities}
          selectedCity={selectedCity}
          setSelectedCity={setSelectedCity}
        />
      </div>

      <WeatherCard city={selectedCity} forecast={selectedForecast} />

      <ForecastCard forecast={selectedForecast} windSpeed={selectedCity.wind} />

      <AccuracyCard
        accuracy={weatherData.accuracy[selectedCity.id].daily.slice(-5)}
      />
    </main>
  );
}

export default Home;
