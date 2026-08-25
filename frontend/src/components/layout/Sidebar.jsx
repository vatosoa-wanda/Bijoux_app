import { NavLink } from 'react-router-dom';

const NAV_ITEMS = [
  { to: '/', icon: '🏠', label: 'Tableau de bord' },
  { to: '/matieres', icon: '📦', label: 'Matières premières' },
  { to: '/stock', icon: '📊', label: 'Stock & mouvements' },
  { to: '/bijoux', icon: '💎', label: 'Fiches bijoux' },
  { to: '/production', icon: '🛠️', label: 'Production' },
  { to: '/qualite', icon: '🔍', label: 'Qualité & Stats' },
];

export default function Sidebar() {
  return (
    <aside className="w-64 bg-vert-sauge text-white flex flex-col shrink-0">
      <div className="flex items-center gap-2 px-6 py-6 text-lg font-semibold">
        <span>💎</span>
        <span>PolyBijoux</span>
      </div>
      <nav className="flex flex-col gap-1 px-3">
        {NAV_ITEMS.map((item) => (
          <NavLink
            key={item.to}
            to={item.to}
            end={item.to === '/'}
            className={({ isActive }) =>
              `flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors ${
                isActive ? 'bg-white/20' : 'hover:bg-white/10'
              }`
            }
          >
            <span>{item.icon}</span>
            <span>{item.label}</span>
          </NavLink>
        ))}
      </nav>
    </aside>
  );
}