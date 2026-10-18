import { useEffect, useState } from 'react';
import {
  X, ShoppingBag, Loader2, AlertTriangle, Search, ChevronRight, Clock,
} from 'lucide-react';
import client, { extractErrorMessages } from '../api/client';
import { formatPrice } from '../utils/format';

const STATUSES = [
  { value: 'new',            label: 'Новый',           color: 'bg-slate-500/10 text-slate-300 ring-slate-500/30' },
  { value: 'checking',       label: 'Проверка',        color: 'bg-sky-500/10 text-sky-300 ring-sky-500/30' },
  { value: 'awaiting_parts', label: 'Ожидание деталей', color: 'bg-amber-500/10 text-amber-300 ring-amber-500/30' },
  { value: 'assembling',     label: 'Сборка',          color: 'bg-indigo-500/10 text-indigo-300 ring-indigo-500/30' },
  { value: 'ready',          label: 'Готов',           color: 'bg-emerald-500/10 text-emerald-300 ring-emerald-500/30' },
  { value: 'cancelled',      label: 'Отменён',         color: 'bg-rose-500/10 text-rose-300 ring-rose-500/30' },
];

const STATUS_BY_VALUE = Object.fromEntries(STATUSES.map((s) => [s.value, s]));

export default function OrdersManageModal({ isOpen, onClose }) {
  const [orders, setOrders] = useState([]);
  const [loading, setLoading] = useState(false);
  const [errors, setErrors] = useState([]);
  const [search, setSearch] = useState('');
  const [filterStatus, setFilterStatus] = useState('');
  const [activeOrder, setActiveOrder] = useState(null);
  const [newStatus, setNewStatus] = useState('');
  const [comment, setComment] = useState('');
  const [saving, setSaving] = useState(false);

  useEffect(() => {
    if (!isOpen) return;
    loadOrders();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [isOpen, filterStatus]);

  const loadOrders = async (q = search) => {
    setLoading(true);
    setErrors([]);
    try {
      const params = {};
      if (q) params.search = q;
      if (filterStatus) params.status = filterStatus;

      const { data } = await client.get('/manage/orders', { params });
      setOrders(data.data || []);
    } catch (err) {
      setErrors(extractErrorMessages(err));
    } finally {
      setLoading(false);
    }
  };

  const submitSearch = (e) => {
    e.preventDefault();
    loadOrders(search.trim());
  };

  const openStatusChange = (order) => {
    setActiveOrder(order);
    setNewStatus(order.status);
    setComment('');
    setErrors([]);
  };

  const closeStatusChange = () => {
    setActiveOrder(null);
    setNewStatus('');
    setComment('');
  };

  const applyStatus = async () => {
    if (!activeOrder) return;
    setSaving(true);
    setErrors([]);
    try {
      const { data } = await client.patch(
        `/manage/orders/${activeOrder.id}/status`,
        { status: newStatus, comment: comment.trim() || null }
      );
      setOrders((prev) => prev.map((o) => (o.id === activeOrder.id ? data.order : o)));
      closeStatusChange();
    } catch (err) {
      setErrors(extractErrorMessages(err));
    } finally {
      setSaving(false);
    }
  };

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div className="absolute inset-0 bg-slate-950/85 backdrop-blur-md" onClick={onClose} />

      <div className="relative z-10 flex w-full max-w-4xl flex-col overflow-hidden rounded-3xl border border-slate-800 bg-slate-900 shadow-2xl">
        <div className="flex items-center justify-between border-b border-slate-800 px-6 py-4">
          <div className="flex items-center gap-3">
            <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-amber-500 to-orange-600 shadow-lg shadow-amber-500/30">
              <ShoppingBag size={18} className="text-white" />
            </div>
            <div>
              <h2 className="text-base font-bold text-white">Заказы</h2>
              <p className="text-[11px] text-slate-500">Управление статусами заказов</p>
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

        {errors.length > 0 && (
          <div className="border-b border-rose-500/30 bg-rose-500/5 px-6 py-2.5">
            <div className="mb-1 flex items-center gap-1.5 text-xs font-semibold text-rose-300">
              <AlertTriangle size={13} />
              Ошибка
            </div>
            <ul className="ml-4 list-disc space-y-0.5 text-[11px] text-rose-200/90">
              {errors.map((m, i) => <li key={i}>{m}</li>)}
            </ul>
          </div>
        )}

        <div className="flex flex-col gap-2 border-b border-slate-800 bg-slate-950/40 px-6 py-3 sm:flex-row">
          <form onSubmit={submitSearch} className="flex-1">
            <div className="relative">
              <Search size={14} className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500" />
              <input
                type="text"
                value={search}
                onChange={(e) => setSearch(e.target.value)}
                placeholder="Номер заказа, имя или email…"
                className="w-full rounded-xl border border-slate-800 bg-slate-950/60 py-2 pl-9 pr-24 text-sm text-slate-200 outline-none focus:border-indigo-500/60"
              />
              <button type="submit" className="absolute right-1.5 top-1/2 -translate-y-1/2 rounded-lg bg-slate-800/60 px-3 py-1 text-[11px] font-medium text-slate-300 hover:bg-slate-800">
                Найти
              </button>
            </div>
          </form>

          <select
            value={filterStatus}
            onChange={(e) => setFilterStatus(e.target.value)}
            className="rounded-xl border border-slate-800 bg-slate-950/60 px-3 py-2 text-sm text-slate-200 outline-none focus:border-indigo-500/60"
          >
            <option value="">Все статусы</option>
            {STATUSES.map((s) => <option key={s.value} value={s.value}>{s.label}</option>)}
          </select>
        </div>

        <div className="max-h-[60vh] overflow-y-auto px-6 py-5">
          {loading && orders.length === 0 ? (
            <div className="flex flex-col items-center gap-3 py-12 text-slate-500">
              <Loader2 size={24} className="animate-spin" />
              <span className="text-xs">Загрузка заказов…</span>
            </div>
          ) : orders.length === 0 ? (
            <div className="flex flex-col items-center gap-3 py-12 text-slate-500">
              <ShoppingBag size={32} className="opacity-40" />
              <span className="text-sm">Заказов не найдено</span>
            </div>
          ) : (
            <div className="space-y-2">
              {orders.map((o) => {
                const st = STATUS_BY_VALUE[o.status] || STATUS_BY_VALUE.new;
                return (
                  <button
                    key={o.id}
                    type="button"
                    onClick={() => openStatusChange(o)}
                    className="group w-full rounded-xl border border-slate-800 bg-slate-950/40 px-4 py-3 text-left transition-colors hover:border-slate-700 hover:bg-slate-950/70"
                  >
                    <div className="flex items-start justify-between gap-3">
                      <div className="min-w-0 flex-1">
                        <div className="flex flex-wrap items-center gap-2">
                          <span className="text-sm font-bold text-white">{o.order_number}</span>
                          <span className={`rounded-md px-1.5 py-0.5 text-[10px] font-semibold ring-1 ${st.color}`}>
                            {st.label}
                          </span>
                        </div>
                        <p className="mt-1 truncate text-[11px] text-slate-500">
                          {o.user?.name} · {o.user?.email}
                        </p>
                        <p className="mt-0.5 text-[11px] text-slate-500">
                          {o.items?.length || 0} позиций · {formatPrice(o.total_price)} ·{' '}
                          <Clock size={10} className="inline" />{' '}
                          {new Date(o.created_at).toLocaleDateString('ru-RU')}
                        </p>
                      </div>
                      <ChevronRight size={16} className="mt-1 shrink-0 text-slate-600 transition-transform group-hover:translate-x-0.5 group-hover:text-slate-400" />
                    </div>
                  </button>
                );
              })}
            </div>
          )}
        </div>

        {activeOrder && (
          <div className="border-t border-slate-800 bg-slate-950/60 px-6 py-5">
            <div className="mb-4 flex items-center justify-between">
              <div>
                <h3 className="text-sm font-bold text-white">
                  Заказ {activeOrder.order_number}
                </h3>
                <p className="text-[11px] text-slate-500">
                  {activeOrder.user?.name} · {formatPrice(activeOrder.total_price)}
                </p>
              </div>
              <button
                type="button"
                onClick={closeStatusChange}
                className="rounded-lg p-1.5 text-slate-500 hover:bg-slate-800 hover:text-white"
              >
                <X size={14} />
              </button>
            </div>

            <div className="grid grid-cols-1 gap-3 sm:grid-cols-[1fr_auto]">
              <select
                value={newStatus}
                onChange={(e) => setNewStatus(e.target.value)}
                className="rounded-xl border border-slate-800 bg-slate-950/60 px-3 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500/60"
              >
                {STATUSES.map((s) => <option key={s.value} value={s.value}>{s.label}</option>)}
              </select>

              <button
                type="button"
                onClick={applyStatus}
                disabled={saving || newStatus === activeOrder.status}
                className="btn-primary !py-2.5 !text-sm"
              >
                {saving ? <Loader2 size={14} className="animate-spin" /> : null}
                Применить
              </button>
            </div>

            <textarea
              value={comment}
              onChange={(e) => setComment(e.target.value)}
              rows={2}
              maxLength={2000}
              placeholder="Комментарий к смене статуса (необязательно)"
              className="mt-3 w-full resize-none rounded-xl border border-slate-800 bg-slate-950/60 px-3 py-2 text-sm text-slate-200 outline-none focus:border-indigo-500/60"
            />
          </div>
        )}
      </div>
    </div>
  );
}