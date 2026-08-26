import { useEffect, useState } from 'react';
import apiClient from '../api/client';
import Modal from '../components/ui/Modal';

export default function Production() {
  const [ofs, setOfs] = useState([]);
  const [bijoux, setBijoux] = useState([]);
  const [statuts, setStatuts] = useState([]);

  const [modalOpen, setModalOpen] = useState(false);
  const [form, setForm] = useState({ id_bijou: '', quantite_prevue: '', date_debut: '', date_fin_prevue: '' });
  const [error, setError] = useState(null);

  const [clotureModal, setClotureModal] = useState(null); // OF en cours de clôture
  const [quantiteRealisee, setQuantiteRealisee] = useState('');
  const [quantiteRejetee, setQuantiteRejetee] = useState('0');

  const loadOfs = () => {
    apiClient.get('/ordres-fabrication').then((res) => setOfs(res.data.data));
  };

  useEffect(() => {
    loadOfs();
    apiClient.get('/bijoux').then((res) => setBijoux(res.data.data));
    apiClient.get('/statuts-production').then((res) => setStatuts(res.data.data));
  }, []);

  const handleCreate = async (e) => {
    e.preventDefault();
    setError(null);
    try {
      await apiClient.post('/ordres-fabrication', form);
      setModalOpen(false);
      setForm({ id_bijou: '', quantite_prevue: '', date_debut: '', date_fin_prevue: '' });
      loadOfs();
    } catch (err) {
      setError(err.message);
    }
  };

  const statutSuivant = (codeActuel) => {
    const ordreCodes = ['EN_ATTENTE', 'EN_COURS', 'TERMINE'];
    const index = ordreCodes.indexOf(codeActuel);
    return index >= 0 && index < 2 ? ordreCodes[index + 1] : null;
  };

  const handleAvancerStatut = async (of) => {
    const codeCible = statutSuivant(of.code_statut);
    if (!codeCible) return;

    if (codeCible === 'TERMINE') {
      // Ouvre un modal pour saisir la quantité réalisée avant de clôturer
      setClotureModal(of);
      setQuantiteRealisee(String(of.quantite_prevue));
      return;
    }

    const statutCible = statuts.find((s) => s.code === codeCible);
    await apiClient.patch(`/ordres-fabrication/${of.id_of}/statut`, {
      id_statut_prod: statutCible.id_statut_prod,
    });
    loadOfs();
  };

  const confirmerCloture = async () => {
    const statutTermine = statuts.find((s) => s.code === 'TERMINE');
    setError(null);
    try {
      await apiClient.patch(`/ordres-fabrication/${clotureModal.id_of}/statut`, {
        id_statut_prod: statutTermine.id_statut_prod,
        quantite_realisee: quantiteRealisee,
        quantite_rejetee: quantiteRejetee,
      });
      setClotureModal(null);
      loadOfs();
    } catch (err) {
      setError(err.message);
    }
  };

  const badgeColor = (code) => {
    if (code === 'TERMINE') return 'bg-vert-sauge/20 text-vert-sauge';
    if (code === 'EN_COURS') return 'bg-terracotta/20 text-terracotta';
    return 'bg-rose-poudre/30 text-brun-doux';
  };

  return (
    <div>
      <div className="flex justify-between items-center mb-4">
        <h2 className="text-lg font-semibold">Ordres de fabrication</h2>
        <button
          onClick={() => setModalOpen(true)}
          className="bg-terracotta text-white px-4 py-2 rounded-lg text-sm font-medium"
        >
          + Nouvel ordre
        </button>
      </div>

      <div className="bg-white rounded-xl shadow-sm overflow-hidden">
        <table className="w-full text-sm">
          <thead className="bg-creme text-left">
            <tr>
              <th className="p-3">Référence</th>
              <th className="p-3">Bijou</th>
              <th className="p-3">Prévue</th>
              <th className="p-3">Réalisée</th>
              <th className="p-3">Statut</th>
              <th className="p-3"></th>
            </tr>
          </thead>
          <tbody>
            {ofs.map((of) => (
              <tr key={of.id_of} className="border-t border-black/5">
                <td className="p-3">{of.reference}</td>
                <td className="p-3">{of.bijou}</td>
                <td className="p-3">{of.quantite_prevue}</td>
                <td className="p-3">{of.quantite_realisee}</td>
                <td className="p-3">
                  <span className={`px-2 py-1 rounded-full text-xs font-medium ${badgeColor(of.code_statut)}`}>
                    {of.statut}
                  </span>
                </td>
                <td className="p-3 text-right">
                  {of.code_statut !== 'TERMINE' && (
                    <button
                      onClick={() => handleAvancerStatut(of)}
                      className="text-terracotta hover:underline text-xs font-medium"
                    >
                      {of.code_statut === 'EN_ATTENTE' ? 'Démarrer' : 'Terminer'}
                    </button>
                  )}
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      {/* Modal création OF */}
      <Modal open={modalOpen} onClose={() => setModalOpen(false)} title="Nouvel ordre de fabrication">
        <form onSubmit={handleCreate} className="space-y-3">
          <div>
            <label className="text-sm font-medium">Bijou</label>
            <select
              className="w-full border rounded-lg px-3 py-2 mt-1"
              value={form.id_bijou}
              onChange={(e) => setForm({ ...form, id_bijou: e.target.value })}
            >
              <option value="">—</option>
              {bijoux.map((b) => <option key={b.id_bijou} value={b.id_bijou}>{b.nom}</option>)}
            </select>
          </div>
          <div>
            <label className="text-sm font-medium">Quantité prévue</label>
            <input
              type="number"
              className="w-full border rounded-lg px-3 py-2 mt-1"
              value={form.quantite_prevue}
              onChange={(e) => setForm({ ...form, quantite_prevue: e.target.value })}
            />
          </div>
          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className="text-sm font-medium">Date de début</label>
              <input
                type="date"
                className="w-full border rounded-lg px-3 py-2 mt-1"
                value={form.date_debut}
                onChange={(e) => setForm({ ...form, date_debut: e.target.value })}
              />
            </div>
            <div>
              <label className="text-sm font-medium">Fin prévue</label>
              <input
                type="date"
                className="w-full border rounded-lg px-3 py-2 mt-1"
                value={form.date_fin_prevue}
                onChange={(e) => setForm({ ...form, date_fin_prevue: e.target.value })}
              />
            </div>
          </div>
          {error && <p className="text-red-500 text-sm">{error}</p>}
          <button type="submit" className="w-full bg-terracotta text-white py-2 rounded-lg font-medium mt-2">
            Lancer l'ordre de fabrication
          </button>
        </form>
      </Modal>

      {/* Modal clôture OF */}
      <Modal open={!!clotureModal} onClose={() => setClotureModal(null)} title="Clôturer l'ordre de fabrication">
        {clotureModal && (
          <div className="space-y-3">
            <p className="text-sm text-brun-doux/70">
              {clotureModal.reference} · {clotureModal.bijou} (prévu : {clotureModal.quantite_prevue})
            </p>
            <div>
              <label className="text-sm font-medium">Quantité réalisée</label>
              <input
                type="number"
                className="w-full border rounded-lg px-3 py-2 mt-1"
                value={quantiteRealisee}
                onChange={(e) => setQuantiteRealisee(e.target.value)}
              />
            </div>
            <div>
              <label className="text-sm font-medium">Quantité rejetée</label>
              <input
                type="number"
                className="w-full border rounded-lg px-3 py-2 mt-1"
                value={quantiteRejetee}
                onChange={(e) => setQuantiteRejetee(e.target.value)}
              />
            </div>
            {error && <p className="text-red-500 text-sm">{error}</p>}
            <button
              onClick={confirmerCloture}
              className="w-full bg-vert-sauge text-white py-2 rounded-lg font-medium"
            >
              Confirmer la clôture
            </button>
          </div>
        )}
      </Modal>
    </div>
  );
}