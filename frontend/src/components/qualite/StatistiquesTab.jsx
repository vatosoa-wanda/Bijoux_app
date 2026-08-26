import { useEffect, useState } from 'react';
import { BarChart, Bar, XAxis, YAxis, Tooltip, ResponsiveContainer, PieChart, Pie, Cell, CartesianGrid } from 'recharts';
import apiClient from '../../api/client';

const COLORS = ['#D77A61', '#9CAF88', '#E8B4BC', '#5A3E36'];

export default function StatistiquesTab() {
  const [tauxRejet, setTauxRejet] = useState([]);
  const [defautsParType, setDefautsParType] = useState([]);

  useEffect(() => {
    apiClient.get('/statistiques/taux-rejet').then((res) => setTauxRejet(res.data.data));
    apiClient.get('/statistiques/defauts-par-type').then((res) => setDefautsParType(res.data.data));
  }, []);

  const defautsAvecDonnees = defautsParType.filter((d) => d.total_quantite > 0);

  return (
    <div className="grid grid-cols-2 gap-6">
      <div className="bg-white rounded-xl shadow-sm p-5">
        <h3 className="font-semibold mb-4">Taux de rejet par bijou (%)</h3>
        {tauxRejet.length === 0 ? (
          <p className="text-brun-doux/50 text-sm">Aucune donnée pour le moment.</p>
        ) : (
          <ResponsiveContainer width="100%" height={280}>
            <BarChart data={tauxRejet}>
              <CartesianGrid strokeDasharray="3 3" stroke="#5A3E3620" />
              <XAxis dataKey="nom" tick={{ fontSize: 12, fill: '#5A3E36' }} />
              <YAxis tick={{ fontSize: 12, fill: '#5A3E36' }} unit="%" />
              <Tooltip formatter={(value) => [`${value}%`, 'Taux de rejet']} />
              <Bar dataKey="taux_rejet_pct" fill="#D77A61" radius={[6, 6, 0, 0]} />
            </BarChart>
          </ResponsiveContainer>
        )}
      </div>

      <div className="bg-white rounded-xl shadow-sm p-5">
        <h3 className="font-semibold mb-4">Répartition des défauts</h3>
        {defautsAvecDonnees.length === 0 ? (
          <p className="text-brun-doux/50 text-sm">Aucun défaut enregistré pour le moment.</p>
        ) : (
          <ResponsiveContainer width="100%" height={280}>
            <PieChart>
              <Pie
                data={defautsAvecDonnees}
                dataKey="total_quantite"
                nameKey="libelle"
                cx="50%"
                cy="50%"
                outerRadius={90}
                label={({ libelle, percent }) => `${libelle} (${(percent * 100).toFixed(0)}%)`}
              >
                {defautsAvecDonnees.map((_, index) => (
                  <Cell key={index} fill={COLORS[index % COLORS.length]} />
                ))}
              </Pie>
              <Tooltip />
            </PieChart>
          </ResponsiveContainer>
        )}
      </div>
    </div>
  );
}