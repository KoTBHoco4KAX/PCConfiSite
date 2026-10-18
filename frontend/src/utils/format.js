const rubFormatter = new Intl.NumberFormat('ru-RU', {
  style: 'currency',
  currency: 'RUB',
  maximumFractionDigits: 0,
});

export function formatPrice(value) {
  const num = Number(value) || 0;
  return rubFormatter.format(num);
}

export const SPEC_KEY = {
  cpu: 'cpuSpec',
  gpu: 'gpuSpec',
  motherboard: 'motherboardSpec',
  ram: 'ramSpec',
  cooling: 'coolingSpec',
  storage: 'storageSpec',
  case: 'caseSpec',
  psu: 'psuSpec',
};

export const SPEC_FIELDS = {
  cpu: [
    ['socket', 'Сокет'],
    ['cores', 'Ядра'],
    ['tdp', 'TDP', 'W'],
  ],
  gpu: [
    ['chipset', 'Чип'],
    ['vram', 'VRAM', 'GB'],
    ['tdp', 'TDP', 'W'],
  ],
  motherboard: [
    ['chipset', 'Чипсет'],
    ['ram_type', 'RAM'],
    ['form_factor', 'Форм'],
  ],
  ram: [
    ['type', 'Тип'],
    ['capacity', 'Объём', 'GB'],
    ['frequency', 'Частота', 'MHz'],
  ],
  cooling: [
    ['type', 'Тип'],
    ['tdp', 'Рассеивание', 'W'],
  ],
  storage: [
    ['type', 'Тип'],
    ['capacity', 'Объём', 'GB'],
  ],
  case: [
    ['form_factor', 'Форм'],
    ['color', 'Цвет'],
  ],
  psu: [
    ['power', 'Мощность', 'W'],
    ['certification', 'Сертификат'],
  ],
};

/**
 * Достаём spec-объект из товара (учитываем snake_case и camelCase).
 */
export function getSpec(product, slotKey) {
  if (!product) return null;
  const camel = SPEC_KEY[slotKey];
  const snake = camel
    .replace(/([A-Z])/g, '_$1')
    .toLowerCase();
  return product[camel] || product[snake] || null;
}

/**
 * Возвращает массив { label, value } готовых к рендеру спек.
 */
export function buildSpecRows(product, slotKey) {
  const spec = getSpec(product, slotKey);
  if (!spec) return [];

  const fields = SPEC_FIELDS[slotKey] || [];
  const rows = [];

  for (const [key, label, unit] of fields) {
    const raw = spec[key];
    if (raw === null || raw === undefined || raw === '') continue;

    let value = raw;
    if (typeof raw === 'boolean') value = raw ? 'Да' : 'Нет';
    else if (unit) value = `${raw} ${unit}`;

    rows.push({ label, value: String(value) });
  }

  return rows;
}

/**
 * Нормализует слот в массив товаров.
 */
export function toArray(value) {
  if (!value) return [];
  return Array.isArray(value) ? value : [value];
}