export default function Badge({ status }) {
  const isAlert = status === 'ALERTE';
  return (
    <span
      className={`px-2 py-1 rounded-full text-xs font-medium ${
        isAlert ? 'bg-rose-poudre text-brun-doux' : 'bg-vert-sauge/20 text-vert-sauge'
      }`}
    >
      {isAlert ? '⚠ Alerte' : '✅ OK'}
    </span>
  );
}