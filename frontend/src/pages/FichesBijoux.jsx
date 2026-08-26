import { useEffect, useState } from 'react';
import apiClient from '../api/client';
import Modal from '../components/ui/Modal';

const EMPTY_BIJOU = {
  id_type_bijou: '', reference: '', nom: '', taille: 'M', complexite: 'Moyenne', temps_fabrication_minutes: '',
};

export default function FichesBijoux() {
  const [bijoux, setBijoux] = useState([]);
  const [typesBijou, setTypesBijou] = useState([]);
  const [matieres, setMatieres] = useState([]);
  const [selected, setSelected] = useState(null);
  const [coutRevient, setCoutRevient] = useState(null);

  const [modalOpen, setModalOpen] = useState(false);
  const [form, setForm] = useState(EMPTY_BIJOU);
  const [errors, setErrors] = useState({});

  const [ligneForm, setLigneForm] = useState({ id_matiere: '', quantite_necessaire: '' });

  const loadBijoux = () => {
    apiClient.get('/bijoux').then((res) => setBijoux(res.data.data));
  };

  useEffect(() => {
    loadBijoux();
    apiClient.get('/types-bijou').then((res) => setTypesBijou(res.data.data));
    apiClient.get('/matieres').then((res) => setMatieres(res.data.data));
  }, []);

  const selectBijou = async (bijou) => {
    const res = await apiClient.get(`/bijoux/${bijou.id_bijou}`);
    setSelected(res.data.data);
    setCoutRevient(null);
  };

  const handleCreateBijou = async (e) => {
    e.preventDefault();
    try {
      await apiClient.post('/bijoux', form);
      setModalOpen(false);
      setForm(EMPTY_BIJOU);
      setErrors({});
      loadBijoux();
    } catch (err) {
      if (err.validationErrors) setErrors(err.validationErrors);
    }
  };

  const handleAddLigne = async (e) => {
    e.preventDefault();
    await apiClient.post(`/bijoux/${selected.id_bijou}/compositions`, ligneForm);
    setLigneForm({ id_matiere: '', quantite_necessaire: '' });
    selectBijou(selected);
  };

  const handleDeleteLigne = async (idComposition) => {
    await apiClient.delete(`/bijoux/${selected.id_bijou}/compositions/${idComposition}`);
    selectBijou(selected);
  };

  const handleCalculerCout = async () => {
    const res = await apiClient.get(`/bijoux/${selected.id_bijou}/cout-revient`);
    setCoutRevient(res.data.cout_revient);
  };

  return (
    <div className="grid grid-cols-3 gap-6">
      {/* Liste des bijoux */}
      <div className="bg-white rounded-xl shadow-sm p-4">
        <div className="flex justify-between items-center mb-3">
          <h3 className="font-semibold">Bijoux</h3>
          <button
            onClick={() => setModalOpen(true)}
            className="text-terracotta text-sm font-medium hover:underline"
          >
            + Nouveau
          </button>
        </div>
        <div className="space-y-1">
          {bijoux.map((b) => (
            <button
              key={b.id_bijou}
              onClick={() => selectBijou(b)}
              className={`w-full text-left px-3 py-2 rounded-lg text-sm ${
                selected?.id_bijou === b.id_bijou ? 'bg-terracotta/10 text-terracotta' : 'hover:bg-creme'
              }`}
            >
              <div className="font-medium">{b.nom}</div>
              <div className="text-xs text-brun-doux/60">{b.reference}</div>
            </button>
          ))}
        </div>
      </div>

      {/* Détail / nomenclature */}
      <div className="col-span-2 bg-white rounded-xl shadow-sm p-5">
        {!selected ? (
          <p className="text-brun-doux/50 text-sm">Sélectionnez un bijou pour voir sa fiche.</p>
        ) : (
          <div>
            <div className="flex justify-between items-start mb-4">
              <div>
                <h3 className="text-lg font-semibold">{selected.nom}</h3>
                <p className="text-sm text-brun-doux/60">
                  {selected.reference} · {selected.type_bijou} · {selected.taille} · {selected.complexite}
                </p>
                <p className="text-sm text-brun-doux/60">
                  Temps de fabrication : {selected.temps_fabrication_minutes} min
                </p>
              </div>
              <button
                onClick={handleCalculerCout}
                className="bg-terracotta text-white px-4 py-2 rounded-lg text-sm font-medium"
              >
                Calculer le coût de revient
              </button>
            </div>

            {coutRevient !== null && (
              <div className="bg-rose-poudre/20 rounded-lg p-3 mb-4 text-sm">
                💰 Coût de revient : <strong>{coutRevient.toFixed(2)} €</strong>
              </div>
            )}

            <h4 className="font-medium mb-2">Nomenclature (matières nécessaires)</h4>
            <table className="w-full text-sm mb-4">
              <thead className="bg-creme text-left">
                <tr>
                  <th className="p-2">Matière</th>
                  <th className="p-2">Quantité</th>
                  <th className="p-2"></th>
                </tr>
              </thead>
              <tbody>
                {selected.compositions.map((c) => (
                  <tr key={c.id_composition} className="border-t border-black/5">
                    <td className="p-2">{c.matiere}</td>
                    <td className="p-2">{c.quantite_necessaire}</td>
                    <td className="p-2 text-right">
                      <button
                        onClick={() => handleDeleteLigne(c.id_composition)}
                        className="text-rose-poudre hover:underline text-xs"
                      >
                        Retirer
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>

            <form onSubmit={handleAddLigne} className="flex gap-2 items-end">
              <div className="flex-1">
                <label className="text-xs font-medium">Matière</label>
                <select
                  className="w-full border rounded-lg px-3 py-2 mt-1"
                  value={ligneForm.id_matiere}
                  onChange={(e) => setLigneForm({ ...ligneForm, id_matiere: e.target.value })}
                >
                  <option value="">—</option>
                  {matieres.map((m) => <option key={m.id_matiere} value={m.id_matiere}>{m.nom}</option>)}
                </select>
              </div>
              <div className="w-32">
                <label className="text-xs font-medium">Quantité</label>
                <input
                  type="number" step="0.01"
                  className="w-full border rounded-lg px-3 py-2 mt-1"
                  value={ligneForm.quantite_necessaire}
                  onChange={(e) => setLigneForm({ ...ligneForm, quantite_necessaire: e.target.value })}
                />
              </div>
              <button type="submit" className="bg-vert-sauge text-white px-4 py-2 rounded-lg text-sm font-medium">
                Ajouter
              </button>
            </form>
          </div>
        )}
      </div>

      <Modal open={modalOpen} onClose={() => setModalOpen(false)} title="Nouveau bijou">
        <form onSubmit={handleCreateBijou} className="space-y-3">
          <div>
            <label className="text-sm font-medium">Référence</label>
            <input
              className="w-full border rounded-lg px-3 py-2 mt-1"
              value={form.reference}
              onChange={(e) => setForm({ ...form, reference: e.target.value })}
            />
            {errors.reference && <p className="text-red-500 text-xs mt-1">{errors.reference[0]}</p>}
          </div>
          <div>
            <label className="text-sm font-medium">Nom</label>
            <input
              className="w-full border rounded-lg px-3 py-2 mt-1"
              value={form.nom}
              onChange={(e) => setForm({ ...form, nom: e.target.value })}
            />
          </div>
          <div>
            <label className="text-sm font-medium">Type</label>
            <select
              className="w-full border rounded-lg px-3 py-2 mt-1"
              value={form.id_type_bijou}
              onChange={(e) => setForm({ ...form, id_type_bijou: e.target.value })}
            >
              <option value="">—</option>
              {typesBijou.map((t) => <option key={t.id_type_bijou} value={t.id_type_bijou}>{t.nom}</option>)}
            </select>
          </div>
          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className="text-sm font-medium">Taille</label>
              <select
                className="w-full border rounded-lg px-3 py-2 mt-1"
                value={form.taille}
                onChange={(e) => setForm({ ...form, taille: e.target.value })}
              >
                {['XS', 'S', 'M', 'L', 'XL'].map((t) => <option key={t} value={t}>{t}</option>)}
              </select>
            </div>
            <div>
              <label className="text-sm font-medium">Complexité</label>
              <select
                className="w-full border rounded-lg px-3 py-2 mt-1"
                value={form.complexite}
                onChange={(e) => setForm({ ...form, complexite: e.target.value })}
              >
                {['Simple', 'Moyenne', 'Complexe'].map((c) => <option key={c} value={c}>{c}</option>)}
              </select>
            </div>
          </div>
          <div>
            <label className="text-sm font-medium">Temps de fabrication (minutes)</label>
            <input
              type="number"
              className="w-full border rounded-lg px-3 py-2 mt-1"
              value={form.temps_fabrication_minutes}
              onChange={(e) => setForm({ ...form, temps_fabrication_minutes: e.target.value })}
            />
          </div>
          <button type="submit" className="w-full bg-terracotta text-white py-2 rounded-lg font-medium mt-2">
            Créer le bijou
          </button>
        </form>
      </Modal>
    </div>
  );
}