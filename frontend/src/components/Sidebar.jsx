function Sidebar({ activePage, setActivePage }) {
  const menuItems = [
    { id: "home", icon: "⌂", label: "Головна" },
    { id: "statistics", icon: "▥", label: "Статистика" },
    { id: "alerts", icon: "♧", label: "Сповіщення" },
  ];

  return (
    <aside className="sidebar">
      <p className="sidebar-title">МЕНЮ</p>

      <nav>
        {menuItems.map((item) => (
          <button
            key={item.id}
            className={`menu-item ${activePage === item.id ? "active" : ""}`}
            onClick={() => setActivePage(item.id)}
          >
            <span className="menu-icon">{item.icon}</span>
            {item.label}
          </button>
        ))}
      </nav>

      <div className="sidebar-bottom">
        <span>WeatherTrack</span>
        <small>Версія 1.0</small>
      </div>
    </aside>
  );
}

export default Sidebar;
