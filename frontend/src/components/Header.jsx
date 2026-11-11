function Header({ theme, onToggleTheme, user, onLogout }) {
  const userName = user.name || user.email;
  const avatarLetter = userName.charAt(0).toUpperCase();

  return (
    <header className="header">
      <div className="header-logo">
        <img src="/weather-icon.png" alt="WeatherTrack" className="logo-icon" />

        <div>
          <h1>WeatherTrack</h1>
          <p>Моніторинг погоди та прогнозування</p>
        </div>
      </div>

      <div className="header-actions">
        <button className="theme-toggle" onClick={onToggleTheme}>
          {theme === "dark" ? "☀ Світла тема" : "☾ Темна тема"}
        </button>

        <div className="header-user">
          <div className="user-avatar">{avatarLetter}</div>

          <div className="user-details">
            <span className="user-name">{userName}</span>
            <span className="user-status">Ви увійшли</span>
          </div>

          <button className="logout-button" onClick={onLogout}>
            Вийти
          </button>
        </div>
      </div>
    </header>
  );
}

export default Header;
