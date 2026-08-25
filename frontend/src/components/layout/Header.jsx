export default function Header({ title }) {
  return (
    <header className="flex items-center justify-between px-8 py-5 bg-white border-b border-black/5">
      <h1 className="text-xl font-semibold text-brun-doux">{title}</h1>
      <div className="flex items-center gap-4">
        <input
          type="text"
          placeholder="Rechercher..."
          className="px-3 py-2 rounded-lg border border-black/10 text-sm outline-none focus:border-terracotta"
        />
        <div className="w-9 h-9 rounded-full bg-terracotta text-white flex items-center justify-center text-sm font-medium">
          MD
        </div>
      </div>
    </header>
  );
}