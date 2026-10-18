import { Zap, AlertTriangle, CheckCircle2 } from 'lucide-react';

export default function WattageBar({
  estimated = 0,
  recommended = 0,
  psu = 0,
}) {
  const safePsu = Number(psu) || 0;
  const load = Number(estimated) || 0;
  const max = Math.max(safePsu, recommended, load, 1);

  // Процент загрузки БП
  const loadPct = Math.min(100, Math.round((load / max) * 100));
  // Процент рекомендации
  const recPct = Math.min(100, Math.round((recommended / max) * 100));

  // Статус запаса
  let status = 'ok';
  let StatusIcon = CheckCircle2;
  let statusText = 'Запас достаточен';
  let statusColor = 'text-emerald-400';

  if (safePsu === 0) {
    status = 'missing';
    StatusIcon = AlertTriangle;
    statusText = 'БП не выбран';
    statusColor = 'text-amber-400';
  } else if (safePsu < load) {
    status = 'critical';
    StatusIcon = AlertTriangle;
    statusText = 'Недостаточно мощности';
    statusColor = 'text-rose-400';
  } else if (safePsu < recommended) {
    status = 'warning';
    StatusIcon = AlertTriangle;
    statusText = 'Запас ниже рекомендуемого';
    statusColor = 'text-amber-400';
  }

  return (
    <div className="rounded-2xl border border-slate-800 bg-slate-900/40 p-4">
      <div className="mb-3 flex items-center justify-between">
        <div className="flex items-center gap-2">
          <Zap size={16} className="text-indigo-400" />
          <span className="text-xs font-semibold uppercase tracking-wider text-slate-400">
            Энергопотребление
          </span>
        </div>
        <span className={`inline-flex items-center gap-1 text-[11px] font-semibold ${statusColor}`}>
          <StatusIcon size={12} />
          {statusText}
        </span>
      </div>

      <div className="mb-4 flex items-end justify-between">
        <div>
          <p className="text-2xl font-bold text-white">
            {load}
            <span className="ml-1 text-sm font-medium text-slate-500">Вт</span>
          </p>
          <p className="mt-0.5 text-[11px] text-slate-500">
            Расчётная нагрузка системы
          </p>
        </div>
        <div className="text-right">
          <p className="text-lg font-semibold text-indigo-300">
            {safePsu || '—'}
            <span className="ml-0.5 text-xs font-medium text-slate-500">Вт</span>
          </p>
          <p className="mt-0.5 text-[11px] text-slate-500">БП в сборке</p>
        </div>
      </div>

      {/* Прогресс-бар */}
      <div className="relative h-3 overflow-hidden rounded-full bg-slate-950 ring-1 ring-slate-800">
        {/* Фон: рекомендуемый уровень */}
        <div
          className="absolute inset-y-0 left-0 bg-gradient-to-r from-indigo-500/20 to-indigo-500/5"
          style={{ width: `${recPct}%` }}
        />
        {/* Активная нагрузка */}
        <div
          className={`absolute inset-y-0 left-0 rounded-full transition-all duration-500 ${
            status === 'critical'
              ? 'bg-gradient-to-r from-rose-500 to-rose-600'
              : status === 'warning'
              ? 'bg-gradient-to-r from-amber-500 to-orange-500'
              : 'bg-gradient-to-r from-emerald-500 to-teal-500'
          }`}
          style={{ width: `${loadPct}%` }}
        />
      </div>

      <div className="mt-2 flex justify-between text-[10px] font-medium text-slate-500">
        <span>0 Вт</span>
        <span>
          Рекомендуемый БП:{' '}
          <span className="text-slate-300">{recommended || '—'} Вт</span>
        </span>
        <span>{max} Вт</span>
      </div>
    </div>
  );
}