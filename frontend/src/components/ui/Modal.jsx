export default function Modal({ open, onClose, title, children }) {
  if (!open) return null;

  return (
    <div className="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
      <div className="bg-white rounded-xl shadow-xl w-full max-w-md p-6">
        <div className="flex items-center justify-between mb-4">
          <h2 className="text-lg font-semibold text-brun-doux">{title}</h2>
          <button onClick={onClose} className="text-brun-doux/50 hover:text-brun-doux">✕</button>
        </div>
        {children}
      </div>
    </div>
  );
}