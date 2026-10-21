import { useEffect, useState } from 'react';
import {
  X, Bookmark, Loader2, Trash2, Download, AlertTriangle, FolderOpen, Users,
  ShoppingBag, Clock,
} from 'lucide-react';
import client, { extractErrorMessages } from '../api/client';
import { formatPrice } from '../utils/format';

const ORDER_STATUS = {
  new:            'Новый',
  checking:       'Проверка',
  awaiting_parts: 'Ожидание деталей',
  assembling:     'Сборка',
  ready:          'Готов',
  cancelled:      'Отменён',
};

export default function SavedBuildsModal({ isOpen, onClose, onLoadBuild, isAdmin, initialTab = 'drafts' }) {
  const [tab, setTab] = useState(initialTab);
  const [builds, setBuilds] = useState([]);
  const [orders, setOrders] = useState([]);
  const [loading, setLoading] = useState(false);
  const [errors, setErrors] = useState([]);

  useEffect(() => {
    if (isOpen) setTab(initialTab);
  }, [isOpen, initialTab]);

  useEffect(() => {
    if (!isOpen) return;
    setErrors([]);
    if (tab === 'orders') loadOrders();
    else loadBuilds(tab);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [isOpen, tab]);

  const loadBuilds = async (mode) => {
    setLoading(true);
    try {
      const url = mode === 'all' ? '/manage/builds' : '/builds';
      const { data } = await client.get(url);
      const list = mode === 'all' ? (data.data || []) : (data.builds || []);
      setBuilds(list);
    } catch (err) {
      setErrors(extractErrorMessages(err));
    } finally {
      setLoading(false);
    }
  };

  const loadOrders = async () => {
    setLoading(true);
    try {
      const { data } = await client.get('/orders');
      setOrders(data.orders || []);
    } catch (err) {
      setErrors(extractErrorMessages(err));
    } finally {
      setLoading(false);
    }
  };

  const handleLoad = async (id) => {
    setLoading(true);
    setErrors([]);
    try {
      const { data } = await client.get(`/builds/${id}`);
      onLoadBuild?.(data.build);
      onClose?.();
    } catch (err) {
      setErrors(extractErrorMessages(err));
    } finally {
      setLoading(false);
    }
  };

  const handleDelete = async (id) => {
    if (!window.confirm('Удалить черновик?')) return;
    try {
      await client.delete(`/builds/${id}`);
      setBuilds((prev) => prev.filter((b) => b.id !== id));
    } catch (err) {
      setErrors(extractErrorMessages(err));
    }
  };

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div className="absolute inset-0 bg-slate-950/85 backdrop-blur-md" onClick={onClose} />

      <div className="relative z-10 flex w-full max-w-2xl flex-col overflow-hidden rounded-3xl border border-slate-800 bg-slate-900 shadow-2xl">
        <div className="flex items-center justify-between border-b border-slate-800 px-6 py-4">
          <div className="flex items-center gap-3">
            <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600 shadow-lg shadow-indigo-500/30">
              <Bookmark size={18} className="text-white" />
            </div>
            <div>
              <h2 className="text-base font-bold text-white">
                {tab === 'orders' ? 'Мои заказы' : (tab === 'all' ? 'Все черновики' : 'Черновики')}
              </h2>
              <p className="text-[11px] text-slate-500">
                {tab === 'orders' ? 'История оформленных заказов' : 'Сохранённые конфигурации'}
              </p>
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

        <div className="flex gap-2 border-b border-slate-800 bg-slate-950/40 px-6 py-3">
          <button
            type="button"
            onClick={() => setTab('drafts')}
            className={`rounded-lg px-3 py-1.5 text-xs font-semibold transition-all ${
              tab === 'drafts'
                ? 'bg-indigo-500/20 text-indigo-300 ring-1 ring-indigo-500/40'
                : 'text-slate-400 hover:text-slate-200'
            }`}
          >
            <FolderOpen size={13} className="mr-1.5 inline" />
            Черновики
          </button>
          <button
            type="button"
            onClick={() => setTab('orders')}
            className={`rounded-lg px-3 py-1.5 text-xs font-semibold transition-all ${
              tab === 'orders'
                ? 'bg-emerald-500/20 text-emerald-300 ring-1 ring-emerald-500/40'
                : 'text-slate-400 hover:text-slate-200'
            }`}
          >
            <ShoppingBag size={13} className="mr-1.5 inline" />
            Мои заказы
          </button>
          {isAdmin && (
            <button
              type="button"
              onClick={() => setTab('all')}
              className={`rounded-lg px-3 py-1.5 text-xs font-semibold transition-all ${
                tab === 'all'
                  ? 'bg-fuchsia-500/20 text-fuchsia-300 ring-1 ring-fuchsia-500/40'
                  : 'text-slate-400 hover:text-slate-200'
              }`}
            >
              <Users size={13} className="mr-1.5 inline" />
              Все
            </button>
          )}
        </div>

        <div className="max-h-[60vh] overflow-y-auto px-6 py-5">
          {errors.length > 0 && (
            <div className="mb-4 rounded-xl border border-rose-500/30 bg-rose-500/5 px-3 py-2.5">
              <div className="mb-1 flex items-center gap-1.5 text-xs font-semibold text-rose-300">
                <AlertTriangle size={13} />
                Ошибка
              </div>
              <ul className="ml-4 list-disc space-y-0.5 text-[11px] text-rose-200/90">
                {errors.map((m, i) => <li key={i}>{m}</li>)}
              </ul>
            </div>
          )}

          {loading ? (
            <div className="flex flex-col items-center gap-3 py-12 text-slate-500">
              <Loader2 size={24} className="animate-spin" />
              <span className="text-xs">Загрузка…</span>
            </div>
          ) : tab === 'orders' ? (
            orders.length === 0 ? (
              <div className="flex flex-col items-center gap-3 py-12 text-slate-500">
                <ShoppingBag size={32} className="opacity-40" />
                <span className="text-sm">Заказов пока нет</span>
              </div>
            ) : (
              <div className="space-y-2">
                {orders.map((o) => (
                  <div
                    key={o.id}
                    className="rounded-xl border border-slate-800 bg-slate-950/40 px-4 py-3"
                  >
                    <div className="flex items-center justify-between gap-3">
                      <div>
                        <p className="text-sm font-bold text-white">{o.order_number}</p>
                        <p className="mt-0.5 flex items-center gap-2 text-[11px] text-slate-500">
                          <Clock size={10} />
                          {new Date(o.created_at).toLocaleString('ru-RU')}
                        </p>
                      </div>
                      <div className="text-right">
                        <p className="text-sm font-bold text-indigo-300">{formatPrice(o.total_price)}</p>
                        <p className="text-[11px] text-slate-500">
                          {ORDER_STATUS[o.status] || o.status}
                        </p>
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            )
          ) : builds.length === 0 ? (
            <div className="flex flex-col items-center gap-3 py-12 text-slate-500">
              <Bookmark size={32} className="opacity-40" />
              <span className="text-sm">Здесь пока пусто</span>
              <span className="text-xs text-slate-600">
                Соберите конфигурацию и нажмите «В черновик»
              </span>
            </div>
          ) : (
            <div className="space-y-2">
              {builds.map((b) => (
                <div
                  key={b.id}
                  className="group flex items-center justify-between gap-3 rounded-xl border border-slate-800 bg-slate-950/40 px-4 py-3 transition-colors hover:border-slate-700"
                >
                  <div className="min-w-0 flex-1">
                    <p className="truncate text-sm font-semibold text-white">{b.name}</p>
                    <p className="mt-0.5 flex flex-wrap items-center gap-2 text-[11px] text-slate-500">
                      <span className="text-indigo-300">{formatPrice(b.total_price)}</span>
                      {b.user && (
                        <span className="text-slate-500">
                          · {b.user.name} ({b.user.email})
                        </span>
                      )}
                    </p>
                  </div>

                  <div className="flex shrink-0 items-center gap-1.5">
                    <button
                      type="button"
                      onClick={() => handleLoad(b.id)}
                      disabled={loading}
                      className="inline-flex items-center gap-1.5 rounded-lg border border-slate-800 bg-slate-900/60 px-2.5 py-1.5 text-[11px] font-medium text-slate-300 transition-colors hover:border-indigo-500/40 hover:text-indigo-300 disabled:opacity-40"
                    >
                      <Download size={12} />
                      Загрузить
                    </button>
                    <button
                      type="button"
                      onClick={() => handleDelete(b.id)}
                      className="rounded-lg p-1.5 text-slate-600 transition-colors hover:bg-slate-800 hover:text-rose-400"
                      title="Удалить"
                    >
                      <Trash2 size={13} />
                    </button>
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>
      </div>
    </div>
  );
}