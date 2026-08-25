import { Outlet, useMatches } from 'react-router-dom';
import Sidebar from './Sidebar';
import Header from './Header';

export default function Layout() {
  const matches = useMatches();
  const title = matches.at(-1)?.handle?.title ?? 'PolyBijoux';

  return (
    <div className="flex min-h-screen bg-creme">
      <Sidebar />
      <div className="flex-1 flex flex-col">
        <Header title={title} />
        <main className="flex-1 p-8">
          <Outlet />
        </main>
      </div>
    </div>
  );
}