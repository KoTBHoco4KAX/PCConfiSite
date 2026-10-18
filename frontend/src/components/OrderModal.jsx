import { useState } from 'react';
import {
  X,
  ShoppingCart,
  CheckCircle2,
  Loader2,
  AlertTriangle,
  PackageCheck,
} from 'lucide-react';
import client, { extractErrorMessages } from '../api/client';
import { formatPrice, toArray } from '../utils/format';

export default function OrderModal({
  isOpen,
  onClose,
  components,
  totalPrice,
  isAuthenticated,
  onRequireLogin,
}) {
  const [note, setNote] = useState('');
  const [loading, setLoading] = useState(false);
  const [errors, setErrors] = useState([]);
  const [orderNumber, setOrderNumber] = useState(null);

  if (!isOpen) return null;

  const allProducts = [
    ...toArray(components.cpu),
    ...toArray(components.gpu),
    ...toArray(components.motherboard),
    ...toArray(components.ram),
    ...toArray(components.cooling),
    ...toArray(components.storage),
    ...toArray(components.case),
    ...toArray(components.psu),
  ];

  const productIds = allProducts.map((p) => p.id);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setErrors([]);

    if (!isAuthenticated) {
      onRequireLogin?.();
      return;
    }

    if (productIds.length === 0) {
      setErrors(['Сборка пуста — добавьте комплектующие.']);
      return;
    }

    setLoading(true);
    try {
      const { data } = await client.post('/orders', {
        product_ids: productIds,
        customer_note: note.trim() || null,
      });

      const number =
        data?.order?.order_number ||
        data?.order_number ||
        data?.data?.order_number;
      setOrderNumber(number || '—');
    } catch (err) {
      setErrors(extractErrorMessages(err));
    } finally {
      setLoading(false);
    }
  };

  const handleClose = () => {
    setNote('');
    setErrors([]);
    setOrderNumber(null);
    onClose?.();
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div
        className="absolute inset-0 bg-slate-950/80 backdrop-blur-sm"
        onClick={handleClose}
      />

      <div className="relative z-10 w-full max-w-2xl overflow-hidden rounded-2xl border border-slate-800 bg-slate-900 shadow-2xl">
        {/* Header */}
        <div className="flex items-center justify-between border-b border-slate-800 px-6 py-4">
          <div className="flex items-center gap-3">
            <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600">
              <ShoppingCart size={18} className="text-white" />
            </div>
            <div>
              <h2 className="text-base font-bold text-white">
                {orderNumber ? 'Заказ оформлен' : 'Оформление заказа'}
              </h2>
              <p className="text-xs text-slate-500">
                {orderNumber
                  ? 'Менеджер свяжется с вами в течение 24 часов'
                  : 'Проверьте состав сборки перед отправкой'}
              </p>
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

        {/* Body */}
        {orderNumber ? (
          <div className="px-6 py-10 text-center">
            <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-emerald-500/10 ring-1 ring-emerald-500/30">
              <PackageCheck size={28} className="text-emerald-400" />
            </div>
            <p className="text-sm text-slate-400">Ваш номер заказа</p>
            <p className="mt-1 text-2xl font-extrabold tracking-tight text-white">
              {orderNumber}
            </p>
            <p className="mx-auto mt-3 max-w-md text-sm text-slate-500">
              Мы зафиксировали состав и цены на момент оформления. Следите за
              статусом в личном кабинете.
            </p>
            <button
              type="button"
              onClick={handleClose}
              className="btn-primary mt-6"
            >
              Отлично, закрыть
            </button>
          </div>
        ) : (
          <form onSubmit={handleSubmit}>
            <div className="max-h-[55vh] overflow-y-auto px-6 py-4">
              <div className="mb-4 space-y-1.5">
                {allProducts.length === 0 && (
                  <p className="rounded-lg border border-amber-500/30 bg-amber-500/5 px-3 py-2 text-xs text-amber-300">
                    Сборка пуста. Запустите автоподбор или добавьте комплектующие.
                  </p>
                )}

                {allProducts.map((p) => (
                  <div
                    key={p.id}
                    className="flex items-center justify-between rounded-lg border border-slate-800 bg-slate-950/50 px-3 py-2"
                  >
                    <div className="flex items-center gap-2.5 min-w-0">
                      <CheckCircle2
                        size={14}
                        className="shrink-0 text-emerald-400"
                      />
                      <span className="truncate text-sm text-slate-200">
                        {p.name}
                      </span>
                    </div>
                    <span className="ml-3 shrink-0 text-sm font-semibold text-slate-300">
                      {formatPrice(p.price)}
                    </span>
                  </div>
                ))}
              </div>

              <label className="block">
                <span className="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-400">
                  Комментарий к заказу
                </span>
                <textarea
                  value={note}
                  onChange={(e) => setNote(e.target.value)}
                  rows={3}
                  maxLength={2000}
                  placeholder="Например: собрать и протестировать под нагрузкой, не звонить до 18:00..."
                  className="w-full resize-none rounded-xl border border-slate-800 bg-slate-950/60 px-3 py-2.5 text-sm text-slate-200 placeholder-slate-600 outline-none transition-colors focus:border-indigo-500/60 focus:bg-slate-950"
                />
              </label>

              {errors.length > 0 && (
                <div className="mt-4 space-y-1.5 rounded-xl border border-rose-500/30 bg-rose-500/5 px-3 py-2.5">
                  <div className="flex items-center gap-1.5 text-xs font-semibold text-rose-300">
                    <AlertTriangle size={13} />
                    Ошибка оформления
                  </div>
                  <ul className="ml-4 list-disc space-y-0.5 text-xs text-rose-200/90">
                    {errors.map((msg, i) => (
                      <li key={i}>{msg}</li>
                    ))}
                  </ul>
                </div>
              )}
            </div>

            <div className="flex items-center justify-between gap-3 border-t border-slate-800 bg-slate-950/40 px-6 py-4">
              <div>
                <p className="text-[11px] uppercase tracking-wider text-slate-500">
                  Итого
                </p>
                <p className="text-xl font-extrabold text-white">
                  {formatPrice(totalPrice)}
                </p>
              </div>
              <div className="flex items-center gap-2">
                <button
                  type="button"
                  onClick={handleClose}
                  className="btn-ghost"
                >
                  Отмена
                </button>
                <button
                  type="submit"
                  disabled={loading || allProducts.length === 0}
                  className="btn-primary"
                >
                  {loading ? (
                    <>
                      <Loader2 size={16} className="animate-spin" />
                      Отправка...
                    </>
                  ) : (
                    <>
                      <ShoppingCart size={16} />
                      Подтвердить заказ
                    </>
                  )}
                </button>
              </div>
            </div>
          </form>
        )}
      </div>
    </div>
  );
}