import { useState } from 'react';
import { X, LogIn, Loader2, AlertTriangle, User } from 'lucide-react';
import client, {
  extractErrorMessages,
  TOKEN_KEY,
  USER_KEY,
} from '../api/client';

export default function LoginModal({ isOpen, onClose, onSuccess, onSwitchToRegister }) {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [loading, setLoading] = useState(false);
  const [errors, setErrors] = useState([]);

  if (!isOpen) return null;

  const handleSubmit = async (e) => {
    e.preventDefault();
    setErrors([]);
    setLoading(true);
    try {
      const { data } = await client.post('/auth/login', { email, password });
      localStorage.setItem(TOKEN_KEY, data.token);
      localStorage.setItem(USER_KEY, JSON.stringify(data.user));
      onSuccess?.(data.user, data.token);
      onClose?.();
      setEmail('');
      setPassword('');
    } catch (err) {
      setErrors(extractErrorMessages(err));
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div className="absolute inset-0 bg-slate-950/80 backdrop-blur-sm" onClick={onClose} />

      <div className="relative z-10 w-full max-w-md overflow-hidden rounded-2xl border border-slate-800 bg-slate-900 shadow-2xl">
        <div className="flex items-center justify-between border-b border-slate-800 px-6 py-4">
          <div className="flex items-center gap-3">
            <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600">
              <User size={18} className="text-white" />
            </div>
            <div>
              <h2 className="text-base font-bold text-white">Вход в аккаунт</h2>
              <p className="text-xs text-slate-500">Sanctum · Bearer token</p>
            </div>
          </div>
          <button
            type="button"
            onClick={onClose}
            className="rounded-lg p-2 text-slate-500 transition-colors hover:bg-slate-800 hover:text-white"
          >
            <X size={18} />
          </button>
        </div>

        <form onSubmit={handleSubmit} className="space-y-4 px-6 py-5">
          <label className="block">
            <span className="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-400">
              Email
            </span>
            <input
              type="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              required
              autoComplete="email"
              className="w-full rounded-xl border border-slate-800 bg-slate-950/60 px-3 py-2.5 text-sm text-slate-200 outline-none transition-colors focus:border-indigo-500/60"
            />
          </label>

          <label className="block">
            <span className="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-400">
              Пароль
            </span>
            <input
              type="password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              required
              autoComplete="current-password"
              className="w-full rounded-xl border border-slate-800 bg-slate-950/60 px-3 py-2.5 text-sm text-slate-200 outline-none transition-colors focus:border-indigo-500/60"
            />
          </label>

          {errors.length > 0 && (
            <div className="rounded-xl border border-rose-500/30 bg-rose-500/5 px-3 py-2">
              <div className="flex items-center gap-1.5 text-xs font-semibold text-rose-300">
                <AlertTriangle size={13} />
                {errors[0]}
              </div>
            </div>
          )}

          <button type="submit" disabled={loading} className="btn-primary w-full">
            {loading ? (
              <>
                <Loader2 size={16} className="animate-spin" />
                Вход…
              </>
            ) : (
              <>
                <LogIn size={16} />
                Войти
              </>
            )}
          </button>

          <p className="pt-1 text-center text-[11px] text-slate-500">
            Нет аккаунта?{' '}
            <button
              type="button"
              onClick={onSwitchToRegister}
              className="font-semibold text-indigo-300 hover:text-indigo-200"
            >
              Зарегистрироваться
            </button>
          </p>
        </form>
      </div>
    </div>
  );
}