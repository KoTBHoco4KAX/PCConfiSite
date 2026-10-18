import { Check, AlertCircle, Plus } from 'lucide-react';
import { formatPrice, buildSpecRows, toArray } from '../utils/format';

export default function SlotCard({ slot, products, onEmptyClick }) {
  const { key, label, icon: Icon } = slot;
  const items = toArray(products);
  const hasItems = items.length > 0;

  return (
    <div
      className={`group relative overflow-hidden rounded-2xl glass p-4 ${
        hasItems ? 'glass-hover' : 'border-dashed border-slate-800/80'
      }`}
    >
      {/* Левый цветной акцент */}
      <div
        className={`absolute left-0 top-0 h-full w-[3px] transition-all duration-300 ${
          hasItems
            ? 'bg-gradient-to-b from-indigo-500 to-violet-500'
            : 'bg-slate-800'
        }`}
      />

      <div className="flex items-start gap-3">
        {/* Иконка слота */}
        <div
          className={`flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border transition-colors ${
            hasItems
              ? 'border-indigo-500/30 bg-indigo-500/10 text-indigo-300'
              : 'border-slate-800 bg-slate-900/70 text-slate-500'
          }`}
        >
          <Icon size={20} strokeWidth={2} />
        </div>

        <div className="min-w-0 flex-1">
          <div className="mb-1 flex items-center justify-between gap-2">
            <span className="text-[11px] font-semibold uppercase tracking-wider text-slate-500">
              {label}
            </span>
            {hasItems ? (
              <span className="inline-flex items-center gap-1 rounded-md bg-emerald-500/10 px-1.5 py-0.5 text-[10px] font-semibold text-emerald-400 ring-1 ring-emerald-500/20">
                <Check size={10} strokeWidth={3} />
                OK
              </span>
            ) : (
              <span className="inline-flex items-center gap-1 rounded-md bg-slate-800/60 px-1.5 py-0.5 text-[10px] font-semibold text-slate-500">
                <AlertCircle size={10} />
                Пусто
              </span>
            )}
          </div>

          {hasItems ? (
            <div className="space-y-3">
              {items.map((product, idx) => {
                const rows = buildSpecRows(product, key);
                return (
                  <div key={`${product.id}-${idx}`}>
                    <div className="flex items-baseline justify-between gap-2">
                      <p className="truncate text-sm font-semibold text-white">
                        {product.name}
                      </p>
                      <span className="shrink-0 text-sm font-bold text-indigo-300">
                        {formatPrice(product.price)}
                      </span>
                    </div>

                    {product.brand?.name && (
                      <p className="mt-0.5 text-[11px] font-medium text-slate-500">
                        {product.brand.name}
                      </p>
                    )}

                    {rows.length > 0 && (
                      <div className="mt-2 flex flex-wrap gap-1.5">
                        {rows.map((row) => (
                          <span key={row.label} className="chip">
                            <span className="text-slate-500">{row.label}:</span>
                            <span className="text-slate-200">{row.value}</span>
                          </span>
                        ))}
                      </div>
                    )}
                  </div>
                );
              })}
            </div>
          ) : (
            <button
              type="button"
              onClick={() => onEmptyClick?.(key)}
              className="flex w-full items-center gap-2 rounded-lg border border-slate-800 bg-slate-950/40 px-3 py-2 text-xs text-slate-500 transition-colors hover:border-indigo-500/40 hover:text-indigo-300"
            >
              <Plus size={14} />
              Не выбрано — запустите автоподбор
            </button>
          )}
        </div>
      </div>
    </div>
  );
}