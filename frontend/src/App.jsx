import { useEffect, useMemo, useState } from 'react';
import {
  Cpu, Monitor, CircuitBoard, MemoryStick, Fan, HardDrive, Box, Zap,
  Sparkles, ShoppingCart, User, LogOut, Loader2, CheckCircle2,
  AlertTriangle, XCircle, Wand2, Save, Bookmark, Shield, Store, Users,
  Mail, Send, ShoppingBag,
} from 'lucide-react';

import client, {
  extractErrorMessages,
  TOKEN_KEY,
  USER_KEY,
} from './api/client';
import { formatPrice, toArray } from './utils/format';

import SlotCard from './components/SlotCard';
import WattageBar from './components/WattageBar';
import OrderModal from './components/OrderModal';
import LoginModal from './components/LoginModal';
import RegisterModal from './components/RegisterModal';
import BuildWizardModal from './components/BuildWizardModal';
import BudgetSlider from './components/BudgetSlider';
import SaveBuildModal from './components/SaveBuildModal';
import SavedBuildsModal from './components/SavedBuildsModal';
import OrdersManageModal from './components/OrdersManageModal';
import UserManagementModal from './components/UserManagementModal';

const ALL_SLOTS = [
  { key: 'cpu', label: 'Процессор', icon: Cpu },
  { key: 'gpu', label: 'Видеокарта', icon: Monitor },
  { key: 'motherboard', label: 'Материнская плата', icon: CircuitBoard },
  { key: 'ram', label: 'Оперативная память', icon: MemoryStick },
  { key: 'cooling', label: 'Охлаждение', icon: Fan },
  { key: 'storage', label: 'Накопители', icon: HardDrive },
  { key: 'case', label: 'Корпус', icon: Box },
  { key: 'psu', label: 'Блок питания', icon: Zap },
];

const WIZARD_SEEN_KEY = 'pc_configurator_wizard_seen';
const PROFILE_KEY     = 'pc_configurator_profile';

const PROFILE_FIELDS = [
  'target_budget', 'use_case', 'game_type',
  'monitor_resolution', 'monitor_refresh_rate',
  'cpu_vendor', 'gpu_vendor',
  'ram_size', 'storage_type', 'storage_size',
  'color', 'cooling', 'case',
];

const DEFAULT_PROFILE = { target_budget: 100000, use_case: 'gaming' };

function loadProfile() {
  try {
    const raw = localStorage.getItem(PROFILE_KEY);
    if (!raw) return DEFAULT_PROFILE;
    const parsed = JSON.parse(raw);
    delete parsed.budget;
    delete parsed.strict_budget;
    return { ...DEFAULT_PROFILE, ...parsed };
  } catch {
    return DEFAULT_PROFILE;
  }
}

function saveProfile(p) {
  localStorage.setItem(PROFILE_KEY, JSON.stringify(p));
}

function pickProfileFields(source) {
  const out = {};
  PROFILE_FIELDS.forEach((k) => {
    if (source[k] !== undefined && source[k] !== null && source[k] !== '') {
      out[k] = source[k];
    }
  });
  return out;
}

const ROLE_BADGE = {
  buyer:  { label: 'ПОКУПАТЕЛЬ', cls: 'bg-emerald-500/15 text-emerald-300 ring-emerald-500/30', icon: User },
  seller: { label: 'ПРОДАВЕЦ',   cls: 'bg-amber-500/15 text-amber-300 ring-amber-500/30',       icon: Store },
  admin:  { label: 'ADMIN',      cls: 'bg-fuchsia-500/15 text-fuchsia-300 ring-fuchsia-500/30', icon: Shield },
};

