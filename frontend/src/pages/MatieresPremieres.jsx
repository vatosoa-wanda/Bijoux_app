import { useEffect, useState } from 'react';
import apiClient from '../api/client';
import Modal from '../components/ui/Modal';
import Badge from '../components/ui/Badge';

const EMPTY_FORM = {
  id_categorie: '', id_unite: '', nom: '', couleur: '',
  quantite_stock: '', seuil_alerte: '', prix_unitaire: '',
};

export default function MatieresPremieres() {
  const [matieres, setMatieres] = useState([]);
  const [categories, setCategories] = useState([]);
  const [unites, setUnites] = useState([]);
  const [modalOpen, setModalOpen] = useState(false);
  const [editing, setEditing] = useState(null);
  const [form, setForm] = useState(EMPTY_FORM);
  const [errors, setErrors] = useState({});

  const loadData = () => {
    apiClient.get('/matieres').then((res) => setMatieres(res.data.data));
  };

  useEffect(() => {
    loadData();
    apiClient.get('/categories-matiere').then((res) => setCategories(res.data.data));
    apiClient.get('/unites-mesure').then((res) => setUnites(res.data.data));
  }, []);

  const openCreate = () => {
    setEditing(null);
    setForm(EMPTY_FORM);
    setErrors({});
    setModalOpen(true);
  };

  const openEdit = (matiere) => {
    setEditing(matiere);
    setForm({
      id_categorie: matiere.id_categorie ?? '',
      id_unite: matiere.id_unite ?? '',
      nom: matiere.nom,
      couleur: matiere.couleur ?? '',
      seuil_alerte: matiere.seuil_alerte,
      prix_unitaire: matiere.prix_unitaire,
    });
    setErrors({});
    setModalOpen(true);
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    try {
      if (editing) {
        await apiClient.put(`/matieres/${editing.id_matiere}`, form);
      } else {
        await apiClient.post('/matieres', form);
      }
      setModalOpen(false);
      loadData();
    } catch (err) {
      if (err.validationErrors) setErrors(err.validationErrors);
    }
  };

  const handleDeactivate = async (id) => {
    if (!confirm('Désactiver cette matière ?')) return;
    await apiClient.delete(`/matieres/${id}`);
    loadData();
  };

  return (
    <div>
      <div className="flex justify-between items-center mb-4">
        <h2 className="text-lg font-semibold">Matières premières</h2>
        <button
          onClick={openCreate}
          className="bg-terracotta text-white px-4 py-2 rounded-lg text-sm font-medium hover:opacity-90"
        >
          + Ajouter une matière
        </button>
      </div>

      <div className="bg-white rounded-xl shadow-sm overflow-hidden">
        <table className="w-full text-sm">
          <thead className="bg-creme text-left">
            <tr>
              <th className="p-3">Nom</th>
              <th className="p-3">Catégorie</th>
              <th className="p-3">Stock</th>
              <th className="p-3">Seuil</th>
              <th className="p-3">Prix unitaire</th>
              <th className="p-3">Statut</th>
              <th className="p-3"></th>
            </tr>
          </thead>
          <tbody>
            {matieres.map((m) => (
              <tr key={m.id_matiere} className="border-t border-black/5">
                <td className="p-3">{m.nom}</td>
                <td className="p-3">{m.categorie}</td>
                <td className="p-3">{m.quantite_stock} {m.unite}</td>
                <td className="p-3">{m.seuil_alerte}</td>
                <td className="p-3">{m.prix_unitaire.toFixed(2)} €</td>
                <td className="p-3"><Badge status={m.statut_stock} /></td>
                <td className="p-3 text-right space-x-2">
                  <button onClick={() => openEdit(m)} className="text-terracotta hover:underline">Modifier</button>
                  <button onClick={() => handleDeactivate(m.id_matiere)} className="text-rose-poudre hover:underline">Désactiver</button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      <Modal open={modalOpen} onClose={() => setModalOpen(false)} title={editing ? 'Modifier la matière' : 'Nouvelle matière'}>
        <form onSubmit={handleSubmit} className="space-y-3">
          <div>
            <label className="text-sm font-medium">Nom</label>
            <input
              className="w-full border rounded-lg px-3 py-2 mt-1"
              value={form.nom}
              onChange={(e) => setForm({ ...form, nom: e.target.value })}
            />
            {errors.nom && <p className="text-red-500 text-xs mt-1">{errors.nom[0]}</p>}
          </div>

          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className="text-sm font-medium">Catégorie</label>
              <select
                className="w-full border rounded-lg px-3 py-2 mt-1"
                value={form.id_categorie}
                onChange={(e) => setForm({ ...form, id_categorie: e.target.value })}
              >
                <option value="">—</option>
                {categories.map((c) => <option key={c.id_categorie} value={c.id_categorie}>{c.nom}</option>)}
              </select>
            </div>
            <div>
              <label className="text-sm font-medium">Unité</label>
              <select
                className="w-full border rounded-lg px-3 py-2 mt-1"
                value={form.id_unite}
                onChange={(e) => setForm({ ...form, id_unite: e.target.value })}
              >
                <option value="">—</option>
                {unites.map((u) => <option key={u.id_unite} value={u.id_unite}>{u.libelle}</option>)}
              </select>
            </div>
          </div>

          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className="text-sm font-medium">Seuil d'alerte</label>
              <input
                type="number" step="0.01"
                className="w-full border rounded-lg px-3 py-2 mt-1"
                value={form.seuil_alerte}
                onChange={(e) => setForm({ ...form, seuil_alerte: e.target.value })}
              />
            </div>
            <div>
              <label className="text-sm font-medium">Prix unitaire (€)</label>
              <input
                type="number" step="0.0001"
                className="w-full border rounded-lg px-3 py-2 mt-1"
                value={form.prix_unitaire}
                onChange={(e) => setForm({ ...form, prix_unitaire: e.target.value })}
              />
            </div>
          </div>

          {!editing && (
            <div>
              <label className="text-sm font-medium">Stock initial (optionnel)</label>
              <input
                type="number" step="0.01"
                className="w-full border rounded-lg px-3 py-2 mt-1"
                value={form.quantite_stock}
                onChange={(e) => setForm({ ...form, quantite_stock: e.target.value })}
              />
            </div>
          )}

          <button type="submit" className="w-full bg-terracotta text-white py-2 rounded-lg font-medium mt-2">
            Enregistrer
          </button>
        </form>
      </Modal>
    </div>
  );
}