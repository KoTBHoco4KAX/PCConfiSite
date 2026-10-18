import { useState } from 'react';
import { X, Save, Loader2, CheckCircle2, AlertTriangle } from 'lucide-react';
import client, { extractErrorMessages } from '../api/client';

export default function SaveBuildModal({ isOpen, onClose, snapshot, defaultName = '', onSaved }) {
  const [name, setName] = useState(defaultName);
  const [loading, setLoading] = useState(false);
  const [errors, setErrors] = useState([]);
  const [saved, setSaved] = useState(false);

  if (!isOpen) return null;

  const handleSubmit = async (e) => {
    e.preventDefault();
    setErrors([]);
    setLoading(true);
    try {
      const { data } = await client.post('/builds', {
        name: name.trim() || 'Моя сборка',
        total_price: snapshot?.total_price || 0,
        snapshot: snapshot || {},
      });
      setSaved(true);
      onSaved?.(data.build);
    } catch (err) {
      setErrors(extractErrorMessages(err));
    } finally {
      setLoading(false);
    }
  };

  const handleClose = () => {
    setName(defaultName);
    setErrors([]);
    setSaved(false);
    onClose?.();
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div className="absolute inset-0 bg-slate-950/85 backdrop-blur-md" onClick={handleClose} />

      <div className="relative z-10 w-full max-w-md overflow-hidden rounded-3xl border border-slate-800 bg-slate-900 shadow-2xl">
        <div className="flex items-center justify-between border-b border-slate-800 px-6 py-4">
          <div className="flex items-center gap-3">
            <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 shadow-lg shadow-emerald-500/30">
              <Save size={18} className="text-white" />
            </div>
            <div>
              <h2 className="text-base font-bold text-white">Сохранить черновик</h2>
              <p className="text-[11px] text-slate-500">Доступно в личном кабинете</p>
            </div>
          </div>
          <button
            type="button"
            onClick={handleClose}
            className="rounded-lg p-2 text-slate-500 transition-colors hover:bg-slate-800 hover:text-white"
          >
            <X size={18} />
          </button>
        </div>

        {saved ? (
          <div className="px-6 py-10 text-center">
            <div className="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-emerald-500/10 ring-1 ring-emerald-500/30">
              <CheckCircle2 size={26} className="text-emerald-400" />
            </div>
            <p className="text-sm font-semibold text-white">Черновик сохранён</p>
            <p className="mt-1 text-xs text-slate-500">
              Найти его можно в разделе «Черновики» в шапке.
            </p>
            <button type="button" onClick={handleClose} className="btn-primary mt-6">
              Отлично
            </button>
          </div>
        ) : (
          <form onSubmit={handleSubmit} className="space-y-4 px-6 py-5">
            <label className="block">
              <span className="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-400">
                Название черновика
              </span>
              <input
                type="text"
                value={name}
                onChange={(e) => setName(e.target.value)}
                maxLength={120}
                placeholder="Например: Игровая сборка для 1440p"
                autoFocus
                className="w-full rounded-xl border border-slate-800 bg-slate-950/60 px-3 py-2.5 text-sm text-slate-200 outline-none transition-colors focus:border-indigo-500/60"
              />
            </label>

            {errors.length > 0 && (
              <div className="rounded-xl border border-rose-500/30 bg-rose-500/5 px-3 py-2.5">
                <div className="mb-1 flex items-center gap-1.5 text-xs font-semibold text-rose-300">
                  <AlertTriangle size={13} />
                  Ошибка сохранения
                </div>
                <ul className="ml-4 list-disc space-y-0.5 text-[11px] text-rose-200/90">
                  {errors.map((m, i) => <li key={i}>{m}</li>)}
                </ul>
              </div>
            )}

            <button type="submit" disabled={loading} className="btn-primary w-full">
              {loading ? (
                <>
                  <Loader2 size={16} className="animate-spin" />
                  Сохранение…
                </>
              ) : (
                <>
                  <Save size={16} />
                  Сохранить
                </>
              )}
            </button>
          </form>
        )}
      </div>
    </div>
  );
}