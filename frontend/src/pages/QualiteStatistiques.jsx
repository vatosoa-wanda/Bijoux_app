import { useState } from 'react';
import ControleQualiteTab from '../components/qualite/ControleQualiteTab';
import StatistiquesTab from '../components/qualite/StatistiquesTab';

export default function QualiteStatistiques() {
  const [tab, setTab] = useState('qualite');

  return (
    <div>
      <div className="flex gap-1 mb-6 border-b border-black/10">
        <button
          onClick={() => setTab('qualite')}
          className={`px-4 py-2 text-sm font-medium border-b-2 -mb-px transition-colors ${
            tab === 'qualite' ? 'border-terracotta text-terracotta' : 'border-transparent text-brun-doux/50'
          }`}
        >
          🔍 Contrôle qualité
        </button>
        <button
          onClick={() => setTab('stats')}
          className={`px-4 py-2 text-sm font-medium border-b-2 -mb-px transition-colors ${
            tab === 'stats' ? 'border-terracotta text-terracotta' : 'border-transparent text-brun-doux/50'
          }`}
        >
          📈 Statistiques
        </button>
      </div>

      {tab === 'qualite' ? <ControleQualiteTab /> : <StatistiquesTab />}
    </div>
  );
}