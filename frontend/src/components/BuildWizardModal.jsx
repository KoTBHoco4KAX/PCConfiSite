import { useMemo, useState } from 'react';
import {
  X, ChevronLeft, ChevronRight, Wand2, Wallet, Gamepad2, Target, Monitor,
  Cpu, MemoryStick, Palette, Loader2, Check, Sparkles, AlertTriangle,
  HardDrive, Box,
} from 'lucide-react';
import client, { extractErrorMessages } from '../api/client';
import BudgetSlider from './BudgetSlider';

const STEP_DEFS = [
  { id: 'target_budget', title: 'Какой у вас бюджет?', subtitle: 'Передвиньте ползунок — сборка подстроится', icon: Wallet },
  { id: 'use_case',      title: 'Для чего собираете ПК?', subtitle: 'Выберите основной сценарий', icon: Gamepad2 },

  { id: 'game_type', title: 'В какие игры играете?', subtitle: 'Поможет сбалансировать CPU и GPU', icon: Target,
    skipIf: (a) => a.use_case === 'office' },

  { id: 'monitor_resolution', title: 'Разрешение монитора', subtitle: 'От этого зависит мощность видеокарты', icon: Monitor,
    skipIf: (a) => a.use_case === 'office' },
  { id: 'monitor_refresh_rate', title: 'Частота обновления', subtitle: 'Сколько герц выдаёт ваш монитор', icon: Monitor,
    skipIf: (a) => a.use_case === 'office' },

  { id: 'cpu_vendor', title: 'Процессор — какой вендор?', subtitle: 'AMD, Intel или алгоритм', icon: Cpu },

  { id: 'gpu_vendor', title: 'Видеокарта — какой вендор?', subtitle: 'NVIDIA, AMD или алгоритм', icon: Cpu,
    skipIf: (a) => a.use_case === 'office' },

  { id: 'ram_size',     title: 'Сколько нужно оперативной памяти?', subtitle: '16 ГБ — минимум, 64 ГБ — для тяжёлых задач', icon: MemoryStick,
    skipIf: (a) => a.use_case === 'office' },
  { id: 'storage_type', title: 'Какой тип накопителей?', subtitle: 'Скорость или объём', icon: HardDrive,
    skipIf: (a) => a.use_case === 'office' },
  { id: 'storage_size', title: 'Объём накопителя', subtitle: 'Сколько места под систему и файлы', icon: HardDrive,
    skipIf: (a) => a.use_case === 'office' },
  { id: 'color',        title: 'Цвет корпуса', subtitle: 'Эстетика вашей сборки', icon: Palette,
    skipIf: (a) => a.use_case === 'office' },
  { id: 'cooling',      title: 'Тип охлаждения процессора', subtitle: 'Воздух или жидкость', icon: Palette,
    skipIf: (a) => a.use_case === 'office' },
  { id: 'case',         title: 'Форм-фактор корпуса', subtitle: 'Классика, аквариум или компакт', icon: Box,
    skipIf: (a) => a.use_case === 'office' },
];

const USE_CASE_OPTIONS = [
  { value: 'gaming',    title: '🎮 Гейминг',           subtitle: 'Игры на первом месте' },
  { value: 'streaming', title: '🎙 Стриминг',          subtitle: 'Игры + трансляции' },
  { value: 'work',      title: '💻 Работа (3D / Код)', subtitle: 'Многопоточные задачи' },
  { value: 'office',    title: '📄 Офис',              subtitle: 'Документы, браузер' },
  { value: 'universal', title: '⚖️ Универсальный',     subtitle: 'Всё понемногу' },
];

const GAME_TYPE_OPTIONS = [
  { value: 'esports', title: '🎯 Киберспорт',         subtitle: 'CS2, Dota 2, Valorant' },
  { value: 'aaa',     title: '🌄 AAA-блокбастеры',    subtitle: 'Cyberpunk, RDR2, Alan Wake 2' },
  { value: 'all',     title: '🎲 Играю во всё подряд', subtitle: 'Универсальная сборка' },
];

const RESOLUTION_OPTIONS = [
  { value: '1080p', title: '1080p Full HD', subtitle: 'Классика для киберспорта' },
  { value: '1440p', title: '1440p 2K',      subtitle: 'Золотая середина' },
  { value: '4k',    title: '4K Ultra HD',   subtitle: 'Максимум детализации', requiresBudget: 130000 },
  { value: 'auto',  title: '🤷‍♂️ Авто / Без разницы', subtitle: 'Доверимся алгоритму' },
];

