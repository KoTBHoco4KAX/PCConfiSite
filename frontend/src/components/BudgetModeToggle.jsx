import { Gauge, ShieldCheck } from 'lucide-react';

export default function BudgetModeToggle({ strict, onChange, disabled }) {
  return (
    <div className="rounded-2xl border border-slate-800 bg-slate-900/40 p-4">
      <div className="mb-3 flex items-center gap-2">
        <Gauge size={16} className="text-indigo-400" />
        <span className="text-xs font-semibold uppercase tracking-wider text-slate-400">
          Режим бюджета
        </span>
      </div>

      <div className="grid grid-cols-2 gap-2 rounded-xl border border-slate-800 bg-slate-950/50 p-1">
        <button
          type="button"
          onClick={() => onChange(false)}
          disabled={disabled}
          className={`flex items-center justify-center gap-1.5 rounded-lg px-3 py-2 text-xs font-semibold transition-all ${
            !strict
              ? 'bg-gradient-to-r from-indigo-500 to-violet-600 text-white shadow-lg shadow-indigo-500/20'
              : 'text-slate-400 hover:text-slate-200'
          } disabled:opacity-50`}
        >
          <Gauge size={13} />
          С люфтом
        </button>

        <button
          type="button"
          onClick={() => onChange(true)}
          disabled={disabled}
          className={`flex items-center justify-center gap-1.5 rounded-lg px-3 py-2 text-xs font-semibold transition-all ${
            strict
              ? 'bg-gradient-to-r from-emerald-500 to-teal-600 text-white shadow-lg shadow-emerald-500/20'
              : 'text-slate-400 hover:text-slate-200'
          } disabled:opacity-50`}
        >
          <ShieldCheck size={13} />
          Строго в бюджет
        </button>
      </div>

      <p className="mt-2 text-[11px] leading-relaxed text-slate-500">
        {strict
          ? 'Итог не превысит выбранный бюджет. Комплектующие могут быть слабее.'
          : 'Максимум мощности за деньги. Итог может превысить бюджет на 10–15%.'}
      </p>
    </div>
  );
}