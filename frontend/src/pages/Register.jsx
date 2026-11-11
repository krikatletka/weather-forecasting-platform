import { useState } from "react";

function Register({ onRegister, goToLogin }) {
  const [name, setName] = useState("");
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [confirmPassword, setConfirmPassword] = useState("");

  function handleSubmit(event) {
    event.preventDefault();

    if (password.length < 6) {
      alert("Пароль повинен містити щонайменше 6 символів!");
      return;
    }

    if (password !== confirmPassword) {
      alert("Паролі не співпадають!");
      return;
    }

    onRegister(name, email);
  }

  return (
    <main className="auth-page">
      <div className="auth-card">
        <div className="auth-icon">☀</div>

        <h1>Реєстрація</h1>
        <p>Створіть обліковий запис WeatherTrack</p>

        <form onSubmit={handleSubmit}>
          <label>Ваше ім'я</label>
          <input
            type="text"
            placeholder="Введіть ім'я"
            value={name}
            onChange={(event) => setName(event.target.value)}
            required
          />

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
            placeholder="Мінімум 6 символів"
            value={password}
            onChange={(event) => setPassword(event.target.value)}
            required
          />

          <label>Підтвердження пароля</label>
          <input
            type="password"
            placeholder="Повторіть пароль"
            value={confirmPassword}
            onChange={(event) => setConfirmPassword(event.target.value)}
            required
          />

          <button type="submit" className="auth-button">
            Зареєструватися
          </button>
        </form>

        <div className="auth-switch">
          Вже маєте обліковий запис?
          <button onClick={goToLogin}>Увійти</button>
        </div>
      </div>
    </main>
  );
}

export default Register;