const REFRESH_OPTIONS = [
  { value: '60-75',   title: '60–75 Гц',  subtitle: 'Стандарт' },
  { value: '144-180', title: '144–180 Гц', subtitle: 'Плавно и быстро' },
  { value: '240+',    title: '240+ Гц',    subtitle: 'Для киберспорта' },
  { value: 'auto',    title: '🤷‍♂️ Авто',   subtitle: 'На усмотрение' },
];

const CPU_VENDOR_OPTIONS = [
  { value: 'amd',   title: '🔴 AMD Ryzen',    subtitle: 'Многопоточность и цена' },
  { value: 'intel', title: '🔵 Intel Core',   subtitle: 'Высокие частоты и iGPU' },
  { value: 'auto',  title: '🤷‍♂️ Без разницы', subtitle: 'Что лучше подойдёт' },
];

const GPU_VENDOR_OPTIONS = [
  { value: 'nvidia', title: '🟢 NVIDIA GeForce', subtitle: 'DLSS и RTX' },
  { value: 'amd',    title: '🔴 AMD Radeon',     subtitle: 'Больше VRAM за те же деньги' },
  { value: 'auto',   title: '🤷‍♂️ Без разницы',   subtitle: 'Что лучше подойдёт' },
];

const RAM_OPTIONS = [
  { value: '16',   title: '16 ГБ',    subtitle: 'Игровой минимум' },
  { value: '32',   title: '32 ГБ',    subtitle: 'Комфортно для всего' },
  { value: '64',   title: '64 ГБ',    subtitle: '3D / Видео / Многопоток' },
  { value: 'auto', title: '🤷‍♂️ Авто', subtitle: 'Подберём по сценарию' },
];

const STORAGE_TYPE_OPTIONS = [
  { value: 'nvme',  title: '⚡ Скоростной NVMe',  subtitle: 'Мгновенная загрузка' },
  { value: 'combo', title: '🔀 Комбо NVMe + HDD', subtitle: 'Скорость + объём' },
  { value: 'auto',  title: '🤷‍♂️ Авто',            subtitle: 'Оптимально' },
];

const STORAGE_SIZE_OPTIONS = [
  { value: '512', title: '512 ГБ', subtitle: 'Базовый набор' },
  { value: '1',   title: '1 ТБ',   subtitle: 'Комфортный объём' },
  { value: '2',   title: '2 ТБ',   subtitle: 'Много игр и файлов' },
  { value: '4',   title: '4 ТБ',   subtitle: 'Максимум' },
];

const COLOR_OPTIONS = [
  { value: 'black', title: '⬛ Черный', subtitle: 'Классика' },
  { value: 'white', title: '⬜ Белый',  subtitle: 'Светлая эстетика' },
  { value: 'any',   title: '🎨 Любой',  subtitle: 'Не важно' },
];

const COOLING_OPTIONS = [
  { value: 'air',   title: '🌬 Воздушный кулер', subtitle: 'Надёжно и тихо' },
  { value: 'water', title: '💧 Водянка (СЖО)',   subtitle: 'Максимальная эффективность' },
  { value: 'auto',  title: '🤷‍♂️ На усмотрение', subtitle: 'Подберём по TDP' },
];

const CASE_OPTIONS = [
  { value: 'mid-tower', title: 'Mid-Tower (сетка)',   subtitle: 'Классический продув' },
  { value: 'aquarium',  title: 'Аквариум со стеклом', subtitle: 'Показать сборку' },
  { value: 'mini-itx',  title: 'Компактный Mini-ITX', subtitle: 'Минимум места' },
  { value: 'auto',      title: '🤷‍♂️ Авто',             subtitle: 'На усмотрение' },
];

const DEFAULT_ANSWERS = {
  target_budget: 100000,
  use_case: null,
  game_type: null,
  monitor_resolution: null,
  monitor_refresh_rate: null,
  cpu_vendor: null,
  gpu_vendor: null,
  ram_size: null,
  storage_type: null,
  storage_size: null,
  color: null,
  cooling: null,
  case: null,
};

