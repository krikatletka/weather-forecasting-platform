import { useState, useEffect } from "react";
import weatherData from "../data/weatherData";

const API_URL = "http://localhost:8000/api";

// Перетворення даних із Laravel у формат компонента
function formatRule(rule) {
  return {
    id: rule.id,
    cityId: Number(rule.city_id),
    parameter: rule.parameter === "wind_speed" ? "wind" : rule.parameter,
    condition:
      rule.operator === "<" || rule.operator === "<=" ? "below" : "above",
    value: Number(rule.threshold_value),
    enabled: Boolean(rule.is_active),
  };
}

function Alerts({ cities = weatherData.cities, token }) {
  const [rules, setRules] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  const [cityId, setCityId] = useState(String(cities[0]?.id ?? ""));
  const [parameter, setParameter] = useState("temperature");
  const [condition, setCondition] = useState("below");
  const [value, setValue] = useState("");

  const parameterNames = {
    temperature: "Температура",
    wind: "Швидкість вітру",
    humidity: "Вологість",
    precipitation: "Опади",
  };

  const parameterUnits = {
    temperature: "°C",
    wind: "км/год",
    humidity: "%",
    precipitation: "мм",
  };

  const conditionNames = {
    below: "нижче",
    above: "вище",
  };

  // Заголовки запитів із токеном авторизації
  function getHeaders() {
    return {
      "Content-Type": "application/json",
      Accept: "application/json",
      Authorization: `Bearer ${token}`,
    };
  }

  // Отримання повідомлення про помилку Laravel
  async function getErrorMessage(response) {
    const result = await response.json();

    if (result.errors) {
      return Object.values(result.errors).flat().join("\n");
    }

    return result.message || "Сталася помилка. Спробуйте ще раз.";
  }

  // Завантаження правил із бази даних
  useEffect(() => {
    async function fetchRules() {
      if (!token) {
        setLoading(false);
        setError("Потрібно увійти в обліковий запис.");
        return;
      }

      try {
        setLoading(true);
        setError("");

        const response = await fetch(`${API_URL}/alerts`, {
          headers: getHeaders(),
        });

        if (!response.ok) {
          throw new Error(await getErrorMessage(response));
        }

        const result = await response.json();

        setRules(result.data.map(formatRule));
      } catch (error) {
        setError(error.message);
      } finally {
        setLoading(false);
      }
    }

    fetchRules();
  }, [token]);

  // Додавання нового правила
  async function addRule(event) {
    event.preventDefault();

    if (value.trim() === "" || !Number.isFinite(Number(value))) {
      return;
    }

    try {
      setError("");

      const response = await fetch(`${API_URL}/alerts`, {
        method: "POST",
        headers: getHeaders(),
        body: JSON.stringify({
          city_id: Number(cityId),
          parameter: parameter === "wind" ? "wind_speed" : parameter,
          operator: condition === "below" ? "<" : ">",
          threshold_value: Number(value),
          is_active: true,
        }),
      });

      if (!response.ok) {
        throw new Error(await getErrorMessage(response));
      }

      const result = await response.json();

      setRules((previousRules) => [formatRule(result.data), ...previousRules]);

      setValue("");
    } catch (error) {
      setError(error.message);
    }
  }

  // Видалення правила
  async function deleteRule(id) {
    try {
      setError("");

      const response = await fetch(`${API_URL}/alerts/${id}`, {
        method: "DELETE",
        headers: getHeaders(),
      });

      if (!response.ok) {
        throw new Error(await getErrorMessage(response));
      }

      setRules((previousRules) =>
        previousRules.filter((rule) => rule.id !== id),
      );
    } catch (error) {
      setError(error.message);
    }
  }

  // Увімкнення та вимкнення правила
  async function toggleRule(id) {
    const rule = rules.find((item) => item.id === id);

    if (!rule) return;

    try {
      setError("");

      const response = await fetch(`${API_URL}/alerts/${id}`, {
        method: "PUT",
        headers: getHeaders(),
        body: JSON.stringify({
          is_active: !rule.enabled,
        }),
      });

      if (!response.ok) {
        throw new Error(await getErrorMessage(response));
      }

      const result = await response.json();

      setRules((previousRules) =>
        previousRules.map((item) =>
          item.id === id ? formatRule(result.data) : item,
        ),
      );
    } catch (error) {
      setError(error.message);
    }
  }

  return (
    <main className="home-page">
      <div className="page-heading">
        <div>
          <p className="page-label">КОНТРОЛЬ ПОГОДИ</p>
          <h1>Сповіщення</h1>
          <p>Створення правил для погодних умов</p>
        </div>
      </div>

      <div className="accuracy-card">
        <div className="section-heading">
          <div>
            <h2>Створити правило</h2>
            <p>Вкажіть умови, за яких потрібно створити сповіщення</p>
          </div>
        </div>

        <form className="alert-form" onSubmit={addRule}>
          <div className="alert-field">
            <label htmlFor="alert-city">Місто</label>

            <select
              id="alert-city"
              value={cityId}
              onChange={(event) => setCityId(event.target.value)}
            >
              {cities.map((city) => (
                <option key={city.id} value={city.id}>
                  {city.name}
                </option>
              ))}
            </select>
          </div>

          <div className="alert-field">
            <label htmlFor="alert-parameter">Параметр</label>

            <select
              id="alert-parameter"
              value={parameter}
              onChange={(event) => setParameter(event.target.value)}
            >
              <option value="temperature">Температура</option>
              <option value="wind">Швидкість вітру</option>
              <option value="humidity">Вологість</option>
              <option value="precipitation">Опади</option>
            </select>
          </div>

          <div className="alert-field">
            <label htmlFor="alert-condition">Умова</label>

            <select
              id="alert-condition"
              value={condition}
              onChange={(event) => setCondition(event.target.value)}
            >
              <option value="below">Нижче</option>
              <option value="above">Вище</option>
            </select>
          </div>

          <div className="alert-field">
            <label htmlFor="alert-value">
              Значення ({parameterUnits[parameter]})
            </label>

            <input
              id="alert-value"
              type="number"
              value={value}
              onChange={(event) => setValue(event.target.value)}
              placeholder="Наприклад, 10"
              required
            />
          </div>

          <button className="add-alert-button" type="submit">
            + Додати правило
          </button>
        </form>
      </div>

      <div className="accuracy-card">
        <div className="section-heading">
          <div>
            <h2>Мої правила</h2>
            <p>
              Усього правил: {rules.length} · Активних:{" "}
              {rules.filter((rule) => rule.enabled).length}
            </p>
          </div>
        </div>

        {error && <p className="empty-alerts">{error}</p>}

        {loading ? (
          <p className="empty-alerts">Завантаження правил...</p>
        ) : rules.length === 0 ? (
          <p className="empty-alerts">Ви ще не створили жодного правила.</p>
        ) : (
          <div className="alert-list">
            {rules.map((rule) => {
              const city = cities.find(
                (item) => Number(item.id) === rule.cityId,
              );

              return (
                <div className="alert-item" key={rule.id}>
                  <div>
                    <strong>
                      {parameterNames[rule.parameter] || rule.parameter}
                    </strong>

                    <p>
                      {city?.name || "Невідоме місто"}:{" "}
                      {conditionNames[rule.condition] || rule.condition}{" "}
                      {rule.value} {parameterUnits[rule.parameter] || ""}
                    </p>

                    <span
                      className={
                        rule.enabled
                          ? "alert-status active"
                          : "alert-status disabled"
                      }
                    >
                      {rule.enabled ? "Увімкнено" : "Вимкнено"}
                    </span>
                  </div>

                  <div className="alert-actions">
                    <button
                      type="button"
                      className="alert-toggle-button"
                      onClick={() => toggleRule(rule.id)}
                    >
                      {rule.enabled ? "Вимкнути" : "Увімкнути"}
                    </button>

                    <button
                      type="button"
                      className="delete-alert-button"
                      onClick={() => deleteRule(rule.id)}
                    >
                      Видалити
                    </button>
                  </div>
                </div>
              );
            })}
          </div>
        )}
      </div>
    </main>
  );
}

export default Alerts;
