const MONTHS = ['jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec'];

export function normalizeCnpj(value: unknown = ''): string {
  return String(value).replace(/[^0-9A-Za-z]/g, '').toUpperCase();
}

export function isValidCnpjShape(value: string): boolean {
  return /^[A-Z0-9]{12}[0-9]{2}$/.test(value);
}

export function formatCnpj(value: unknown = ''): string {
  const cnpj = normalizeCnpj(value);
  return `${cnpj.slice(0, 2)}.${cnpj.slice(2, 5)}.${cnpj.slice(5, 8)}/${cnpj.slice(8, 12)}-${cnpj.slice(12, 14)}`;
}

export function formatCep(value: unknown = ''): string {
  const cep = String(value);
  return `${cep.slice(0, 5)}-${cep.slice(4, 7)}`;
}

export function formatPhone(value: unknown = ''): string {
  const phone = String(value);
  return `(${phone.slice(0, 3)}) ${phone.slice(3, 7)}-${phone.slice(7, 11)}`;
}

function parseDatabaseDate(value: unknown): Date | null {
  if (value instanceof Date) return value;
  const parts = String(value || '').split(' ');
  const month = MONTHS.indexOf((parts[0] || '').toLowerCase());
  if (month < 0) return null;
  return new Date(Number(parts[2]), month, Number(parts[1]), 12, 0, 0);
}

export function formatDate(value: unknown): string {
  const date = parseDatabaseDate(value);
  if (!date || Number.isNaN(date.getTime())) return '';
  return new Intl.DateTimeFormat('pt-BR', { timeZone: 'America/Sao_Paulo' }).format(date);
}

export function formatDateTime(value: unknown): string {
  if (value instanceof Date) {
    return new Intl.DateTimeFormat('pt-BR', {
      timeZone: 'America/Sao_Paulo',
      dateStyle: 'short',
      timeStyle: 'short'
    }).format(value);
  }

  const text = String(value || '');
  const date = formatDate(text);
  const time = text.split(' ')[3] || '';
  return [date, time].filter(Boolean).join(' - ');
}

export function formatMoney(value: unknown): string {
  return new Intl.NumberFormat('pt-BR', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(Number(value || 0));
}

const BANKS: Record<string, string> = {
  '001': 'Banco do Brasil',
  '033': 'Santander',
  '104': 'Caixa',
  '237': 'Bradesco',
  '336': 'C6 Bank',
  '341': 'Itaú',
  '399': 'HSBC',
  '422': 'Safra',
  '655': 'Votorantim',
  '707': 'Daycoval',
  '745': 'Citi',
  '756': 'Sicoob'
};

export function bankInfo(code: unknown): { code: string; name: string } {
  const normalized = String(code || '').padStart(3, '0');
  return { code: normalized, name: BANKS[normalized] || 'Banco' };
}

export const ENABLED_BANKS = new Set(Object.keys(BANKS));
