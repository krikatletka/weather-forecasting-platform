function AccuracyCard({ accuracy }) {
  return (
    <div className="accuracy-card">
      <div className="section-heading">
        <div>
          <h2>Точність прогнозування</h2>
          <p>Порівняння прогнозованої та фактичної температури</p>
        </div>
      </div>

      <div className="accuracy-list">
        {accuracy.map((item, index) => (
          <div className="accuracy-row" key={index}>
            <span>{item.date}</span>

            <div className="accuracy-values">
              <span>
                Прогноз: <strong>{item.predicted}°C</strong>
              </span>

              <span>
                Факт: <strong>{item.actual}°C</strong>
              </span>
            </div>

            <span
              className={
                item.predicted === item.actual
                  ? "accuracy-status good"
                  : "accuracy-status"
              }
            >
              {item.predicted === item.actual
                ? "Збіг"
                : `Різниця ${Math.abs(item.predicted - item.actual)}°`}
            </span>
          </div>
        ))}
      </div>
    </div>
  );
}

export default AccuracyCard;
