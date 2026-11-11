import { useState, useEffect } from "react";

function ForecastCard({ forecast, windSpeed }) {
  const [currentTime, setCurrentTime] = useState(new Date());

  useEffect(() => {
    const timer = setInterval(() => {
      setCurrentTime(new Date());
    }, 30000);

    return () => clearInterval(timer);
  }, []);

  const currentHour = currentTime.getHours();

  const hourlyForecast = Array.from({ length: 24 }, (_, index) => {
    const hour = (currentHour + index) % 24;

    const item = forecast.find(
      (entry) => Number(entry.time.slice(0, 2)) === hour,
    );

    return {
      time: `${String(hour).padStart(2, "0")}:00`,
      temperature: item?.temperature ?? 0,
      icon: item?.icon ?? "☁️",
    };
  });

  const temperatures = hourlyForecast.map((item) => item.temperature);

  const min = Math.min(...temperatures) - 2;
  const max = Math.max(...temperatures) + 2;
  const range = max - min || 1;

  const slotWidth = 87;

  const points = hourlyForecast.map((item, index) => {
    const x = index * slotWidth + 37.5;
    const y = 140 - ((item.temperature - min) / range) * 100;

    return {
      x,
      y,
      temperature: item.temperature,
    };
  });

  const line = points.map((point) => `${point.x},${point.y}`).join(" ");

  const area = `37.5,170 ${line} ${points[23].x},170`;

  return (
    <div className="forecast-card">
      <div className="section-heading">
        <div>
          <h2>Прогноз на 24 години</h2>
          <p>Погодинна зміна температури та швидкості вітру</p>
        </div>

        <span className="forecast-badge">
          Зараз{" "}
          {currentTime.toLocaleTimeString("uk-UA", {
            hour: "2-digit",
            minute: "2-digit",
          })}
        </span>
      </div>

      <div className="forecast-timeline-scroll">
        <div className="forecast-timeline-content">
          <div className="temperature-chart">
            <svg viewBox="0 0 2088 190" className="temperature-line">
              <defs>
                <linearGradient
                  id="temperatureGradient"
                  x1="0"
                  y1="0"
                  x2="0"
                  y2="1"
                >
                  <stop offset="0%" stopColor="#55aaff" stopOpacity="0.3" />
                  <stop offset="100%" stopColor="#55aaff" stopOpacity="0" />
                </linearGradient>
              </defs>

              {[40, 85, 130, 175].map((y) => (
                <line
                  key={y}
                  x1="0"
                  y1={y}
                  x2="2088"
                  y2={y}
                  stroke="currentColor"
                  opacity="0.12"
                  strokeDasharray="5 6"
                />
              ))}

              <polygon points={area} fill="url(#temperatureGradient)" />

              <polyline
                points={line}
                fill="none"
                stroke="#55aaff"
                strokeWidth="3"
                strokeLinecap="round"
                strokeLinejoin="round"
              />

              {points.map((point, index) => (
                <g key={index}>
                  <line
                    x1={point.x}
                    y1={point.y + 8}
                    x2={point.x}
                    y2="180"
                    stroke="currentColor"
                    opacity="0.15"
                    strokeDasharray="3 5"
                  />

                  <text
                    x={point.x}
                    y={point.y - 13}
                    textAnchor="middle"
                    fill="currentColor"
                    fontSize="13"
                    fontWeight="600"
                  >
                    {point.temperature}°
                  </text>

                  <circle
                    cx={point.x}
                    cy={point.y}
                    r={index === 0 ? 6 : 4}
                    fill={index === 0 ? "#ffffff" : "#55aaff"}
                    stroke="#55aaff"
                    strokeWidth="2"
                  />
                </g>
              ))}
            </svg>
          </div>

          <div className="forecast-list">
            {hourlyForecast.map((item, index) => (
              <div
                className={`forecast-item ${
                  index === 0 ? "forecast-current" : ""
                }`}
                key={item.time}
              >
                <span className="forecast-time">
                  {index === 0 ? "Зараз" : item.time}
                </span>

                <span className="forecast-icon">{item.icon}</span>

                <strong className="forecast-wind">
                  <span>↗</span> {windSpeed} км/год
                </strong>
              </div>
            ))}
          </div>
        </div>
      </div>
    </div>
  );
}

export default ForecastCard;
