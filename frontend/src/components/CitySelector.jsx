import { useState } from "react";

function CitySelector({ cities, selectedCity, setSelectedCity }) {
  const [search, setSearch] = useState("");
  const [isOpen, setIsOpen] = useState(false);

  const filteredCities = cities.filter((city) =>
    city.name.toLowerCase().includes(search.toLowerCase()),
  );

  function selectCity(city) {
    setSelectedCity(city);
    setSearch("");
    setIsOpen(false);
  }

  return (
    <div className="city-selector">
      <label htmlFor="city-search">Пошук міста:</label>

      <div className="city-search-wrapper">
        <input
          id="city-search"
          type="text"
          placeholder={selectedCity.name}
          value={search}
          onChange={(event) => {
            setSearch(event.target.value);
            setIsOpen(true);
          }}
          onFocus={() => setIsOpen(true)}
        />

        {isOpen && (
          <div className="city-suggestions">
            {filteredCities.length > 0 ? (
              filteredCities.map((city) => (
                <button
                  type="button"
                  key={city.id}
                  onClick={() => selectCity(city)}
                  className={
                    city.id === selectedCity.id
                      ? "city-suggestion active"
                      : "city-suggestion"
                  }
                >
                  {city.name}
                </button>
              ))
            ) : (
              <p className="city-no-results">Місто не знайдено</p>
            )}

            <button
              type="button"
              className="city-close"
              onClick={() => {
                setSearch("");
                setIsOpen(false);
              }}
            >
              Закрити
            </button>
          </div>
        )}
      </div>
    </div>
  );
}

export default CitySelector;
