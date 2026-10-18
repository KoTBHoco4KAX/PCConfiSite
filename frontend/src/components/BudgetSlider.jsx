import { useEffect, useState } from 'react';
import { Wallet, Infinity as InfinityIcon, Pencil } from 'lucide-react';

const MIN = 30000;
const MAX = 200000;
const STEP = 5000;

function formatMoney(v) {
  const n = Number(v) || 0;
  return new Intl.NumberFormat('ru-RU').format(Math.round(n)) + ' ₽';
}

export default function BudgetSlider({ value, onChange, disabled }) {
  const isInfinite = Number(value) >= MAX;

  const [editing, setEditing] = useState(false);
  const [draft, setDraft] = useState('');

  useEffect(() => {
    if (!editing) {
      setDraft(String(Math.min(Number(value) || MIN, MAX)));
    }
  }, [value, editing]);

  const handleSlider = (e) => {
    const v = Number(e.target.value);
    onChange(v);
  };

  const commitDraft = () => {
    let v = Number(String(draft).replace(/[^\d]/g, ''));
    if (!v || v < MIN) v = MIN;
    onChange(v);
    setEditing(false);
  };

  const sliderPos = Math.min(Number(value) || MIN, MAX);

  return (
    <div className="rounded-2xl border border-slate-800 bg-slate-900/50 p-5 backdrop-blur-xl">
      <div className="mb-4 flex items-center justify-between gap-3">
        <div className="flex items-center gap-2">
          <Wallet size={16} className="text-indigo-400" />
          <span className="text-xs font-semibold uppercase tracking-wider text-slate-400">
            Целевой бюджет
          </span>
        </div>

        <div className="flex items-center gap-1.5 rounded-lg border border-slate-800 bg-slate-950/60 px-2.5 py-1.5">
          {editing ? (
            <input
              type="text"
              inputMode="numeric"
              autoFocus
              value={draft}
              onChange={(e) => setDraft(e.target.value)}
              onBlur={commitDraft}
              onKeyDown={(e) => {
                if (e.key === 'Enter') commitDraft();
                if (e.key === 'Escape') {
                  setEditing(false);
                }
              }}
              className="w-24 bg-transparent text-right text-sm font-bold text-white outline-none"
            />
          ) : (
            <button
              type="button"
              onClick={() => !disabled && setEditing(true)}
              className="flex items-center gap-1.5 text-sm font-bold text-white"
              title="Нажмите, чтобы ввести точное значение"
            >
              {isInfinite ? (
                <>
                  <InfinityIcon size={16} className="text-fuchsia-400" />
                  <span className="text-fuchsia-300">Без лимита</span>
                </>
              ) : (
                <>
                  {formatMoney(value)}
                  <Pencil size={11} className="text-slate-500" />
                </>
              )}
            </button>
          )}
        </div>
      </div>

      <div className="relative py-2">
        <input
          type="range"
          min={MIN}
          max={MAX}
          step={STEP}
          value={sliderPos}
          onChange={handleSlider}
          disabled={disabled}
          className="slider-native w-full cursor-pointer appearance-none bg-transparent"
          style={{
            background: `linear-gradient(to right, rgb(99 102 241) 0%, rgb(168 85 247) ${
              ((sliderPos - MIN) / (MAX - MIN)) * 100
            }%, rgb(30 41 59) ${((sliderPos - MIN) / (MAX - MIN)) * 100}%, rgb(30 41 59) 100%)`,
            height: '6px',
            borderRadius: '9999px',
          }}
        />
      </div>

      <div className="mt-2 flex justify-between text-[10px] font-medium text-slate-500">
        <span>{formatMoney(MIN)}</span>
        <span>{formatMoney((MIN + MAX) / 2)}</span>
        <span className="flex items-center gap-1">
          <span>{formatMoney(MAX)}</span>
          <InfinityIcon size={10} className="text-fuchsia-400" />
        </span>
      </div>

      <style>{`
        .slider-native::-webkit-slider-thumb {
          -webkit-appearance: none;
          appearance: none;
          width: 20px;
          height: 20px;
          border-radius: 9999px;
          background: linear-gradient(135deg, #818cf8, #c084fc);
          border: 2px solid #0f172a;
          box-shadow: 0 4px 12px rgba(129, 140, 248, 0.5);
          cursor: pointer;
          transition: transform 0.15s ease;
        }
        .slider-native::-webkit-slider-thumb:hover {
          transform: scale(1.15);
        }
        .slider-native::-moz-range-thumb {
          width: 20px;
          height: 20px;
          border-radius: 9999px;
          background: linear-gradient(135deg, #818cf8, #c084fc);
          border: 2px solid #0f172a;
          box-shadow: 0 4px 12px rgba(129, 140, 248, 0.5);
          cursor: pointer;
        }
      `}</style>
    </div>
  );
}