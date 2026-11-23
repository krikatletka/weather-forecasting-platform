
// Час для погодинного прогнозу
const forecastHours = [
  "00:00", "01:00", "02:00", "03:00",
  "04:00", "05:00", "06:00", "07:00",
  "08:00", "09:00", "10:00", "11:00",
  "12:00", "13:00", "14:00", "15:00",
  "16:00", "17:00", "18:00", "19:00",
  "20:00", "21:00", "22:00", "23:00",
];

// Створення погодинного прогнозу
function createForecast(temperatures, icons) {
  return forecastHours.map((time, index) => ({
    time,
    temperature: temperatures[index],
    icon: icons[index],
  }));
}

// Створення демонстраційних даних для статистики
function createDailyAccuracy(predicted, actual) {
  return predicted.map((temperature, index) => {
    const date = new Date();
    date.setDate(date.getDate() - (predicted.length - 1 - index));

    return {
      date: date.toLocaleDateString("uk-UA", {
        day: "2-digit",
        month: "2-digit",
      }),
      predicted: temperature,
      actual: actual[index],
    };
  });
}

// Створення погодинного порівняння
function createHourlyAccuracy(forecast, differences) {
  return forecast.map((item, index) => ({
    date: item.time,
    predicted: item.temperature,
    actual: item.temperature + differences[index],
  }));
}

const weatherData = {
  cities: [
    {
      id: 1,
      name: "Харків",
      temperature: 12,
      feelsLike: 10,
      condition: "Мінлива хмарність",
      humidity: 65,
      pressure: 1015,
      wind: 12,
      windDirection: "Північно-західний",
      precipitation: 0,
      icon: "⛅",
    },
    {
      id: 2,
      name: "Київ",
      temperature: 14,
      feelsLike: 13,
      condition: "Ясно",
      humidity: 55,
      pressure: 1018,
      wind: 8,
      windDirection: "Західний",
      precipitation: 0,
      icon: "☀️",
    },
    {
      id: 3,
      name: "Львів",
      temperature: 9,
      feelsLike: 7,
      condition: "Хмарно",
      humidity: 78,
      pressure: 1012,
      wind: 15,
      windDirection: "Південний",
      precipitation: 1.2,
      icon: "☁️",
    },
  ],

  // Прогноз для кожного міста
  forecasts: {
    1: createForecast(
      [
        8, 8, 7, 7, 7, 8, 9, 10,
        11, 12, 12, 12, 12, 13, 14, 13,
        11, 10, 9, 8, 8, 7, 7, 7,
      ],
      [
        "☁️", "☁️", "☁️", "☁️", "☁️", "⛅",
        "⛅", "⛅", "⛅", "⛅", "⛅", "⛅",
        "⛅", "☀️", "☀️", "⛅", "☁️", "☁️",
        "☁️", "☁️", "☁️", "☁️", "☁️", "☁️",
      ]
    ),

    2: createForecast(
      [
        10, 10, 9, 9, 9, 10, 11, 12,
        13, 14, 15, 15, 14, 15, 16, 15,
        14, 13, 12, 11, 11, 10, 10, 9,
      ],
      [
        "☀️", "☀️", "☀️", "☀️", "☀️", "☀️",
        "☀️", "☀️", "☀️", "☀️", "☀️", "☀️",
        "☀️", "☀️", "☀️", "☀️", "⛅", "⛅",
        "⛅", "☀️", "☀️", "☀️", "☀️", "☀️",
      ]
    ),

    3: createForecast(
      [
        6, 6, 5, 5, 5, 6, 7, 8,
        8, 9, 9, 10, 9, 10, 11, 10,
        9, 8, 8, 7, 7, 6, 6, 6,
      ],
      [
        "☁️", "☁️", "☁️", "☁️", "☁️", "☁️",
        "☁️", "☁️", "⛅", "⛅", "☁️", "☁️",
        "☁️", "⛅", "⛅", "☁️", "☁️", "☁️",
        "🌧️", "🌧️", "☁️", "☁️", "☁️", "☁️",
      ]
    ),
  },

  // Порівняння прогнозу та фактичної температури
  accuracy: {
    1: {
      daily: createDailyAccuracy(
        [
          12, 14, 11, 9, 13, 12, 10,
          11, 13, 15, 14, 12, 10, 9,
          11, 13, 12, 14, 15, 13, 11,
          10, 12, 14, 13, 11, 9, 10,
          12, 13,
        ],
        [
          13, 13, 10, 10, 12, 13, 11,
          12, 12, 14, 15, 11, 11, 8,
          12, 14, 11, 13, 14, 12, 12,
          9, 13, 13, 12, 12, 10, 11,
          11, 14,
        ]
      ),
      hourly: createHourlyAccuracy(
        []
      ),
    },

    2: {
      daily: createDailyAccuracy(
        [
          15, 16, 14, 12, 14, 15, 13,
          14, 16, 17, 15, 13, 12, 11,
          14, 15, 16, 14, 13, 12, 14,
          15, 17, 16, 14, 13, 12, 14,
          15, 16,
        ],
        [
          14, 15, 15, 13, 13, 16, 12,
          15, 15, 16, 14, 14, 11, 12,
          13, 16, 15, 13, 14, 11, 15,
          14, 16, 15, 15, 12, 13, 13,
          14, 17,
        ]
      ),
      hourly: [],
    },

    3: {
      daily: createDailyAccuracy(
        [
          8, 9, 7, 6, 10, 8, 7,
          9, 10, 11, 8, 7, 6, 8,
          9, 10, 11, 9, 8, 7, 9,
          10, 8, 7, 9, 11, 10, 8,
          7, 9,
        ],
        [
          7, 10, 8, 5, 9, 9, 8,
          8, 11, 10, 9, 6, 7, 9,
          8, 11, 10, 8, 9, 6, 10,
          9, 9, 8, 10, 10, 9, 9,
          8, 10,
        ]
      ),
      hourly: [],
    },
  },
};

export default weatherData;
