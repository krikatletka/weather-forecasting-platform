import { useState, useEffect } from "react";
import CitySelector from "../components/CitySelector";

const API_URL = "http://localhost:8000/api";

const algorithmNames = {
  historical_average: "Historical Average",
  similar_conditions: "Similar Conditions",
};

function Statistics({ cities, selectedCity, setSelectedCity }) {
  const [period, setPeriod] = useState("7");

  const [history, setHistory] = useState([]);
  const [accuracy, setAccuracy] = useState(null);
  const [comparison, setComparison] = useState(null);

  const [backtesting, setBacktesting] = useState(null);
  const [backtestingLoading, setBacktestingLoading] = useState(false);
  const [backtestingError, setBacktestingError] = useState("");

  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  // Завантаження звичайної статистики
  useEffect(() => {
    const controller = new AbortController();

    async function fetchStatistics() {
      setLoading(true);
      setError("");

      try {
        const cityId = selectedCity.id;
        const hours = period === "24" ? 24 : Number(period) * 24;

        const dateTo = new Date();
        const dateFrom = new Date();

        dateFrom.setDate(
          dateFrom.getDate() - (period === "24" ? 0 : Number(period) - 1),
        );

        const formatDate = (date) => {
          const year = date.getFullYear();
          const month = String(date.getMonth() + 1).padStart(2, "0");
          const day = String(date.getDate()).padStart(2, "0");

          return `${year}-${month}-${day}`;
        };

        const params = new URLSearchParams({
          city_id: String(cityId),
          date_from: formatDate(dateFrom),
          date_to: formatDate(dateTo),
        });

        const [historyResponse, accuracyResponse, comparisonResponse] =
          await Promise.all([
            fetch(`${API_URL}/weather/${cityId}/history?hours=${hours}`, {
              signal: controller.signal,
            }),

            fetch(`${API_URL}/predictions/accuracy?${params}`, {
              signal: controller.signal,
            }),

            fetch(`${API_URL}/predictions/comparison?${params}`, {
              signal: controller.signal,
            }),
          ]);

        if (!historyResponse.ok) {
          throw new Error("Не вдалося отримати історію погоди");
        }

        if (!accuracyResponse.ok || !comparisonResponse.ok) {
          throw new Error("Не вдалося отримати аналітику прогнозів");
        }

        const historyResult = await historyResponse.json();
        const accuracyResult = await accuracyResponse.json();
        const comparisonResult = await comparisonResponse.json();

        if (controller.signal.aborted) return;

        setHistory(historyResult.data || []);
        setAccuracy(accuracyResult.data);
        setComparison(comparisonResult.data);
      } catch (err) {
        if (err.name !== "AbortError" && !controller.signal.aborted) {
          setError(err.message);
        }
      } finally {
        if (!controller.signal.aborted) {
          setLoading(false);
        }
      }
    }

    fetchStatistics();

    return () => controller.abort();
  }, [selectedCity.id, period]);

  // Завантаження результатів backtesting
  useEffect(() => {
    const controller = new AbortController();

    async function fetchBacktesting() {
      setBacktestingLoading(true);
      setBacktestingError("");

      try {
        const response = await fetch(
          `${API_URL}/predictions/backtesting?city_id=${selectedCity.id}`,
          { signal: controller.signal },
        );

        if (!response.ok) {
          throw new Error(
            "Не вдалося отримати результати тестування алгоритмів",
          );
        }

        const result = await response.json();

        if (controller.signal.aborted) return;

        setBacktesting(result.data);
      } catch (err) {
        if (err.name !== "AbortError" && !controller.signal.aborted) {
          setBacktestingError(err.message);
        }
      } finally {
        if (!controller.signal.aborted) {
          setBacktestingLoading(false);
        }
      }
    }

    fetchBacktesting();

    return () => controller.abort();
  }, [selectedCity.id]);

  const formatNumber = (value) => {
    if (value === null || value === undefined) {
      return "—";
    }

    return Number(value).toFixed(2);
  };

  // Пошук результату конкретного алгоритму та горизонту
  const getBacktestingResult = (algorithm, horizon) => {
    return backtesting?.results?.find(
      (item) => item.algorithm === algorithm && item.horizon_hours === horizon,
    );
  };

  // Дані для графіка
  const horizons = [1, 6, 12, 24];

  const getBarWidth = (value, maxValue) => {
    if (value === null || value === undefined || maxValue <= 0) {
      return 0;
    }

    return Math.max(2, (value / maxValue) * 100);
  };

  const maxMae = Math.max(
    0,
    ...(backtesting?.results || []).map((item) => item.mae ?? 0),
  );

  return (
    <main className="home-page">
      <div className="page-heading">
        <div>
          <p className="page-label">АНАЛІЗ ПОГОДИ</p>
          <h1>Статистика</h1>
          <p>Аналіз погодних показників та точності прогнозування</p>
        </div>

        <CitySelector
          cities={cities}
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

      {loading && <p className="weather-status">Завантаження статистики...</p>}

      {error && <p className="weather-error">{error}</p>}

      {!loading && !error && (
        <>
          {/* Точність прогнозування */}
          <section className="accuracy-card">
            <div className="section-heading">
              <div>
                <h2>Точність прогнозування</h2>
                <p>{selectedCity.name} — результати перевірки прогнозів</p>
              </div>
            </div>

            <div className="statistics-summary">
              <div className="statistic-box">
                <span>📊</span>
                <p>Перевірено прогнозів</p>
                <h2>{accuracy?.overall?.total_checked ?? 0}</h2>
              </div>

              <div className="statistic-box">
                <span>🌡️</span>
                <p>Середня похибка MAE</p>
                <h2>
                  {accuracy?.overall?.mae !== null &&
                  accuracy?.overall?.mae !== undefined
                    ? `${formatNumber(accuracy.overall.mae)}°C`
                    : "Немає даних"}
                </h2>
              </div>
            </div>

            {(!accuracy?.overall?.total_checked ||
              accuracy.overall.total_checked === 0) && (
              <p className="weather-status">
                Поки немає перевірених прогнозів. Дані з'являться після
                отримання придатних фактичних вимірювань.
              </p>
            )}
          </section>

          {/* Порівняння алгоритмів за перевіреними прогнозами */}
          <section className="accuracy-card">
            <div className="section-heading">
              <div>
                <h2>Порівняння алгоритмів</h2>
                <p>
                  Чим менша середня абсолютна похибка MAE, тим точніший алгоритм
                </p>
              </div>
            </div>

            {comparison?.ranking?.length > 0 ? (
              <>
                <div className="statistics-summary">
                  <div className="statistic-box">
                    <span>🏆</span>
                    <p>Найкращий алгоритм</p>
                    <h2>{comparison.best_algorithm || "—"}</h2>
                  </div>
                </div>

                <div className="statistics-table">
                  <div className="statistics-table-row table-header">
                    <span>Алгоритм</span>
                    <span>Перевірено</span>
                    <span>MAE</span>
                  </div>

                  {comparison.ranking.map((item) => (
                    <div className="statistics-table-row" key={item.algorithm}>
                      <span>
                        {algorithmNames[item.algorithm] || item.algorithm}
                      </span>
                      <span>{item.total_checked}</span>
                      <span>
                        {item.mae !== null
                          ? `${formatNumber(item.mae)}°C`
                          : "—"}
                      </span>
                    </div>
                  ))}
                </div>
              </>
            ) : (
              <p className="weather-status">
                Порівняння поки недоступне: недостатньо перевірених прогнозів
                для обчислення похибки алгоритмів.
              </p>
            )}
          </section>

          {/* Backtesting */}
          <section className="accuracy-card backtesting-section">
            <div className="section-heading">
              <div>
                <p className="page-label">АРХІВНЕ ТЕСТУВАННЯ</p>
                <h2>Порівняння алгоритмів прогнозування</h2>
                <p>Перевірка алгоритмів на історичних погодних даних ERA5</p>
              </div>
            </div>

            {backtestingLoading && (
              <p className="weather-status">
                Виконується тестування алгоритмів...
              </p>
            )}

            {backtestingError && (
              <p className="weather-error">{backtestingError}</p>
            )}

            {!backtestingLoading && !backtestingError && backtesting && (
              <>
                <div className="statistics-summary">
                  <div className="statistic-box">
                    <span>🏙️</span>
                    <p>Перевірено міст</p>
                    <h2>{backtesting.tested_cities}</h2>
                  </div>

                  <div className="statistic-box">
                    <span>⏱️</span>
                    <p>Інтервал тестування</p>
                    <h2>Кожні {backtesting.stride_hours} год.</h2>
                  </div>
                </div>

                <h3 className="backtesting-subtitle">
                  Похибка MAE за часовими горизонтами
                </h3>

                <div className="backtesting-chart">
                  {horizons.map((horizon) => {
                    const historical = getBacktestingResult(
                      "historical_average",
                      horizon,
                    );

                    const similar = getBacktestingResult(
                      "similar_conditions",
                      horizon,
                    );

                    return (
                      <div className="backtesting-chart-group" key={horizon}>
                        <div className="backtesting-chart-label">
                          {horizon} год.
                        </div>

                        <div className="backtesting-bars">
                          <div className="backtesting-bar-row">
                            <span>Historical Average</span>

                            <div className="backtesting-bar-track">
                              <div
                                className="backtesting-bar historical-bar"
                                style={{
                                  width: `${getBarWidth(
                                    historical?.mae,
                                    maxMae,
                                  )}%`,
                                }}
                              />
                            </div>

                            <strong>{formatNumber(historical?.mae)}°C</strong>
                          </div>

                          <div className="backtesting-bar-row">
                            <span>Similar Conditions</span>

                            <div className="backtesting-bar-track">
                              <div
                                className="backtesting-bar similar-bar"
                                style={{
                                  width: `${getBarWidth(
                                    similar?.mae,
                                    maxMae,
                                  )}%`,
                                }}
                              />
                            </div>

                            <strong>{formatNumber(similar?.mae)}°C</strong>
                          </div>
                        </div>
                      </div>
                    );
                  })}
                </div>
                
                <div className="backtesting-legend">
                  <div className="backtesting-legend-item">
                    <span className="backtesting-legend-color historical" />
                    Historical Average
                  </div>

                  <div className="backtesting-legend-item">
                    <span className="backtesting-legend-color similar" />
                    Similar Conditions
                  </div>
                </div>

                <h3 className="backtesting-subtitle">Детальні результати</h3>

                <div className="statistics-table backtesting-table">
                  <div className="statistics-table-row table-header">
                    <span>Алгоритм</span>
                    <span>Горизонт</span>
                    <span>Тести</span>
                    <span>MAE</span>
                    <span>RMSE</span>
                  </div>

                  {backtesting.results.map((item, index) => (
                    <div
                      className="statistics-table-row"
                      key={`${item.algorithm}-${item.horizon_hours}-${index}`}
                    >
                      <span>
                        {algorithmNames[item.algorithm] || item.algorithm}
                      </span>
                      <span>{item.horizon_hours} год.</span>
                      <span>{item.count}</span>
                      <span>
                        {item.mae !== null
                          ? `${formatNumber(item.mae)}°C`
                          : "—"}
                      </span>
                      <span>
                        {item.rmse !== null
                          ? `${formatNumber(item.rmse)}°C`
                          : "—"}
                      </span>
                    </div>
                  ))}
                </div>

                <p className="backtesting-note">
                  Менші значення MAE та RMSE означають меншу похибку
                  прогнозування. Перевірка виконується на архівних даних ERA5,
                  які є даними реаналізу, а не незалежними вимірюваннями
                  метеостанцій.
                </p>
              </>
            )}
          </section>

          {/* Історія погоди */}
          <section className="accuracy-card">
            <div className="section-heading">
              <div>
                <h2>Історія погоди</h2>
                <p>{selectedCity.name} — погодні записи з бази даних</p>
              </div>
            </div>

            {history.length > 0 ? (
              <div className="statistics-table">
                <div className="statistics-table-row table-header">
                  <span>Дата та час</span>
                  <span>Температура</span>
                  <span>Вологість</span>
                  <span>Вітер</span>
                </div>

                {history.map((item) => (
                  <div className="statistics-table-row" key={item.id}>
                    <span>
                      {new Date(item.recorded_at).toLocaleString("uk-UA")}
                    </span>
                    <span>{item.temperature}°C</span>
                    <span>{item.humidity}%</span>
                    <span>{item.wind_speed} м/с</span>
                  </div>
                ))}
              </div>
            ) : (
              <p className="weather-status">
                За вибраний період записів не знайдено.
              </p>
            )}
          </section>
        </>
      )}
    </main>
  );
}

export default Statistics;
