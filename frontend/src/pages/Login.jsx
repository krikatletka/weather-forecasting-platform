import { useState } from "react";

function Login({ onLogin, goToRegister }) {
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");

  function handleSubmit(event) {
    event.preventDefault();

    if (!email || !password) {
      alert("Заповніть усі поля!");
      return;
    }

    onLogin(email);
  }

  return (
    <main className="auth-page">
      <div className="auth-card">
        <div className="auth-icon">
          <img src="/weather-icon.png" alt="WeatherTrack" />
        </div>

        <h1>Вхід у систему</h1>
        <p>Увійдіть до свого облікового запису WeatherTrack</p>

        <form onSubmit={handleSubmit}>
          <label>Електронна пошта</label>
          <input
            type="email"
            placeholder="example@email.com"
            value={email}
            onChange={(event) => setEmail(event.target.value)}
            required
          />

          <label>Пароль</label>
          <input
            type="password"
            placeholder="Введіть пароль"
            value={password}
            onChange={(event) => setPassword(event.target.value)}
            required
          />

          <button type="submit" className="auth-button">
            Увійти
          </button>
        </form>

        <div className="auth-switch">
          Ще не маєте облікового запису?
          <button onClick={goToRegister}>Зареєструватися</button>
        </div>
      </div>
    </main>
  );
}

export default Login;
