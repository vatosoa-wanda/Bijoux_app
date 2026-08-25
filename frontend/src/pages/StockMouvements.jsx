import { useEffect, useState } from 'react';
import apiClient from '../api/client';

export default function StockMouvements() {
  const [mouvements, setMouvements] = useState([]);
  const [matieres, setMatieres] = useState([]);
  const [types, setTypes] = useState([]);
  const [form, setForm] = useState({ id_matiere: '', id_type_mvt: '', quantite: '', commentaire: '' });
  const [error, setError] = useState(null);

  const loadMouvements = () => {
    apiClient.get('/mouvements').then((res) => setMouvements(res.data.data));
  };

  useEffect(() => {
    loadMouvements();
    apiClient.get('/matieres').then((res) => setMatieres(res.data.data));
    // Types de mouvement : réutilise la table via un futur endpoint dédié si besoin.
    // Pour l'instant on peut aussi les coder en dur si pas d'endpoint créé.
  }, []);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError(null);
    try {
      await apiClient.post('/mouvements', form);
      setForm({ id_matiere: '', id_type_mvt: '', quantite: '', commentaire: '' });
      loadMouvements();
    } catch (err) {
      setError(err.message);
    }
  };

  return (
    <div className="grid grid-cols-3 gap-6">
      <div className="col-span-2 bg-white rounded-xl shadow-sm overflow-hidden">
        <table className="w-full text-sm">
          <thead className="bg-creme text-left">
            <tr>
              <th className="p-3">Date</th>
              <th className="p-3">Matière</th>
              <th className="p-3">Type</th>
              <th className="p-3">Sens</th>
              <th className="p-3">Quantité</th>
            </tr>
          </thead>
          <tbody>
            {mouvements.map((m) => (
              <tr key={m.id_mouvement} className="border-t border-black/5">
                <td className="p-3">{new Date(m.date_mouvement).toLocaleDateString('fr-FR')}</td>
                <td className="p-3">{m.matiere}</td>
                <td className="p-3">{m.type_mouvement}</td>
                <td className="p-3">
                  <span className={m.sens === 'ENTREE' ? 'text-vert-sauge' : 'text-terracotta'}>
                    {m.sens === 'ENTREE' ? '+' : '-'}{m.quantite}
                  </span>
                </td>
                <td className="p-3">{m.quantite}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      <div className="bg-white rounded-xl shadow-sm p-5 h-fit">
        <h3 className="font-semibold mb-3">Nouveau mouvement</h3>
        <form onSubmit={handleSubmit} className="space-y-3">
          <select
            className="w-full border rounded-lg px-3 py-2"
            value={form.id_matiere}
            onChange={(e) => setForm({ ...form, id_matiere: e.target.value })}
          >
            <option value="">Matière</option>
            {matieres.map((m) => <option key={m.id_matiere} value={m.id_matiere}>{m.nom}</option>)}
          </select>

          <select
            className="w-full border rounded-lg px-3 py-2"
            value={form.id_type_mvt}
            onChange={(e) => setForm({ ...form, id_type_mvt: e.target.value })}
          >
            <option value="">Type de mouvement</option>
            {/* à remplacer par un fetch réel une fois l'endpoint type-mouvement créé */}
          </select>

          <input
            type="number" step="0.01" placeholder="Quantité"
            className="w-full border rounded-lg px-3 py-2"
            value={form.quantite}
            onChange={(e) => setForm({ ...form, quantite: e.target.value })}
          />

          <textarea
            placeholder="Commentaire (optionnel)"
            className="w-full border rounded-lg px-3 py-2"
            value={form.commentaire}
            onChange={(e) => setForm({ ...form, commentaire: e.target.value })}
          />

          {error && <p className="text-red-500 text-sm">{error}</p>}

          <button type="submit" className="w-full bg-terracotta text-white py-2 rounded-lg font-medium">
            Enregistrer le mouvement
          </button>
        </form>
      </div>
    </div>
  );
}