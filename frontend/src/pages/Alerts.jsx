import { useState, useEffect } from "react";
import weatherData from "../data/weatherData";

function Alerts({ selectedCity, userEmail }) {
  const [rules, setRules] = useState(() => {
    try {
      const savedRules = localStorage.getItem(`weather-alerts-${userEmail}`);
      return savedRules ? JSON.parse(savedRules) : [];
    } catch {
      return [];
    }
  });

  const [cityId, setCityId] = useState(String(selectedCity.id));
  const [parameter, setParameter] = useState("temperature");
  const [condition, setCondition] = useState("below");
  const [value, setValue] = useState("");

  useEffect(() => {
    localStorage.setItem(`weather-alerts-${userEmail}`, JSON.stringify(rules));
  }, [rules, userEmail]);

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

  function addRule(event) {
    event.preventDefault();

    if (value.trim() === "") {
      return;
    }

    const newRule = {
      id: Date.now(),
      cityId: Number(cityId),
      parameter,
      condition,
      value: Number(value),
      enabled: true,
    };

    setRules((previousRules) => [...previousRules, newRule]);
    setValue("");
  }

  function deleteRule(id) {
    setRules((previousRules) => previousRules.filter((rule) => rule.id !== id));
  }

  function toggleRule(id) {
    setRules((previousRules) =>
      previousRules.map((rule) =>
        rule.id === id ? { ...rule, enabled: !rule.enabled } : rule,
      ),
    );
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
              {weatherData.cities.map((city) => (
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

        {rules.length === 0 ? (
          <p className="empty-alerts">Ви ще не створили жодного правила.</p>
        ) : (
          <div className="alert-list">
            {rules.map((rule) => {
              const city = weatherData.cities.find(
                (item) => item.id === rule.cityId,
              );

              return (
                <div className="alert-item" key={rule.id}>
                  <div>
                    <strong>{parameterNames[rule.parameter]}</strong>

                    <p>
                      {city?.name || "Невідоме місто"}:{" "}
                      {conditionNames[rule.condition]} {rule.value}{" "}
                      {parameterUnits[rule.parameter]}
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