export default function App() {
  const [components, setComponents] = useState({});
  const [validation, setValidation] = useState(null);
  const [totalPrice, setTotalPrice] = useState(0);
  const [estimatedWattage, setEstimatedWattage] = useState(0);
  const [recommendedPsu, setRecommendedPsu] = useState(0);
  const [budgetMeta, setBudgetMeta] = useState(null);

  const [autoLoading, setAutoLoading] = useState(false);
  const [globalError, setGlobalError] = useState('');
  const [flashMessage, setFlashMessage] = useState('');

  const [profile, setProfile] = useState(() => loadProfile());

  const [user, setUser] = useState(() => {
    try {
      const raw = localStorage.getItem(USER_KEY);
      return raw ? JSON.parse(raw) : null;
    } catch {
      return null;
    }
  });
  const [isAuthenticated, setIsAuthenticated] = useState(
    () => Boolean(localStorage.getItem(TOKEN_KEY))
  );

  const rawRoles = Array.isArray(user?.roles) ? user.roles : [];
  const roles = rawRoles.map(r => r === 'user' ? 'buyer' : r); // Маппинг старых юзеров
  const isAdmin  = roles.includes('admin');
  const isSeller = roles.includes('seller') || isAdmin;
  const isEmailVerified = Boolean(user?.email_verified_at);

  const [showOrderModal, setShowOrderModal] = useState(false);
  const [showLoginModal, setShowLoginModal] = useState(false);
  const [showRegisterModal, setShowRegisterModal] = useState(false);
  const [showWizardModal, setShowWizardModal] = useState(false);
  const [showSaveModal, setShowSaveModal] = useState(false);
  const [showBuildsModal, setShowBuildsModal] = useState(false);
  const [buildsTab, setBuildsTab] = useState('drafts');
  const [showOrdersManageModal, setShowOrdersManageModal] = useState(false);
  const [showUsersModal, setShowUsersModal] = useState(false);

  useEffect(() => {
    const handler = () => {
      setIsAuthenticated(false);
      setUser(null);
    };
    window.addEventListener('pc-configurator:unauthorized', handler);
    return () => window.removeEventListener('pc-configurator:unauthorized', handler);
  }, []);

  useEffect(() => {
    const seen = localStorage.getItem(WIZARD_SEEN_KEY);
    if (!seen) {
      const t = setTimeout(() => setShowWizardModal(true), 700);
      return () => clearTimeout(t);
    }
  }, []);

  useEffect(() => {
    if (!flashMessage) return;
    const t = setTimeout(() => setFlashMessage(''), 4000);
    return () => clearTimeout(t);
  }, [flashMessage]);

  useEffect(() => {
    if (!isAuthenticated) return;
    const params = new URLSearchParams(window.location.search);
    if (params.get('verified') !== '1') return;

    client.get('/auth/me')
      .then(({ data }) => {
        setUser(data.user);
        localStorage.setItem(USER_KEY, JSON.stringify(data.user));
        setFlashMessage('Почта подтверждена. Полный доступ открыт.');
      })
      .catch(() => {});

    window.history.replaceState({}, '', window.location.pathname);
  }, [isAuthenticated]);

  const closeWizard = () => {
    localStorage.setItem(WIZARD_SEEN_KEY, '1');
    setShowWizardModal(false);
  };

  const applyBuildResult = (data) => {
    const comps = data.components || {};
    setComponents(comps);
    setValidation(data.validation || null);
    setTotalPrice(Number(data.total_price) || 0);
    setBudgetMeta(data.budget || null);

    const ew = Number(data?.validation?.estimated_wattage) || 0;
    setEstimatedWattage(ew);

    const psu = toArray(comps.psu)[0];
    const psuPower = Number(psu?.psuSpec?.power ?? psu?.psu_spec?.power ?? 0);
    const rec = Number(data?.validation?.recommended_psu_wattage) || psuPower || 0;
    setRecommendedPsu(rec || psuPower);
  };

  const runAutoBuild = async (nextProfile) => {
    setAutoLoading(true);
    setGlobalError('');
    try {
      const payload = pickProfileFields(nextProfile);
      const { data } = await client.post('/builder/auto', payload);
      applyBuildResult(data);
    } catch (err) {
      setGlobalError(extractErrorMessages(err).join(' · '));
    } finally {
      setAutoLoading(false);
    }
  };

  const revalidate = async (ids) => {
    if (!ids || ids.length === 0) {
      setValidation(null);
      setTotalPrice(0);
      setEstimatedWattage(0);
      setRecommendedPsu(0);
      setBudgetMeta(null);
      return;
    }
    try {
      const { data } = await client.post('/builder/validate', { product_ids: ids });
      setValidation(data);
      setTotalPrice(Number(data.total_price) || 0);
      setEstimatedWattage(Number(data.estimated_wattage) || 0);
      setRecommendedPsu((prev) => prev || Number(data.estimated_wattage) || 0);
    } catch (err) {
      setGlobalError(extractErrorMessages(err).join(' · '));
    }
  };

  const isOffice = profile.use_case === 'office';

  const visibleSlots = useMemo(
    () => (isOffice ? ALL_SLOTS.filter((s) => s.key !== 'gpu') : ALL_SLOTS),
    [isOffice]
  );

  const allProductIds = useMemo(() => {
    const ids = [];
    visibleSlots.forEach((s) =>
      toArray(components[s.key]).forEach((p) => p?.id && ids.push(p.id))
    );
    return ids;
  }, [components, visibleSlots]);

  const handleBudgetChange = (v) => {
    const next = { ...profile, target_budget: v };
    setProfile(next);
    saveProfile(next);
  };

  const handleBudgetCommit = (v) => {
    const next = { ...profile, target_budget: v };
    setProfile(next);
    saveProfile(next);
    runAutoBuild(next);
  };

  const handleWizardComplete = (data) => {
    applyBuildResult(data);

    const cfg = data?.config_applied || data?.config || {};
    const next = { ...profile, ...pickProfileFields(cfg) };
    if (data?.budget?.target) {
      next.target_budget = data.budget.target;
    }
    setProfile(next);
    saveProfile(next);
  };

  const handleClearBuild = () => {
    setComponents({});
    setValidation(null);
    setTotalPrice(0);
    setEstimatedWattage(0);
    setRecommendedPsu(0);
    setBudgetMeta(null);
    setGlobalError('');
  };

  const handleRemoveSlot = (slotKey) => {
    setComponents((prev) => {
      const next = { ...prev };
      delete next[slotKey];
      return next;
    });
    const newIds = [];
    visibleSlots.forEach((s) => {
      if (s.key === slotKey) return;
      toArray(components[s.key]).forEach((p) => p?.id && newIds.push(p.id));
    });
    revalidate(newIds);
  };

  const ensureVerified = () => {
    if (!isAuthenticated) {
      setShowLoginModal(true);
      return false;
    }
    if (!isEmailVerified) {
      setFlashMessage('Сначала подтвердите почту — проверьте входящие или запросите письмо заново.');
      return false;
    }
    return true;
  };

  const handleOrderClick = () => {
    if (!ensureVerified()) return;
    setShowOrderModal(true);
  };

  const handleSaveClick = () => {
    if (!ensureVerified()) return;
    if (!hasBuild) return;
    setShowSaveModal(true);
  };

  const handleResendEmail = async () => {
    try {
      const { data } = await client.post('/auth/email/resend');
      setFlashMessage(data.message || 'Письмо отправлено.');
    } catch (err) {
      setGlobalError(extractErrorMessages(err).join(' · '));
    }
  };

  const handleLogout = async () => {
    try { await client.post('/auth/logout'); } catch { /* ignore */ }
    localStorage.removeItem(TOKEN_KEY);
    localStorage.removeItem(USER_KEY);
    setIsAuthenticated(false);
    setUser(null);
  };

  const handleAuthSuccess = (u) => {
    setUser(u);
    setIsAuthenticated(true);
  };

  const handleRegisterSuccess = (u) => {
    setUser(u);
    setIsAuthenticated(true);
    setFlashMessage('Регистрация успешна. Проверьте почту — мы отправили письмо для подтверждения.');
  };

  const handleLoadBuild = (build) => {
    const snapshot = build?.snapshot;
    if (!snapshot || typeof snapshot !== 'object') return;

    if (snapshot.components) setComponents(snapshot.components);
    if (snapshot.total_price !== undefined) setTotalPrice(Number(snapshot.total_price) || 0);
    if (snapshot.validation) setValidation(snapshot.validation);
    if (snapshot.budget) setBudgetMeta(snapshot.budget);

    const ew = Number(snapshot?.validation?.estimated_wattage) || 0;
    setEstimatedWattage(ew);

    const psu = toArray(snapshot.components?.psu)[0];
    const psuPower = Number(psu?.psuSpec?.power ?? psu?.psu_spec?.power ?? 0);
    const rec = Number(snapshot?.validation?.recommended_psu_wattage) || psuPower || 0;
    setRecommendedPsu(rec || psuPower);

    setFlashMessage(`Черновик «${build.name}» загружен`);
  };

  const hasBuild = allProductIds.length > 0;
  const isValid = Boolean(validation?.is_valid);
  const errors = validation?.errors || [];
  const warnings = validation?.warnings || [];
  const overBudget = Boolean(budgetMeta?.over_budget);
  const isInfinityBudget = Number(profile.target_budget) >= 200000;

  const currentSnapshot = useMemo(() => ({
    components,
    total_price: totalPrice,
    validation,
    budget: budgetMeta,
  }), [components, totalPrice, validation, budgetMeta]);

  const primaryRole = isAdmin ? 'admin' : (isSeller ? 'seller' : 'buyer');
  const roleBadge = ROLE_BADGE[primaryRole] || ROLE_BADGE.buyer;
  const RoleIcon = roleBadge.icon;

  return (
    <div className="min-h-screen">
      <header className="sticky top-0 z-30 border-b border-slate-800/80 bg-slate-950/80 backdrop-blur-xl">
        <div className="mx-auto flex max-w-[1400px] items-center justify-between px-4 py-3 sm:px-6">
          <div className="flex items-center gap-3">
            <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-500 via-violet-500 to-fuchsia-500 shadow-lg shadow-indigo-500/30">
              <Cpu size={20} className="text-white" />
            </div>
            <div>
              <h1 className="text-sm font-extrabold tracking-tight text-white sm:text-base">
                PC Configurator
              </h1>
              <p className="hidden text-[11px] text-slate-500 sm:block">
                Профессиональный подбор комплектующих
              </p>
            </div>
          </div>

          <div className="flex items-center gap-2">
            <button
              type="button"
              onClick={() => setShowWizardModal(true)}
              className="hidden items-center gap-2 rounded-xl bg-gradient-to-r from-indigo-500 to-violet-600 px-4 py-2.5 text-xs font-semibold text-white shadow-lg shadow-indigo-500/25 transition-all hover:from-indigo-400 hover:to-violet-500 sm:inline-flex"
            >
              <Wand2 size={14} />
              Подобрать ПК
            </button>

            {isAuthenticated && isEmailVerified && (
              <>
                <button
                  type="button"
                  onClick={() => { setBuildsTab('drafts'); setShowBuildsModal(true); }}
                  className="hidden items-center gap-2 rounded-xl border border-slate-800 bg-slate-900/60 px-3 py-2 text-xs font-medium text-slate-300 transition-all hover:border-indigo-500/40 hover:text-white md:inline-flex"
                >
                  <Bookmark size={14} />
                  Черновики
                </button>
                
                <button
                  type="button"
                  onClick={() => { setBuildsTab('orders'); setShowBuildsModal(true); }}
                  className="hidden items-center gap-2 rounded-xl border border-slate-800 bg-slate-900/60 px-3 py-2 text-xs font-medium text-slate-300 transition-all hover:border-indigo-500/40 hover:text-white md:inline-flex"
                >
                  <ShoppingBag size={14} />
                  Мои заказы
                </button>
              </>
            )}

            {isSeller && isEmailVerified && (
              <button
                type="button"
                onClick={() => setShowOrdersManageModal(true)}
                className="hidden items-center gap-2 rounded-xl border border-amber-500/30 bg-amber-500/5 px-3 py-2 text-xs font-semibold text-amber-300 transition-all hover:border-amber-500/50 hover:bg-amber-500/10 md:inline-flex"
              >
                <Store size={14} />
                Все заказы
              </button>
            )}

            {isAdmin && isEmailVerified && (
              <button
                type="button"
                onClick={() => setShowUsersModal(true)}
                className="hidden items-center gap-2 rounded-xl border border-fuchsia-500/30 bg-fuchsia-500/5 px-3 py-2 text-xs font-semibold text-fuchsia-300 transition-all hover:border-fuchsia-500/50 hover:bg-fuchsia-500/10 md:inline-flex"
              >
                <Users size={14} />
                Пользователи
              </button>
            )}

            {isAuthenticated ? (
              <>
                <div className="hidden items-center gap-2 rounded-xl border border-slate-800 bg-slate-900/60 px-3 py-2 sm:flex">
                  <RoleIcon size={14} className={roleBadge.cls.split(' ')[1]} />
                  <span className="text-xs font-medium text-slate-200">
                    {user?.name || user?.email || 'Пользователь'}
                  </span>
                  <span className={`rounded-md px-1.5 py-0.5 text-[10px] font-semibold ring-1 ${roleBadge.cls}`}>
                    {roleBadge.label}
                  </span>
                </div>
                <button type="button" onClick={handleLogout} className="btn-ghost !px-3 !py-2" title="Выйти">
                  <LogOut size={15} />
                </button>
              </>
            ) : (
              <>
                <button
                  type="button"
                  onClick={() => setShowRegisterModal(true)}
                  className="btn-ghost !px-3 !py-2 !text-xs"
                >
                  Регистрация
                </button>
                <button
                  type="button"
                  onClick={() => setShowLoginModal(true)}
                  className="btn-primary !px-4 !py-2 !text-xs"
                >
                  <User size={13} />
                  Войти
                </button>
              </>
            )}
          </div>
        </div>
      </header>

      <main className="mx-auto max-w-[1400px] px-4 py-6 sm:px-6">
        {flashMessage && (
          <div className="mb-4 flex items-center gap-2 rounded-xl border border-emerald-500/30 bg-emerald-500/5 px-4 py-3 text-sm text-emerald-200">
            <CheckCircle2 size={16} className="shrink-0 text-emerald-400" />
            <span className="flex-1">{flashMessage}</span>
          </div>
        )}

        {globalError && (
          <div className="mb-4 flex items-start gap-2 rounded-xl border border-rose-500/30 bg-rose-500/5 px-4 py-3 text-sm text-rose-200">
            <XCircle size={16} className="mt-0.5 shrink-0 text-rose-400" />
            <span>{globalError}</span>
          </div>
        )}

        {isAuthenticated && !isEmailVerified && (
          <div className="mb-4 flex flex-col gap-3 rounded-xl border border-amber-500/40 bg-amber-500/5 px-4 py-3 sm:flex-row sm:items-center">
            <div className="flex items-start gap-2 flex-1">
              <Mail size={18} className="mt-0.5 shrink-0 text-amber-400" />
              <div className="text-sm text-amber-100">
                <p className="font-semibold">Подтвердите почту</p>
                <p className="mt-0.5 text-xs text-amber-200/80">
                  Мы отправили письмо на <span className="font-medium">{user?.email}</span>.
                  Без подтверждения нельзя оформлять заказы и сохранять черновики.
                </p>
              </div>
            </div>
            <button
              type="button"
              onClick={handleResendEmail}
              className="inline-flex items-center justify-center gap-2 rounded-lg border border-amber-500/40 bg-amber-500/10 px-3 py-2 text-xs font-semibold text-amber-200 transition-colors hover:bg-amber-500/20"
            >
              <Send size={13} />
              Отправить ещё раз
            </button>
          </div>
        )}

        {!hasBuild && !autoLoading && (
          <section className="mb-6 overflow-hidden rounded-3xl border border-slate-800 bg-gradient-to-br from-indigo-500/10 via-violet-500/5 to-transparent p-6 sm:p-10">
            <div className="mx-auto max-w-2xl text-center">
              <div className="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-500 via-violet-500 to-fuchsia-500 shadow-xl shadow-indigo-500/40">
                <Sparkles size={24} className="text-white" />
              </div>
              <h2 className="text-2xl font-extrabold tracking-tight text-white sm:text-3xl">
                Не знаете, с чего начать?
              </h2>
              <p className="mx-auto mt-2 max-w-lg text-sm text-slate-400">
                Ответьте на несколько простых вопросов — мы соберём идеальную
                конфигурацию под ваш бюджет, сценарий и монитор.
              </p>
              <button
                type="button"
                onClick={() => setShowWizardModal(true)}
                className="btn-primary mt-6 !px-6 !py-3.5 !text-base"
              >
                <Wand2 size={18} />
                Подобрать ПК через опросник
              </button>
            </div>
          </section>
        )}

        <section className="mb-6">
          <div className="mb-3 flex items-center justify-between">
            <h2 className="text-sm font-bold uppercase tracking-wider text-slate-300">
              Бюджет сборки
            </h2>
            {hasBuild && (
              <button
                type="button"
                onClick={handleClearBuild}
                className="text-xs font-medium text-slate-500 transition-colors hover:text-rose-400"
              >
                Очистить сборку
              </button>
            )}
          </div>

          <BudgetSlider
            value={profile.target_budget ?? 100000}
            onChange={handleBudgetChange}
            disabled={autoLoading}
          />

          <div className="mt-3 flex items-center gap-2">
            <button
              type="button"
              onClick={() => handleBudgetCommit(profile.target_budget ?? 100000)}
              disabled={autoLoading}
              className="btn-primary !py-2.5 !text-sm"
            >
              {autoLoading ? <Loader2 size={15} className="animate-spin" /> : <Sparkles size={15} />}
              Пересобрать под этот бюджет
            </button>
            {isInfinityBudget && (
              <span className="text-[11px] text-fuchsia-300">Без лимита — соберём топ.</span>
            )}
          </div>
        </section>

        <div className="grid grid-cols-1 gap-6 lg:grid-cols-[1fr_380px]">
          <section className="space-y-3">
            <div className="mb-1 flex items-center justify-between">
              <h2 className="text-sm font-bold uppercase tracking-wider text-slate-300">
                Комплектующие
              </h2>
              <span className="text-xs text-slate-500">
                {allProductIds.length} / {visibleSlots.length} слотов заполнено
              </span>
            </div>

            {autoLoading && !hasBuild ? (
              <div className="space-y-3">
                {visibleSlots.map((s) => (
                  <div key={s.key} className="h-[88px] animate-pulse rounded-2xl border border-slate-800 bg-slate-900/40" />
                ))}
              </div>
            ) : (
              visibleSlots.map((slot) => (
                <div key={slot.key} className="relative group">
                  <SlotCard
                    slot={slot}
                    products={components[slot.key]}
                    onEmptyClick={() => setShowWizardModal(true)}
                  />
                  {toArray(components[slot.key]).length > 0 && (
                    <button
                      type="button"
                      onClick={() => handleRemoveSlot(slot.key)}
                      className="absolute right-3 top-3 rounded-md p-1 text-slate-600 opacity-0 transition-all hover:bg-slate-800 hover:text-rose-400 group-hover:opacity-100"
                      title="Убрать"
                    >
                      <XCircle size={14} />
                    </button>
                  )}
                </div>
              ))
            )}
          </section>

          <aside className="lg:sticky lg:top-24 lg:h-fit">
            <div className="space-y-4 rounded-2xl border border-slate-800 bg-slate-900/50 p-5 backdrop-blur-xl">
              <div>
                <p className="text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                  Стоимость сборки
                </p>
                <p className="mt-1 text-3xl font-extrabold tracking-tight text-white">
                  {formatPrice(totalPrice)}
                </p>
                {budgetMeta && !isInfinityBudget && (
                  <p className="mt-1 text-[11px] text-slate-500">
                    Целевой бюджет:{' '}
                    <span className="text-slate-300">{formatPrice(budgetMeta.target)}</span>
                    {overBudget && (
                      <span className="ml-2 rounded-md bg-amber-500/10 px-1.5 py-0.5 text-[10px] font-semibold text-amber-300">
                        +{Math.round(((totalPrice / budgetMeta.target) - 1) * 100)}%
                      </span>
                    )}
                  </p>
                )}
              </div>

              <WattageBar
                estimated={estimatedWattage}
                recommended={recommendedPsu}
                psu={Number(
                  toArray(components.psu)[0]?.psuSpec?.power ??
                    toArray(components.psu)[0]?.psu_spec?.power ?? 0
                )}
              />

              <div>
                {!hasBuild ? (
                  <div className="flex items-center gap-2 rounded-xl border border-slate-800 bg-slate-950/50 px-3 py-2.5 text-xs text-slate-400">
                    <AlertTriangle size={14} className="text-slate-500" />
                    Запустите подбор для начала
                  </div>
                ) : isValid ? (
                  <div className="flex items-center gap-2 rounded-xl border border-emerald-500/30 bg-emerald-500/5 px-3 py-2.5 text-xs font-semibold text-emerald-300">
                    <CheckCircle2 size={14} />
                    Сборка совместима
                  </div>
                ) : (
                  <div className="rounded-xl border border-rose-500/30 bg-rose-500/5 p-3">
                    <div className="mb-2 flex items-center gap-2 text-xs font-semibold text-rose-300">
                      <XCircle size={14} />
                      Обнаружены несовместимости
                    </div>
                    <ul className="ml-4 list-disc space-y-1 text-[11px] leading-relaxed text-rose-200/90">
                      {errors.map((err, i) => (<li key={i}>{err}</li>))}
                    </ul>
                  </div>
                )}

                {warnings.length > 0 && (
                  <div className="mt-2 rounded-xl border border-amber-500/30 bg-amber-500/5 p-3">
                    <div className="mb-1.5 flex items-center gap-2 text-xs font-semibold text-amber-300">
                      <AlertTriangle size={13} />
                      Предупреждения
                    </div>
                    <ul className="ml-4 list-disc space-y-0.5 text-[11px] leading-relaxed text-amber-200/90">
                      {warnings.map((w, i) => (<li key={i}>{w}</li>))}
                    </ul>
                  </div>
                )}
              </div>

              <div className="grid grid-cols-2 gap-2">
                <button
                  type="button"
                  onClick={handleSaveClick}
                  disabled={!hasBuild || autoLoading || (isAuthenticated && !isEmailVerified)}
                  className="btn-ghost !py-3 !text-sm disabled:opacity-40"
                >
                  <Save size={15} />
                  В черновик
                </button>
                <button
                  type="button"
                  onClick={handleOrderClick}
                  disabled={!hasBuild || (!isValid && hasBuild) || (isAuthenticated && !isEmailVerified)}
                  className="btn-primary !py-3 text-sm"
                >
                  <ShoppingCart size={15} />
                  Заказать
                </button>
              </div>

              {!isAuthenticated && (
                <p className="text-center text-[11px] text-slate-500">
                  Войдите или зарегистрируйтесь, чтобы сохранять черновики и оформлять заказы
                </p>
              )}
            </div>
          </aside>
        </div>
      </main>

      <LoginModal
        isOpen={showLoginModal}
        onClose={() => setShowLoginModal(false)}
        onSuccess={handleAuthSuccess}
        onSwitchToRegister={() => {
          setShowLoginModal(false);
          setShowRegisterModal(true);
        }}
      />

      <RegisterModal
        isOpen={showRegisterModal}
        onClose={() => setShowRegisterModal(false)}
        onSuccess={handleRegisterSuccess}
        onSwitchToLogin={() => {
          setShowRegisterModal(false);
          setShowLoginModal(true);
        }}
      />

      <OrderModal
        isOpen={showOrderModal}
        onClose={() => setShowOrderModal(false)}
        components={components}
        totalPrice={totalPrice}
        isAuthenticated={isAuthenticated}
        onRequireLogin={() => {
          setShowOrderModal(false);
          setShowLoginModal(true);
        }}
      />

      <BuildWizardModal
        isOpen={showWizardModal}
        onClose={closeWizard}
        onComplete={handleWizardComplete}
        initialTargetBudget={profile.target_budget ?? 100000}
      />

      <SaveBuildModal
        isOpen={showSaveModal}
        onClose={() => setShowSaveModal(false)}
        snapshot={currentSnapshot}
        defaultName={`Черновик за ${formatPrice(totalPrice)}`}
        onSaved={() => setFlashMessage('Черновик сохранён')}
      />

      <SavedBuildsModal
        isOpen={showBuildsModal}
        onClose={() => setShowBuildsModal(false)}
        onLoadBuild={handleLoadBuild}
        isAdmin={isAdmin}
        initialTab={buildsTab}
      />

      <OrdersManageModal
        isOpen={showOrdersManageModal}
        onClose={() => setShowOrdersManageModal(false)}
      />

      <UserManagementModal
        isOpen={showUsersModal}
        onClose={() => setShowUsersModal(false)}
        currentUserId={user?.id}
      />
    </div>
  );
}