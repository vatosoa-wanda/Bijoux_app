import { createBrowserRouter, RouterProvider } from 'react-router-dom';
import Layout from './components/layout/Layout';
import Dashboard from './pages/Dashboard';
import MatieresPremieres from './pages/MatieresPremieres';
import StockMouvements from './pages/StockMouvements';
import FichesBijoux from './pages/FichesBijoux';
import Production from './pages/Production';
import QualiteStatistiques from './pages/QualiteStatistiques';

const router = createBrowserRouter([
  {
    path: '/',
    element: <Layout />,
    children: [
      { index: true, element: <Dashboard />, handle: { title: 'Tableau de bord' } },
      { path: 'matieres', element: <MatieresPremieres />, handle: { title: 'Matières premières' } },
      { path: 'stock', element: <StockMouvements />, handle: { title: 'Stock & mouvements' } },
      { path: 'bijoux', element: <FichesBijoux />, handle: { title: 'Fiches bijoux' } },
      { path: 'production', element: <Production />, handle: { title: 'Production' } },
      { path: 'qualite', element: <QualiteStatistiques />, handle: { title: 'Qualité & Statistiques' } },
    ],
  },
]);

export default function App() {
  return <RouterProvider router={router} />;
}