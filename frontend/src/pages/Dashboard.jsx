import { useEffect, useState } from 'react';
import apiClient from '../api/client';

export default function Dashboard() {
  const [alertes, setAlertes] = useState([]);

  useEffect(() => {
    apiClient.get('/matieres/alertes').then((res) => setAlertes(res.data.data));
  }, []);

  return (
    <div>
      <h2 className="text-lg font-semibold mb-4">⚠️ Alertes</h2>
      <div className="space-y-2">
        {alertes.length === 0 && (
          <p className="text-brun-doux/50 text-sm">Aucune alerte de stock actuellement.</p>
        )}
        {alertes.map((a) => (
          <div key={a.id_matiere} className="flex items-center justify-between bg-rose-poudre/30 px-4 py-3 rounded-lg">
            <span className="text-sm">
              ⚠ <strong>{a.nom}</strong> : {a.quantite_stock} restant (seuil {a.seuil_alerte})
            </span>
          </div>
        ))}
      </div>
    </div>
  );
}