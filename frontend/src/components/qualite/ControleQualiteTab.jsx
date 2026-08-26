import { useEffect, useState } from 'react';
import apiClient from '../../api/client';

export default function ControleQualiteTab() {
  const [ofsTermines, setOfsTermines] = useState([]);
  const [typesDefaut, setTypesDefaut] = useState([]);
  const [controles, setControles] = useState([]);

  const [selectedOf, setSelectedOf] = useState('');
  const [quantiteControlee, setQuantiteControlee] = useState('');
  const [quantiteValidee, setQuantiteValidee] = useState('');
  const [quantiteRejetee, setQuantiteRejetee] = useState('0');
  const [defautsCoches, setDefautsCoches] = useState({}); // { id_type_defaut: quantite }
  const [commentaire, setCommentaire] = useState('');
  const [error, setError] = useState(null);
  const [success, setSuccess] = useState(false);

  const loadData = () => {
    apiClient.get('/ordres-fabrication').then((res) => {
      setOfsTermines(res.data.data.filter((of) => of.code_statut === 'TERMINE'));
    });
    apiClient.get('/controles-qualite').then((res) => setControles(res.data.data));
  };

  useEffect(() => {
    loadData();
    apiClient.get('/types-defaut').then((res) => setTypesDefaut(res.data.data));
  }, []);

  const toggleDefaut = (id) => {
    setDefautsCoches((prev) => {
      const copy = { ...prev };
      if (id in copy) {
        delete copy[id];
      } else {
        copy[id] = 1;
      }
      return copy;
    });
  };

  const updateDefautQuantite = (id, quantite) => {
    setDefautsCoches((prev) => ({ ...prev, [id]: quantite }));
  };

  const resetForm = () => {
    setSelectedOf('');
    setQuantiteControlee('');
    setQuantiteValidee('');
    setQuantiteRejetee('0');
    setDefautsCoches({});
    setCommentaire('');
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError(null);
    setSuccess(false);

    const defauts = Object.entries(defautsCoches).map(([id_type_defaut, quantite]) => ({
      id_type_defaut: Number(id_type_defaut),
      quantite: Number(quantite),
    }));

    try {
      await apiClient.post('/controles-qualite', {
        id_of: selectedOf,
        quantite_controlee: quantiteControlee,
        quantite_validee: quantiteValidee,
        quantite_rejetee: quantiteRejetee,
        commentaire: commentaire || null,
        defauts,
      });
      setSuccess(true);
      resetForm();
      loadData();
    } catch (err) {
      setError(err.message);
    }
  };

  return (
    <div className="grid grid-cols-3 gap-6">
      {/* Formulaire de contrôle */}
      <div className="col-span-2 bg-white rounded-xl shadow-sm p-5">
        <h3 className="font-semibold mb-4">Nouveau contrôle qualité</h3>

        <form onSubmit={handleSubmit} className="space-y-4">
          <div>
            <label className="text-sm font-medium">Ordre de fabrication (terminé)</label>
            <select
              className="w-full border rounded-lg px-3 py-2 mt-1"
              value={selectedOf}
              onChange={(e) => setSelectedOf(e.target.value)}
            >
              <option value="">—</option>
              {ofsTermines.map((of) => (
                <option key={of.id_of} value={of.id_of}>
                  {of.reference} · {of.bijou} (réalisé : {of.quantite_realisee})
                </option>
              ))}
            </select>
          </div>

          <div className="grid grid-cols-3 gap-3">
            <div>
              <label className="text-sm font-medium">Quantité contrôlée</label>
              <input
                type="number"
                className="w-full border rounded-lg px-3 py-2 mt-1"
                value={quantiteControlee}
                onChange={(e) => setQuantiteControlee(e.target.value)}
              />
            </div>
            <div>
              <label className="text-sm font-medium">Validée</label>
              <input
                type="number"
                className="w-full border rounded-lg px-3 py-2 mt-1"
                value={quantiteValidee}
                onChange={(e) => setQuantiteValidee(e.target.value)}
              />
            </div>
            <div>
              <label className="text-sm font-medium">Rejetée</label>
              <input
                type="number"
                className="w-full border rounded-lg px-3 py-2 mt-1"
                value={quantiteRejetee}
                onChange={(e) => setQuantiteRejetee(e.target.value)}
              />
            </div>
          </div>

          <div>
            <label className="text-sm font-medium block mb-2">Défauts constatés</label>
            <div className="space-y-2">
              {typesDefaut.map((td) => {
                const checked = td.id_type_defaut in defautsCoches;
                return (
                  <div key={td.id_type_defaut} className="flex items-center gap-3">
                    <label className="flex items-center gap-2 flex-1 text-sm">
                      <input
                        type="checkbox"
                        checked={checked}
                        onChange={() => toggleDefaut(td.id_type_defaut)}
                        className="accent-terracotta"
                      />
                      {td.libelle}
                    </label>
                    {checked && (
                      <input
                        type="number"
                        min="1"
                        className="w-20 border rounded-lg px-2 py-1 text-sm"
                        value={defautsCoches[td.id_type_defaut]}
                        onChange={(e) => updateDefautQuantite(td.id_type_defaut, e.target.value)}
                      />
                    )}
                  </div>
                );
              })}
            </div>
          </div>

          <div>
            <label className="text-sm font-medium">Commentaire (optionnel)</label>
            <textarea
              className="w-full border rounded-lg px-3 py-2 mt-1"
              value={commentaire}
              onChange={(e) => setCommentaire(e.target.value)}
            />
          </div>

          {error && <p className="text-red-500 text-sm">{error}</p>}
          {success && <p className="text-vert-sauge text-sm">✅ Contrôle enregistré avec succès.</p>}

          <div className="flex gap-3">
            <button
              type="submit"
              className="bg-vert-sauge text-white px-5 py-2 rounded-lg text-sm font-medium"
            >
              Valider le contrôle
            </button>
          </div>
        </form>
      </div>

      {/* Historique des contrôles */}
      <div className="bg-white rounded-xl shadow-sm p-5 h-fit">
        <h3 className="font-semibold mb-3">Historique</h3>
        <div className="space-y-3 max-h-96 overflow-y-auto">
          {controles.length === 0 && (
            <p className="text-brun-doux/50 text-sm">Aucun contrôle enregistré.</p>
          )}
          {controles.map((c) => (
            <div key={c.id_controle} className="border-b border-black/5 pb-2 text-sm">
              <div className="flex justify-between">
                <span className="font-medium">{c.of}</span>
                <span className="text-brun-doux/50 text-xs">
                  {new Date(c.date_controle).toLocaleDateString('fr-FR')}
                </span>
              </div>
              <div className="text-xs text-brun-doux/60">
                {c.quantite_validee} validés / {c.quantite_rejetee} rejetés
              </div>
              {c.defauts.length > 0 && (
                <div className="text-xs text-rose-poudre mt-1">
                  {c.defauts.map((d) => d.type_defaut).join(', ')}
                </div>
              )}
            </div>
          ))}
        </div>
      </div>
    </div>
  );
}