import { useEffect, useState } from 'react';
import {
  X, Users, Plus, Loader2, Trash2, Pencil, AlertTriangle,
  CheckCircle2, Search, Shield, Store, User as UserIcon,
} from 'lucide-react';
import client, { extractErrorMessages } from '../api/client';

const ROLE_LABELS = {
  buyer:  { label: 'Покупатель', icon: UserIcon, color: 'text-emerald-400', chip: 'bg-emerald-500/10 text-emerald-300 ring-emerald-500/30' },
  seller: { label: 'Продавец',   icon: Store,    color: 'text-amber-400',   chip: 'bg-amber-500/10 text-amber-300 ring-amber-500/30' },
  admin:  { label: 'Админ',      icon: Shield,   color: 'text-fuchsia-400', chip: 'bg-fuchsia-500/10 text-fuchsia-300 ring-fuchsia-500/30' },
};

const EMPTY_FORM = { id: null, name: '', email: '', password: '', roles: ['buyer'] };

export default function UserManagementModal({ isOpen, onClose, currentUserId }) {
  const [users, setUsers] = useState([]);
  const [roles, setRoles] = useState([]);
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [errors, setErrors] = useState([]);
  const [success, setSuccess] = useState('');
  const [search, setSearch] = useState('');
  const [form, setForm] = useState(EMPTY_FORM);
  const [showForm, setShowForm] = useState(false);

  useEffect(() => {
    if (!isOpen) return;
    loadRoles();
    loadUsers();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [isOpen]);

  useEffect(() => {
    if (!success) return;
    const t = setTimeout(() => setSuccess(''), 2500);
    return () => clearTimeout(t);
  }, [success]);

  const loadRoles = async () => {
    try {
      const { data } = await client.get('/manage/roles');
      setRoles(data.roles || []);
    } catch { /* ignore */ }
  };

  const loadUsers = async (q = '') => {
    setLoading(true);
    setErrors([]);
    try {
      const params = q ? { search: q } : {};
      const { data } = await client.get('/manage/users', { params });
      setUsers(data.data || []);
    } catch (err) {
      setErrors(extractErrorMessages(err));
    } finally {
      setLoading(false);
    }
  };

  const submitSearch = (e) => {
    e.preventDefault();
    loadUsers(search.trim());
  };

  const openCreate = () => {
    setForm(EMPTY_FORM);
    setShowForm(true);
    setErrors([]);
  };

  const openEdit = (user) => {
    setForm({
      id: user.id,
      name: user.name,
      email: user.email,
      password: '',
      roles: user.roles && user.roles.length ? user.roles : ['buyer'],
    });
    setShowForm(true);
    setErrors([]);
  };

  const closeForm = () => {
    setForm(EMPTY_FORM);
    setShowForm(false);
  };

  const toggleRole = (slug) => {
    setForm((prev) => {
      const has = prev.roles.includes(slug);
      return {
        ...prev,
        roles: has ? prev.roles.filter((r) => r !== slug) : [...prev.roles, slug],
      };
    });
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setSaving(true);
    setErrors([]);
    try {
      const payload = {
        name:  form.name.trim(),
        email: form.email.trim(),
        roles: form.roles,
      };
      if (form.password) payload.password = form.password;

      if (form.id) {
        const { data } = await client.patch(`/manage/users/${form.id}`, payload);
        setUsers((prev) => prev.map((u) => (u.id === form.id ? data.user : u)));
        setSuccess('Пользователь обновлён');
      } else {
        if (!form.password) {
          setErrors(['Пароль обязателен при создании.']);
          setSaving(false);
          return;
        }
        payload.password = form.password;
        const { data } = await client.post('/manage/users', payload);
        setUsers((prev) => [data.user, ...prev]);
        setSuccess('Пользователь создан');
      }
      closeForm();
    } catch (err) {
      setErrors(extractErrorMessages(err));
    } finally {
      setSaving(false);
    }
  };

  const handleDelete = async (user) => {
    if (user.id === currentUserId) {
      setErrors(['Нельзя удалить самого себя.']);
      return;
    }
    if (!window.confirm(`Удалить пользователя ${user.email}?`)) return;
    try {
      await client.delete(`/manage/users/${user.id}`);
      setUsers((prev) => prev.filter((u) => u.id !== user.id));
      setSuccess('Пользователь удалён');
    } catch (err) {
      setErrors(extractErrorMessages(err));
    }
  };

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div className="absolute inset-0 bg-slate-950/85 backdrop-blur-md" onClick={onClose} />

      <div className="relative z-10 flex w-full max-w-3xl flex-col overflow-hidden rounded-3xl border border-slate-800 bg-slate-900 shadow-2xl">
        <div className="flex items-center justify-between border-b border-slate-800 px-6 py-4">
          <div className="flex items-center gap-3">
            <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-fuchsia-500 to-violet-600 shadow-lg shadow-fuchsia-500/30">
              <Users size={18} className="text-white" />
            </div>
            <div>
              <h2 className="text-base font-bold text-white">Управление пользователями</h2>
              <p className="text-[11px] text-slate-500">Создание, редактирование и роли</p>
            </div>
          </div>
          <div className="flex items-center gap-2">
            <button type="button" onClick={openCreate} className="btn-primary !px-3.5 !py-2 !text-xs">
              <Plus size={14} />
              Создать
            </button>
            <button
              type="button"
              onClick={onClose}
              className="rounded-lg p-2 text-slate-500 transition-colors hover:bg-slate-800 hover:text-white"
            >
              <X size={18} />
            </button>
          </div>
        </div>

        {success && (
          <div className="border-b border-emerald-500/30 bg-emerald-500/5 px-6 py-2.5 text-xs font-semibold text-emerald-300">
            <CheckCircle2 size={13} className="mr-1.5 inline" />
            {success}
          </div>
        )}

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

        <form onSubmit={submitSearch} className="border-b border-slate-800 bg-slate-950/40 px-6 py-3">
          <div className="relative">
            <Search size={14} className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500" />
            <input
              type="text"
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              placeholder="Поиск по имени или email…"
              className="w-full rounded-xl border border-slate-800 bg-slate-950/60 py-2 pl-9 pr-24 text-sm text-slate-200 outline-none transition-colors focus:border-indigo-500/60"
            />
            <button type="submit" className="absolute right-1.5 top-1/2 -translate-y-1/2 rounded-lg bg-slate-800/60 px-3 py-1 text-[11px] font-medium text-slate-300 hover:bg-slate-800">
              Найти
            </button>
          </div>
        </form>

        <div className="max-h-[60vh] overflow-y-auto px-6 py-5">
          {loading && users.length === 0 ? (
            <div className="flex flex-col items-center gap-3 py-12 text-slate-500">
              <Loader2 size={24} className="animate-spin" />
              <span className="text-xs">Загрузка…</span>
            </div>
          ) : users.length === 0 ? (
            <div className="flex flex-col items-center gap-3 py-12 text-slate-500">
              <Users size={32} className="opacity-40" />
              <span className="text-sm">Пользователей не найдено</span>
            </div>
          ) : (
            <div className="space-y-2">
              {users.map((u) => (
                <div
                  key={u.id}
                  className="group flex items-center justify-between gap-3 rounded-xl border border-slate-800 bg-slate-950/40 px-4 py-3 transition-colors hover:border-slate-700"
                >
                  <div className="min-w-0 flex-1">
                    <p className="truncate text-sm font-semibold text-white">
                      {u.name}
                      {u.id === currentUserId && (
                        <span className="ml-2 rounded-md bg-indigo-500/10 px-1.5 py-0.5 text-[10px] font-semibold text-indigo-300">
                          это вы
                        </span>
                      )}
                    </p>
                    <p className="truncate text-[11px] text-slate-500">{u.email}</p>
                  </div>

                  <div className="flex flex-wrap items-center gap-1">
                    {(u.roles || []).map((slug) => {
                      const meta = ROLE_LABELS[slug] || { label: slug, chip: 'bg-slate-800 text-slate-300' };
                      return (
                        <span
                          key={slug}
                          className={`rounded-md px-1.5 py-0.5 text-[10px] font-semibold ring-1 ${meta.chip}`}
                        >
                          {meta.label}
                        </span>
                      );
                    })}
                  </div>

                  <div className="flex shrink-0 items-center gap-1.5">
                    <button
                      type="button"
                      onClick={() => openEdit(u)}
                      className="rounded-lg p-1.5 text-slate-500 transition-colors hover:bg-slate-800 hover:text-indigo-300"
                      title="Редактировать"
                    >
                      <Pencil size={13} />
                    </button>
                    <button
                      type="button"
                      onClick={() => handleDelete(u)}
                      disabled={u.id === currentUserId}
                      className="rounded-lg p-1.5 text-slate-500 transition-colors hover:bg-slate-800 hover:text-rose-400 disabled:cursor-not-allowed disabled:opacity-30"
                      title={u.id === currentUserId ? 'Нельзя удалить себя' : 'Удалить'}
                    >
                      <Trash2 size={13} />
                    </button>
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>

        {showForm && (
          <div className="border-t border-slate-800 bg-slate-950/60 px-6 py-5">
            <div className="mb-4 flex items-center justify-between">
              <h3 className="text-sm font-bold text-white">
                {form.id ? `Редактирование: ${form.email}` : 'Новый пользователь'}
              </h3>
              <button
                type="button"
                onClick={closeForm}
                className="rounded-lg p-1.5 text-slate-500 hover:bg-slate-800 hover:text-white"
              >
                <X size={14} />
              </button>
            </div>

            <form onSubmit={handleSubmit} className="space-y-3">
              <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <label className="block">
                  <span className="mb-1 block text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                    Имя
                  </span>
                  <input
                    type="text"
                    value={form.name}
                    onChange={(e) => setForm({ ...form, name: e.target.value })}
                    required
                    maxLength={255}
                    className="w-full rounded-xl border border-slate-800 bg-slate-950/60 px-3 py-2 text-sm text-slate-200 outline-none focus:border-indigo-500/60"
                  />
                </label>
                <label className="block">
                  <span className="mb-1 block text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                    Email
                  </span>
                  <input
                    type="email"
                    value={form.email}
                    onChange={(e) => setForm({ ...form, email: e.target.value })}
                    required
                    maxLength={255}
                    className="w-full rounded-xl border border-slate-800 bg-slate-950/60 px-3 py-2 text-sm text-slate-200 outline-none focus:border-indigo-500/60"
                  />
                </label>
              </div>

              <label className="block">
                <span className="mb-1 block text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                  Пароль {form.id && <span className="text-slate-500">(оставьте пустым, чтобы не менять)</span>}
                </span>
                <input
                  type="password"
                  value={form.password}
                  onChange={(e) => setForm({ ...form, password: e.target.value })}
                  minLength={8}
                  autoComplete="new-password"
                  className="w-full rounded-xl border border-slate-800 bg-slate-950/60 px-3 py-2 text-sm text-slate-200 outline-none focus:border-indigo-500/60"
                />
              </label>

              <div>
                <span className="mb-2 block text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                  Роли
                </span>
                <div className="flex flex-wrap gap-2">
                  {roles.map((r) => {
                    const active = form.roles.includes(r.slug);
                    const meta = ROLE_LABELS[r.slug];
                    const Icon = meta?.icon || UserIcon;
                    return (
                      <button
                        type="button"
                        key={r.slug}
                        onClick={() => toggleRole(r.slug)}
                        className={`inline-flex items-center gap-2 rounded-xl border px-3 py-2 text-xs font-semibold transition-all ${
                          active
                            ? 'border-indigo-500/60 bg-indigo-500/10 text-white ring-1 ring-indigo-500/40'
                            : 'border-slate-800 bg-slate-900/40 text-slate-400 hover:border-slate-700 hover:text-slate-200'
                        }`}
                      >
                        <Icon size={13} className={active ? meta?.color : ''} />
                        {r.name}
                      </button>
                    );
                  })}
                </div>
              </div>

              <div className="flex justify-end gap-2 pt-1">
                <button type="button" onClick={closeForm} className="btn-ghost !py-2 !text-xs">
                  Отмена
                </button>
                <button type="submit" disabled={saving} className="btn-primary !py-2 !text-xs">
                  {saving ? <Loader2 size={13} className="animate-spin" /> : <CheckCircle2 size={13} />}
                  {form.id ? 'Сохранить' : 'Создать'}
                </button>
              </div>
            </form>
          </div>
        )}
      </div>
    </div>
  );
}