function OptionCard({ selected, onClick, title, subtitle, disabled }) {
  return (
    <button
      type="button"
      onClick={onClick}
      disabled={disabled}
      className={`group relative flex items-center gap-3 rounded-2xl border p-4 text-left transition-all duration-200 ${
        disabled
          ? 'cursor-not-allowed border-slate-800/40 bg-slate-950/40 opacity-35'
          : selected
          ? 'border-indigo-500/60 bg-gradient-to-br from-indigo-500/15 to-violet-500/5 shadow-lg shadow-indigo-500/20 ring-1 ring-indigo-500/40'
          : 'border-slate-800 bg-slate-900/40 hover:-translate-y-0.5 hover:border-indigo-500/40 hover:bg-slate-900/70'
      }`}
    >
      <div className="min-w-0 flex-1">
        <p className={`text-sm font-semibold ${selected ? 'text-white' : 'text-slate-200'}`}>
          {title}
        </p>
        {subtitle && <p className="mt-0.5 text-xs text-slate-500">{subtitle}</p>}
      </div>
      {selected && (
        <div className="shrink-0 rounded-full bg-gradient-to-br from-indigo-500 to-violet-500 p-1">
          <Check size={12} className="text-white" strokeWidth={3} />
        </div>
      )}
    </button>
  );
}

