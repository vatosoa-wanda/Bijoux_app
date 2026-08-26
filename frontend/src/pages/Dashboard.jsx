import { useEffect, useState } from 'react';
import apiClient from '../api/client';

const KPI_CARDS = [
  { key: 'bijoux_en_cours', icon: '🔄', label: 'Ordres en cours' },
  { key: 'ordres_termines', icon: '✅', label: 'Ordres terminés' },
  { key: 'total_rejetes', icon: '❌', label: 'Bijoux rejetés', warning: true },
  { key: 'cout_moyen_bijou', icon: '💶', label: 'Coût moyen / bijou', suffix: ' €' },
  { key: 'produits_en_stock', icon: '📦', label: 'Produits en stock' },
];

export default function Dashboard() {
  const [alertes, setAlertes] = useState([]);
  const [kpi, setKpi] = useState(null);

  useEffect(() => {
    apiClient.get('/matieres/alertes').then((res) => setAlertes(res.data.data));
    apiClient.get('/dashboard/kpi').then((res) => setKpi(res.data.data));
  }, []);

  return (
    <div className="space-y-8">
      {/* Alertes */}
      <section>
        <h2 className="text-lg font-semibold mb-3">⚠️ Alertes</h2>
        <div className="space-y-2">
          {alertes.length === 0 && (
            <p className="text-brun-doux/50 text-sm">Aucune alerte de stock actuellement.</p>
          )}
          {alertes.map((a) => (
            <div
              key={a.id_matiere}
              className="flex items-center justify-between bg-rose-poudre/30 px-4 py-3 rounded-lg"
            >
              <span className="text-sm">
                ⚠ <strong>{a.nom}</strong> : {a.quantite_stock} restant (seuil {a.seuil_alerte})
              </span>
            </div>
          ))}
        </div>
      </section>

      {/* KPI */}
      <section>
        <h2 className="text-lg font-semibold mb-3">📊 Indicateurs clés</h2>
        <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
          {KPI_CARDS.map((card) => {
            const value = kpi ? kpi[card.key] : null;
            return (
              <div
                key={card.key}
                className={`bg-white rounded-xl shadow-sm p-4 flex items-center gap-3 ${
                  card.warning && value > 0 ? 'ring-2 ring-rose-poudre' : ''
                }`}
              >
                <span className="text-2xl">{card.icon}</span>
                <div>
                  <div className="text-lg font-semibold text-brun-doux">
                    {value === null ? '—' : `${value}${card.suffix ?? ''}`}
                  </div>
                  <div className="text-xs text-brun-doux/60">{card.label}</div>
                </div>
              </div>
            );
          })}
        </div>
      </section>
    </div>
  );
}