export default function BuildWizardModal({
  isOpen,
  onClose,
  onComplete,
  initialTargetBudget = 100000,
}) {
  const [answers, setAnswers] = useState(() => ({
    ...DEFAULT_ANSWERS,
    target_budget: initialTargetBudget,
  }));
  const [stepIndex, setStepIndex] = useState(0);
  const [loading, setLoading] = useState(false);
  const [errors, setErrors] = useState([]);

  const visibleSteps = useMemo(
    () => STEP_DEFS.filter((s) => !(s.skipIf && s.skipIf(answers))),
    [answers]
  );

  const safeIndex = Math.min(stepIndex, visibleSteps.length - 1);
  const currentStep = visibleSteps[safeIndex];
  const isFirst = safeIndex === 0;
  const isLast = safeIndex === visibleSteps.length - 1;
  const progress = ((safeIndex + 1) / visibleSteps.length) * 100;

  const update = (patch) => setAnswers((prev) => ({ ...prev, ...patch }));

  const reset = () => {
    setAnswers({ ...DEFAULT_ANSWERS, target_budget: initialTargetBudget });
    setStepIndex(0);
    setErrors([]);
    setLoading(false);
  };

  const handleClose = () => {
    if (loading) return;
    reset();
    onClose?.();
  };

  const canProceed = useMemo(() => {
    if (!currentStep) return false;
    if (currentStep.id === 'target_budget') {
      return Number(answers.target_budget) >= 30000;
    }
    if (currentStep.id === 'cpu_vendor' || currentStep.id === 'gpu_vendor') {
      return true;
    }
    const v = answers[currentStep.id];
    return v !== null && v !== undefined && v !== '';
  }, [currentStep, answers]);

  const goNext = () => {
    if (!canProceed || loading) return;
    if (isLast) return submit();
    setStepIndex((i) => Math.min(i + 1, visibleSteps.length - 1));
  };

  const goBack = () => {
    if (isFirst || loading) return;
    setStepIndex((i) => Math.max(i - 1, 0));
  };

  const buildPayload = () => {
    const payload = { target_budget: Number(answers.target_budget) };
    const put = (key, value) => {
      if (value && value !== 'auto' && value !== 'any') payload[key] = value;
    };

    put('use_case', answers.use_case);
    put('game_type', answers.game_type);
    put('monitor_resolution', answers.monitor_resolution);
    put('monitor_refresh_rate', answers.monitor_refresh_rate);
    put('cpu_vendor', answers.cpu_vendor);
    if (answers.use_case !== 'office') put('gpu_vendor', answers.gpu_vendor);
    put('ram_size', answers.ram_size);
    put('storage_type', answers.storage_type);
    put('storage_size', answers.storage_size);
    if (answers.color !== 'any') put('color', answers.color);
    put('cooling', answers.cooling);
    put('case', answers.case);

    return payload;
  };

  const submit = async () => {
    setLoading(true);
    setErrors([]);
    try {
      const { data } = await client.post('/builder/auto', buildPayload());
      onComplete?.(data);
      reset();
      onClose?.();
    } catch (err) {
      setErrors(extractErrorMessages(err));
    } finally {
      setLoading(false);
    }
  };

  if (!isOpen) return null;

  const renderOptions = () => {
    if (!currentStep) return null;

    switch (currentStep.id) {
      case 'target_budget':
        return (
          <BudgetSlider
            value={answers.target_budget}
            onChange={(v) => update({ target_budget: v })}
          />
        );

      case 'use_case':
        return USE_CASE_OPTIONS.map((opt) => (
          <OptionCard
            key={opt.value}
            selected={answers.use_case === opt.value}
            onClick={() => update({ use_case: opt.value })}
            title={opt.title}
            subtitle={opt.subtitle}
          />
        ));

      case 'game_type':
        return GAME_TYPE_OPTIONS.map((opt) => (
          <OptionCard
            key={opt.value}
            selected={answers.game_type === opt.value}
            onClick={() => update({ game_type: opt.value })}
            title={opt.title}
            subtitle={opt.subtitle}
          />
        ));

      case 'monitor_resolution':
        return RESOLUTION_OPTIONS.map((opt) => {
          const disabled = opt.requiresBudget && answers.target_budget < opt.requiresBudget;
          return (
            <OptionCard
              key={opt.value}
              selected={answers.monitor_resolution === opt.value}
              onClick={() => update({ monitor_resolution: opt.value })}
              title={opt.title}
              subtitle={disabled ? `Требуется бюджет от ${opt.requiresBudget / 1000} 000 ₽` : opt.subtitle}
              disabled={disabled}
            />
          );
        });

      case 'monitor_refresh_rate':
        return REFRESH_OPTIONS.map((opt) => (
          <OptionCard
            key={opt.value}
            selected={answers.monitor_refresh_rate === opt.value}
            onClick={() => update({ monitor_refresh_rate: opt.value })}
            title={opt.title}
            subtitle={opt.subtitle}
          />
        ));

      case 'cpu_vendor':
        return CPU_VENDOR_OPTIONS.map((opt) => (
          <OptionCard
            key={opt.value}
            selected={answers.cpu_vendor === opt.value}
            onClick={() => update({ cpu_vendor: opt.value })}
            title={opt.title}
            subtitle={opt.subtitle}
          />
        ));

      case 'gpu_vendor':
        return GPU_VENDOR_OPTIONS.map((opt) => (
          <OptionCard
            key={opt.value}
            selected={answers.gpu_vendor === opt.value}
            onClick={() => update({ gpu_vendor: opt.value })}
            title={opt.title}
            subtitle={opt.subtitle}
          />
        ));

      case 'ram_size':
        return RAM_OPTIONS.map((opt) => (
          <OptionCard
            key={opt.value}
            selected={answers.ram_size === opt.value}
            onClick={() => update({ ram_size: opt.value })}
            title={opt.title}
            subtitle={opt.subtitle}
          />
        ));

      case 'storage_type':
        return STORAGE_TYPE_OPTIONS.map((opt) => (
          <OptionCard
            key={opt.value}
            selected={answers.storage_type === opt.value}
            onClick={() => update({ storage_type: opt.value })}
            title={opt.title}
            subtitle={opt.subtitle}
          />
        ));

      case 'storage_size':
        return STORAGE_SIZE_OPTIONS.map((opt) => (
          <OptionCard
            key={opt.value}
            selected={answers.storage_size === opt.value}
            onClick={() => update({ storage_size: opt.value })}
            title={opt.title}
            subtitle={opt.subtitle}
          />
        ));

      case 'color':
        return COLOR_OPTIONS.map((opt) => (
          <OptionCard
            key={opt.value}
            selected={answers.color === opt.value}
            onClick={() => update({ color: opt.value })}
            title={opt.title}
            subtitle={opt.subtitle}
          />
        ));

      case 'cooling':
        return COOLING_OPTIONS.map((opt) => (
          <OptionCard
            key={opt.value}
            selected={answers.cooling === opt.value}
            onClick={() => update({ cooling: opt.value })}
            title={opt.title}
            subtitle={opt.subtitle}
          />
        ));

      case 'case':
        return CASE_OPTIONS.map((opt) => (
          <OptionCard
            key={opt.value}
            selected={answers.case === opt.value}
            onClick={() => update({ case: opt.value })}
            title={opt.title}
            subtitle={opt.subtitle}
          />
        ));

      default:
        return null;
    }
  };

  const StepIcon = currentStep?.icon || Wand2;

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div className="absolute inset-0 bg-slate-950/85 backdrop-blur-md" onClick={handleClose} />

      <div className="relative z-10 flex w-full max-w-xl flex-col overflow-hidden rounded-3xl border border-slate-800 bg-slate-900 shadow-2xl">
        <div className="flex items-center justify-between border-b border-slate-800 px-6 py-4">
          <div className="flex items-center gap-3">
            <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-500 via-violet-500 to-fuchsia-500 shadow-lg shadow-indigo-500/30">
              <Wand2 size={18} className="text-white" />
            </div>
            <div>
              <h2 className="text-base font-bold text-white">Подбор ПК через опросник</h2>
              <p className="text-[11px] text-slate-500">
                Вопрос {safeIndex + 1} из {visibleSteps.length}
              </p>
            </div>
          </div>
          <button
            type="button"
            onClick={handleClose}
            disabled={loading}
            className="rounded-lg p-2 text-slate-500 transition-colors hover:bg-slate-800 hover:text-white disabled:opacity-40"
          >
            <X size={18} />
          </button>
        </div>

        <div className="border-b border-slate-800 bg-slate-950/40 px-6 py-3">
          <div className="mb-2 flex items-center justify-between text-[11px] font-medium text-slate-500">
            <span>Шаг {safeIndex + 1} из {visibleSteps.length}</span>
            <span>{Math.round(progress)}%</span>
          </div>
          <div className="h-1.5 overflow-hidden rounded-full bg-slate-800">
            <div
              className="h-full rounded-full bg-gradient-to-r from-indigo-500 via-violet-500 to-fuchsia-500 transition-all duration-500"
              style={{ width: `${progress}%` }}
            />
          </div>
        </div>

        <div className="max-h-[58vh] overflow-y-auto px-6 py-5">
          <div className="mb-5 flex items-start gap-3">
            <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-indigo-500/30 bg-indigo-500/10 text-indigo-300">
              <StepIcon size={18} />
            </div>
            <div>
              <h3 className="text-lg font-bold text-white">{currentStep?.title}</h3>
              <p className="mt-0.5 text-xs text-slate-500">{currentStep?.subtitle}</p>
            </div>
          </div>

          {loading ? (
            <div className="flex flex-col items-center justify-center py-12 text-center">
              <div className="relative mb-5">
                <div className="absolute inset-0 animate-ping rounded-full bg-indigo-500/20" />
                <div className="relative flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-br from-indigo-500 to-violet-600 shadow-xl shadow-indigo-500/40">
                  <Sparkles size={26} className="text-white" />
                </div>
              </div>
              <p className="text-sm font-semibold text-white">
                ИИ анализирует TDP, сокеты и совместимость…
              </p>
              <p className="mt-1 max-w-sm text-xs text-slate-500">
                Подбираем оптимальные комплектующие под ваш бюджет.
              </p>
              <div className="mt-5 flex items-center gap-2 text-xs text-slate-500">
                <Loader2 size={14} className="animate-spin" />
                <span>Это займёт пару секунд</span>
              </div>
            </div>
          ) : (
            <>
              <div className={currentStep?.id === 'target_budget' ? '' : 'grid grid-cols-1 gap-2.5 sm:grid-cols-2'}>
                {renderOptions()}
              </div>

              {errors.length > 0 && (
                <div className="mt-5 rounded-xl border border-rose-500/30 bg-rose-500/5 px-3 py-2.5">
                  <div className="mb-1 flex items-center gap-1.5 text-xs font-semibold text-rose-300">
                    <AlertTriangle size={13} />
                    Не удалось подобрать сборку
                  </div>
                  <ul className="ml-4 list-disc space-y-0.5 text-[11px] text-rose-200/90">
                    {errors.map((msg, i) => (<li key={i}>{msg}</li>))}
                  </ul>
                </div>
              )}
            </>
          )}
        </div>

        <div className="flex items-center justify-between gap-3 border-t border-slate-800 bg-slate-950/40 px-6 py-4">
          <button
            type="button"
            onClick={handleClose}
            disabled={loading}
            className="text-xs font-medium text-slate-500 transition-colors hover:text-slate-300 disabled:opacity-40"
          >
            Собрать вручную
          </button>

          <div className="flex items-center gap-2">
            <button type="button" onClick={goBack} disabled={isFirst || loading} className="btn-ghost !px-3.5 !py-2.5">
              <ChevronLeft size={15} />
              Назад
            </button>

            <button type="button" onClick={goNext} disabled={!canProceed || loading} className="btn-primary">
              {isLast ? (
                <>
                  <Sparkles size={16} />
                  Собрать конфигурацию
                </>
              ) : (
                <>
                  Далее
                  <ChevronRight size={16} />
                </>
              )}
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}