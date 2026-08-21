#!/usr/bin/env bash
set -euo pipefail

ROOT="$(git rev-parse --show-toplevel 2>/dev/null || true)"
if [[ -z "$ROOT" ]]; then
  echo "Erro: execute este patch dentro do repositório zamboni-portal." >&2
  exit 1
fi
cd "$ROOT"

if [[ ! -f apps/boleto_api/src/legacy-boleto.ts ]]; then
  echo "Erro: o bridge legacy-boleto.ts não existe. Faça git pull do main antes de aplicar este patch." >&2
  exit 1
fi

if ! git diff --quiet || ! git diff --cached --quiet; then
  echo "Erro: existem alterações locais rastreadas. Faça commit/stash antes de aplicar o patch." >&2
  git status --short
  exit 1
fi

node <<'NODE_PATCH'
const fs = require('node:fs');

function read(path) {
  return fs.readFileSync(path, 'utf8').replace(/^\uFEFF/, '');
}

function write(path, content) {
  fs.writeFileSync(path, content.replace(/\r\n/g, '\n'), 'utf8');
}

function replaceOnce(text, oldText, newText, label) {
  const index = text.indexOf(oldText);
  if (index < 0) {
    console.error(`Patch abortado: trecho esperado não encontrado em ${label}.`);
    process.exit(1);
  }
  return text.slice(0, index) + newText + text.slice(index + oldText.length);
}

for (const envFile of ['.env.example', '.env.hml.example']) {
  let text = read(envFile);
  const pattern = /\n# [^\n]*(?:renderer|visualizador)[^\n]*(?:PHP|legado)[^\n]*\nLEGACY_BOLETO_BASE_URL=[^\n]*\n/i;
  if (!pattern.test(text)) {
    console.error(`Patch abortado: LEGACY_BOLETO_BASE_URL não encontrado em ${envFile}.`);
    process.exit(1);
  }
  text = text.replace(pattern, '\n');
  write(envFile, text);
}

const configPath = 'apps/boleto_api/src/config.ts';
let config = read(configPath);
config = replaceOnce(
  config,
  "  legacyBoletoBaseUrl: process.env.LEGACY_BOLETO_BASE_URL?.trim() || '',\n",
  '',
  configPath
);
write(configPath, config);

const dbPath = 'apps/boleto_api/src/database.ts';
let db = read(dbPath);
const dbPattern = /\nexport async function legacyBoletoAccess\([\s\S]*?\n}\n\nexport async function customerPortal/m;
if (!dbPattern.test(db)) {
  console.error('Patch abortado: função legacyBoletoAccess não encontrada em database.ts.');
  process.exit(1);
}
const dbReplacement = `
export async function boletoRecord(
  cnpj: string,
  nossoNumero: string,
  banco: string,
  empresa: string
): Promise<Record<string, unknown> | null> {
  const connection = await pool();
  const normalizedBank = String(banco).padStart(3, '0');
  const normalizedCompany = String(empresa).trim().toUpperCase();
  const result = await requestFor(connection, cnpj)
    .input('nossoNumero', sql.VarChar, nossoNumero)
    .input('banco', sql.VarChar, normalizedBank)
    .input('empresa', sql.VarChar, normalizedCompany)
    .query(\`SELECT TOP 1 *
      FROM dbo.Boleto_Titulo_Ativo
      WHERE Cgc_Cpf_Cliente = @cnpj
        AND LTRIM(RTRIM(CONVERT(varchar(40), Num_Nosso_Num))) = @nossoNumero
        AND RIGHT('000' + LTRIM(RTRIM(CONVERT(varchar(3), Cod_Banco))), 3) = @banco
        AND UPPER(LTRIM(RTRIM(CONVERT(varchar(4), EMPRESA)))) = @empresa\`);

  return (result.recordset[0] as Record<string, unknown> | undefined) || null;
}

export async function customerPortal`;
db = db.replace(dbPattern, dbReplacement);
write(dbPath, db);

const serverPath = 'apps/boleto_api/src/server.ts';
let server = read(serverPath);
server = replaceOnce(
  server,
  "import { authenticate, closePool, customerPortal, legacyBoletoAccess } from './database.js';",
  "import { authenticate, boletoRecord, closePool, customerPortal } from './database.js';",
  serverPath
);
server = replaceOnce(
  server,
  "import { fetchLegacyAsset, LegacyBoletoError, renderLegacyBoleto } from './legacy-boleto.js';",
  "import { BoletoError, renderBoleto } from './boleto.js';",
  serverPath
);

const assetPattern = /\n\s{4}if \(request\.method === 'GET' && url\.pathname === '\/api\/boleto-asset'\) \{[\s\S]*?\n\s{4}\}\n\n(?=\s{4}if \(request\.method === 'GET' && url\.pathname === '\/api\/boleto'\))/m;
if (!assetPattern.test(server)) {
  console.error('Patch abortado: rota /api/boleto-asset não encontrada em server.ts.');
  process.exit(1);
}
server = server.replace(assetPattern, '\n');

const oldRoute = `      const access = await legacyBoletoAccess(session.cnpj, nossoNumero, banco, empresa);
      if (!access) {
        json(response, request, 404, { erro: 'Boleto não encontrado para este cliente.' });
        return;
      }

      const document = await renderLegacyBoleto({
        cnpj: session.cnpj,
        password: access.password,
        nossoNumero,
        banco,
        empresa
      });

      await logAccess('log_boleto', session.cnpj, \`Documento visualizado: \${nossoNumero}; Banco: \${banco}; Empresa: \${empresa}\`);
      html(response, 200, document);`;
const newRoute = `      const record = await boletoRecord(session.cnpj, nossoNumero, banco, empresa);
      if (!record) {
        json(response, request, 404, { erro: 'Boleto não encontrado para este cliente.' });
        return;
      }

      const document = renderBoleto(record);
      await logAccess('log_boleto', session.cnpj, \`Documento visualizado: \${nossoNumero}; Banco: \${banco}; Empresa: \${empresa}\`);
      html(response, 200, document);`;
server = replaceOnce(server, oldRoute, newRoute, serverPath);
server = replaceOnce(
  server,
  `    if (error instanceof LegacyBoletoError) {
      json(response, request, error.status, { erro: error.message });
      return;
    }`,
  `    if (error instanceof BoletoError) {
      json(response, request, error.status, { erro: error.message });
      return;
    }`,
  serverPath
);
write(serverPath, server);

const readmePath = 'README.md';
let readme = read(readmePath);
const oldReadme = `A consulta lista os títulos disponíveis no SQL Server. A emissão de um arquivo
de cobrança não está implementada nesta versão e dependerá de uma fonte nativa
ou URL disponibilizada pela base.`;
const newReadme = `A consulta lista os títulos disponíveis no SQL Server. A visualização e a
impressão dos boletos são geradas nativamente pela API TypeScript a partir dos
dados de \`dbo.Boleto_Titulo_Ativo\`, sem dependência do sistema PHP legado. Os
layouts atualmente portados cobrem os bancos habilitados no portal.`;
readme = replaceOnce(readme, oldReadme, newReadme, readmePath);
write(readmePath, readme);
NODE_PATCH

cat > apps/boleto_api/src/boleto.ts <<'TS_BOLETO'
export type BoletoRow = Record<string, unknown>;

export class BoletoError extends Error {
  constructor(public readonly status: number, message: string) {
    super(message);
  }
}

interface BankDocument {
  bankCode: string;
  bankName: string;
  bankDisplay: string;
  barcode: string;
  digitableLine: string;
  agencyCode: string;
  ourNumber: string;
  wallet: string;
}

interface CivilDate {
  year: number;
  month: number;
  day: number;
}

const BANK_NAMES: Record<string, string> = {
  '001': 'Banco do Brasil',
  '033': 'Santander',
  '104': 'Caixa Econômica Federal',
  '237': 'Bradesco',
  '336': 'C6 Bank',
  '341': 'Itaú',
  '399': 'HSBC',
  '422': 'Safra',
  '655': 'Banco Votorantim',
  '707': 'Daycoval',
  '745': 'Citibank',
  '756': 'Sicoob'
};

const ZAMBONI_ONLY_BANKS = new Set(['399', '745']);
const MONTHS: Record<string, number> = {
  jan: 1, feb: 2, mar: 3, apr: 4, may: 5, jun: 6,
  jul: 7, aug: 8, sep: 9, oct: 10, nov: 11, dec: 12
};

function value(row: BoletoRow, key: string): string {
  return String(row[key] ?? '').trim();
}

function numeric(value: unknown): number {
  if (typeof value === 'number') return Number.isFinite(value) ? value : 0;
  let text = String(value ?? '').trim();
  if (!text) return 0;
  if (text.includes(',') && text.includes('.')) text = text.replace(/\./g, '').replace(',', '.');
  else if (text.includes(',')) text = text.replace(',', '.');
  const parsed = Number(text);
  return Number.isFinite(parsed) ? parsed : 0;
}

function digits(value: unknown): string {
  return String(value ?? '').replace(/\D/g, '');
}

function leftPad(value: unknown, length: number, char = '0'): string {
  return String(value ?? '').replace(/,/g, '').padStart(length, char);
}

function rightPad(value: unknown, length: number, char = '0'): string {
  return String(value ?? '').padEnd(length, char);
}

function splitAccount(input: unknown): { account: string; digit: string } {
  const text = String(input ?? '').trim();
  const separator = text.indexOf('-');
  if (separator < 0) return { account: text, digit: '0' };
  return {
    account: text.slice(0, separator),
    digit: text.slice(separator + 1) || '0'
  };
}

function parseCivilDate(input: unknown): CivilDate | null {
  if (input instanceof Date && !Number.isNaN(input.getTime())) {
    return { year: input.getUTCFullYear(), month: input.getUTCMonth() + 1, day: input.getUTCDate() };
  }

  const text = String(input ?? '').trim();
  if (!text) return null;

  let match = text.match(/^(\d{4})-(\d{2})-(\d{2})/);
  if (match) return { year: Number(match[1]), month: Number(match[2]), day: Number(match[3]) };

  match = text.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})/);
  if (match) return { year: Number(match[3]), month: Number(match[2]), day: Number(match[1]) };

  const parts = text.split(/\s+/);
  const month = MONTHS[(parts[0] || '').toLowerCase()];
  if (month && parts[1] && parts[2]) {
    return { year: Number(parts[2]), month, day: Number(parts[1]) };
  }

  return null;
}

function validCivilDate(date: CivilDate | null): date is CivilDate {
  if (!date) return false;
  const candidate = new Date(Date.UTC(date.year, date.month - 1, date.day));
  return candidate.getUTCFullYear() === date.year
    && candidate.getUTCMonth() + 1 === date.month
    && candidate.getUTCDate() === date.day;
}

function utcDate(date: CivilDate): Date {
  return new Date(Date.UTC(date.year, date.month - 1, date.day));
}

function civilFromUtc(date: Date): CivilDate {
  return { year: date.getUTCFullYear(), month: date.getUTCMonth() + 1, day: date.getUTCDate() };
}

function formatCivil(date: CivilDate): string {
  return `${String(date.day).padStart(2, '0')}/${String(date.month).padStart(2, '0')}/${date.year}`;
}

function dueDate(row: BoletoRow): CivilDate {
  const parsed = parseCivilDate(row.Dat_Venc);
  if (!validCivilDate(parsed)) throw new BoletoError(422, 'A data de vencimento do boleto é inválida.');
  return parsed;
}

function todayInSaoPaulo(): CivilDate {
  const parts = new Intl.DateTimeFormat('en-CA', {
    timeZone: 'America/Sao_Paulo', year: 'numeric', month: '2-digit', day: '2-digit'
  }).formatToParts(new Date());
  const get = (type: Intl.DateTimeFormatPartTypes) => Number(parts.find((part) => part.type === type)?.value || 0);
  return { year: get('year'), month: get('month'), day: get('day') };
}

function addDays(date: CivilDate, days: number): CivilDate {
  const result = utcDate(date);
  result.setUTCDate(result.getUTCDate() + days);
  return civilFromUtc(result);
}

function daysBetween(from: CivilDate, to: CivilDate): number {
  return Math.round((utcDate(to).getTime() - utcDate(from).getTime()) / 86_400_000);
}

function easter(year: number): CivilDate {
  const a = year % 19;
  const b = Math.floor(year / 100);
  const c = year % 100;
  const d = Math.floor(b / 4);
  const e = b % 4;
  const f = Math.floor((b + 8) / 25);
  const g = Math.floor((b - f + 1) / 3);
  const h = (19 * a + b - d - g + 15) % 30;
  const i = Math.floor(c / 4);
  const k = c % 4;
  const l = (32 + 2 * e + 2 * i - h - k) % 7;
  const m = Math.floor((a + 11 * h + 22 * l) / 451);
  const month = Math.floor((h + l - 7 * m + 114) / 31);
  const day = ((h + l - 7 * m + 114) % 31) + 1;
  return { year, month, day };
}

function dateKey(date: CivilDate): string {
  return `${date.year}-${String(date.month).padStart(2, '0')}-${String(date.day).padStart(2, '0')}`;
}

function holidays(year: number): Set<string> {
  const fixed = [
    [1, 1], [4, 21], [5, 1], [9, 7], [10, 12], [11, 2], [11, 15], [12, 25]
  ].map(([month, day]) => dateKey({ year, month, day }));
  const pascoa = easter(year);
  const movable = [-50, -49, -48, -47, -2, 60].map((offset) => dateKey(addDays(pascoa, offset)));
  return new Set([...fixed, ...movable]);
}

function nextBusinessDay(input: CivilDate): CivilDate {
  let date = input;
  for (let attempts = 0; attempts < 15; attempts++) {
    const day = utcDate(date).getUTCDay();
    if (day !== 0 && day !== 6 && !holidays(date.year).has(dateKey(date))) return date;
    date = addDays(date, 1);
  }
  return date;
}

function displayedDueAndAmount(row: BoletoRow): { due: CivilDate; amount: number; lateDays: number; lateFee: number } {
  const originalDue = dueDate(row);
  const originalAmount = numeric(row.Val_total);
  const dailyInterest = numeric(row.Juros_mora_dia);
  const today = todayInSaoPaulo();
  const adjustedDue = nextBusinessDay(originalDue);
  const diffFromAdjustedDue = daysBetween(adjustedDue, today);

  if (diffFromAdjustedDue <= 0) {
    const todayBusiness = nextBusinessDay(today);
    let displayDue: CivilDate;
    if (dateKey(adjustedDue) === dateKey(todayBusiness)) displayDue = adjustedDue;
    else displayDue = daysBetween(originalDue, today) < 0 ? originalDue : adjustedDue;
    return { due: displayDue, amount: originalAmount, lateDays: 0, lateFee: 0 };
  }

  const paymentDate = nextBusinessDay(today);
  const lateDays = Math.max(0, daysBetween(originalDue, paymentDate));
  const lateFee = dailyInterest * lateDays;
  return { due: paymentDate, amount: originalAmount + lateFee, lateDays, lateFee };
}

export function factorDueDate(input: CivilDate | string | Date): number {
  const date = typeof input === 'object' && !(input instanceof Date) && 'year' in input
    ? input as CivilDate
    : parseCivilDate(input);
  if (!validCivilDate(date)) throw new BoletoError(422, 'Data inválida para cálculo do fator de vencimento.');
  const newCycle = { year: 2025, month: 2, day: 22 };
  if (utcDate(date).getTime() >= utcDate(newCycle).getTime()) return daysBetween(newCycle, date) + 1000;
  return daysBetween({ year: 1997, month: 10, day: 7 }, date);
}

export function modulo10(input: string): number {
  let total = 0;
  let factor = 2;
  for (let i = input.length - 1; i >= 0; i--) {
    const product = Number(input[i]) * factor;
    total += Math.floor(product / 10) + (product % 10);
    factor = factor === 2 ? 1 : 2;
  }
  const remainder = total % 10;
  return remainder === 0 ? 0 : 10 - remainder;
}

function modulo11Remainder(input: string, base = 9): number {
  let total = 0;
  let factor = 2;
  for (let i = input.length - 1; i >= 0; i--) {
    total += Number(input[i]) * factor;
    if (factor === base) factor = 1;
    factor++;
  }
  return total % 11;
}

function modulo11(input: string, base = 9): number {
  let total = 0;
  let factor = 2;
  for (let i = input.length - 1; i >= 0; i--) {
    total += Number(input[i]) * factor;
    if (factor === base) factor = 1;
    factor++;
  }
  const digit = (total * 10) % 11;
  return digit === 10 ? 0 : digit;
}

function modulo11Brazil(input: string): string {
  let total = 0;
  let factor = 2;
  for (let i = input.length - 1; i >= 0; i--) {
    total += Number(input[i]) * factor;
    if (factor === 9) factor = 1;
    factor++;
  }
  let digit: string | number = (total * 10) % 11;
  if (digit === 10) digit = 'X';
  if (input.length === 43 && (digit === 0 || digit === 'X' || Number(digit) > 9)) digit = 1;
  return String(digit);
}

function barcodeDvGeneric(base: string): number {
  const digit = 11 - modulo11Remainder(base, 9);
  return digit === 0 || digit === 1 || digit === 10 || digit === 11 ? 1 : digit;
}

function barcodeDvBradesco(base: string): number {
  const remainder = modulo11Remainder(base, 9);
  return remainder === 0 || remainder === 1 || remainder === 10 ? 1 : 11 - remainder;
}

function barcodeDvHsbc(base: string): number {
  let total = 0;
  let factor = 2;
  for (let i = base.length - 1; i >= 0; i--) {
    total += Number(base[i]) * factor;
    factor++;
    if (factor > 9) factor = 2;
  }
  const remainder = total % 11;
  return remainder === 0 || remainder === 1 || remainder === 10 ? 1 : 11 - remainder;
}

function bankDisplay(code: string, fixedDigit?: string): string {
  if (fixedDigit) return `${code}-${fixedDigit}`;
  return `${code}-${modulo11(code)}`;
}

function originalAmountBarcode(row: BoletoRow): string {
  return String(Math.round(numeric(row.Val_total) * 100)).padStart(10, '0');
}

function factor(row: BoletoRow): string {
  return String(factorDueDate(dueDate(row))).padStart(4, '0');
}

function assertBarcode(code: string, bank: string): string {
  if (!/^\d{44}$/.test(code)) {
    throw new BoletoError(422, `Os dados cadastrados para o banco ${bank} não formaram um código de barras válido.`);
  }
  return code;
}

export function digitableLine(barcode: string): string {
  if (!/^\d{44}$/.test(barcode)) throw new BoletoError(422, 'Código de barras inválido.');
  const field1Base = barcode.slice(0, 4) + barcode.slice(19, 24);
  const field2Base = barcode.slice(24, 34);
  const field3Base = barcode.slice(34, 44);
  const field1 = field1Base + modulo10(field1Base);
  const field2 = field2Base + modulo10(field2Base);
  const field3 = field3Base + modulo10(field3Base);
  return `${field1.slice(0, 5)}.${field1.slice(5)} ${field2.slice(0, 5)}.${field2.slice(5)} ${field3.slice(0, 5)}.${field3.slice(5)} ${barcode[4]} ${barcode.slice(5, 19)}`;
}

function buildResult(bankCode: string, barcode: string, agencyCode: string, ourNumber: string, wallet: string, fixedDigit?: string): BankDocument {
  const validBarcode = assertBarcode(barcode, bankCode);
  return {
    bankCode,
    bankName: BANK_NAMES[bankCode] || `Banco ${bankCode}`,
    bankDisplay: bankDisplay(bankCode, fixedDigit),
    barcode: validBarcode,
    digitableLine: digitableLine(validBarcode),
    agencyCode,
    ourNumber,
    wallet
  };
}

function brazil(row: BoletoRow): BankDocument {
  const raw = value(row, 'Num_Nosso_Num');
  const account = splitAccount(row.Cod_Cedente);
  const agency = value(row, 'Cod_Agencia').slice(0, 4);
  const wallet = '17';
  const amount = originalAmountBarcode(row);
  const dueFactor = factor(row);

  if (raw.length === 17) {
    const agreement = raw.slice(0, 7);
    const our = leftPad(raw.slice(7, 17), 10);
    const zeros = '000000';
    const base = `0019${dueFactor}${amount}${zeros}${rightPad(agreement, 7)}${our}${wallet}`;
    const dv = modulo11Brazil(base);
    const barcode = `0019${dv}${dueFactor}${amount}${zeros}${rightPad(agreement, 7)}${our}${wallet}`;
    return buildResult('001', barcode, `${leftPad(agency, 4)}-X / ${leftPad(account.account, 8)}-${account.digit}`, `${agreement}${our}`, wallet);
  }

  if (raw.length === 12) {
    const agreement = raw.slice(0, 6);
    const our = leftPad(raw.slice(6, -1), 5);
    const accountNumber = leftPad(account.account, 8);
    const base = `0019${dueFactor}${amount}${rightPad(agreement, 6)}${our}${leftPad(agency, 4)}${accountNumber}${wallet}`;
    const dv = modulo11Brazil(base);
    const barcode = `0019${dv}${dueFactor}${amount}${rightPad(agreement, 6)}${our}${leftPad(agency, 4)}${accountNumber}${wallet}`;
    const display = `${agreement}${our}-${modulo11Brazil(`${agreement}${our}`)}`;
    return buildResult('001', barcode, `${leftPad(agency, 4)}-X / ${accountNumber}-${account.digit}`, display, wallet);
  }

  throw new BoletoError(422, 'Nosso número do Banco do Brasil possui formato não suportado pelo convênio atual.');
}

function bradesco(row: BoletoRow): BankDocument {
  const rawAccount = value(row, 'Cod_Cedente') === '3288-3' ? '6829-2' : value(row, 'Cod_Cedente');
  const account = splitAccount(rawAccount);
  const agencyData = splitAccount(row.Cod_Agencia);
  const agency = leftPad(agencyData.account.slice(0, 4), 4);
  const accountNumber = leftPad(account.account, 7);
  const wallet = String(Number(value(row, 'Cod_Var_Carteira') || '0')).padStart(2, '0');
  const ourRaw = value(row, 'Num_Nosso_Num').slice(0, 11);
  const ourBase = leftPad(wallet, 2) + leftPad(ourRaw, 11);
  const remainder = modulo11Remainder(ourBase, 7);
  const digit = 11 - remainder;
  const ourDv = digit === 10 ? 'P' : digit === 11 ? '0' : String(digit);
  const base = `2379${factor(row)}${originalAmountBarcode(row)}${agency}${ourBase}${accountNumber}0`;
  const dv = barcodeDvBradesco(base);
  const barcode = `2379${dv}${factor(row)}${originalAmountBarcode(row)}${agency}${ourBase}${accountNumber}0`;
  return buildResult('237', barcode, `${agency}-${agencyData.digit} / ${accountNumber}-${account.digit}`, `${ourBase.slice(0, 2)}/${ourBase.slice(2)}-${ourDv}`, wallet);
}

function caixa(row: BoletoRow): BankDocument {
  const raw = value(row, 'Num_Nosso_Num');
  const cedent = splitAccount(row.Cod_Cedente);
  const cedentNumber = leftPad(cedent.account, 6);
  const cedentDvCalc = 11 - modulo11Remainder(cedentNumber, 9);
  const cedentDv = cedentDvCalc === 10 || cedentDvCalc === 11 ? 0 : cedentDvCalc;
  const n1 = leftPad(raw.slice(2, 5), 3);
  const n2 = leftPad(raw.slice(5, 8), 3);
  const n3 = leftPad(raw.slice(8, 17), 9);
  const freeBase = `${cedentNumber}${cedentDv}${n1}1${n2}4${n3}`;
  const freeDvCalc = 11 - modulo11Remainder(freeBase, 9);
  const freeDv = freeDvCalc === 10 || freeDvCalc === 11 ? 0 : freeDvCalc;
  const free = `${freeBase}${freeDv}`;
  const ourBase = `14${n1}${n2}${n3}`;
  const ourDvCalc = 11 - modulo11Remainder(ourBase, 9);
  const ourDv = ourDvCalc === 10 || ourDvCalc === 11 ? 0 : ourDvCalc;
  const base = `1049${factor(row)}${originalAmountBarcode(row)}${free}`;
  const dv = barcodeDvBradesco(base);
  const barcode = `1049${dv}${factor(row)}${originalAmountBarcode(row)}${free}`;
  return buildResult('104', barcode, `${leftPad(value(row, 'Cod_Agencia'), 4)} / ${cedentNumber}-${cedentDv}`, `${ourBase}-${ourDv}`, 'RG');
}

function itau(row: BoletoRow): BankDocument {
  const agency = '2938';
  const account = '18567';
  const wallet = '109';
  const our = leftPad(value(row, 'Num_Nosso_Num').slice(0, 8), 8);
  const ourDv = modulo10(`${agency}${account}${wallet}${our}`);
  const accountDv = modulo10(`${agency}${account}`);
  const base = `3419${factor(row)}${originalAmountBarcode(row)}${wallet}${our}${ourDv}${agency}${account}${accountDv}000`;
  const dv = barcodeDvGeneric(base);
  const barcode = `${base.slice(0, 4)}${dv}${base.slice(4)}`;
  return buildResult('341', barcode, `${agency} / ${account}-${accountDv}`, `${wallet}/${our}-${ourDv}`, wallet);
}

function santander(row: BoletoRow): BankDocument {
  const agency = leftPad(value(row, 'Cod_Agencia'), 3);
  const rawAccount = value(row, 'Cod_Cedente');
  const isSofisa = value(row, 'BENEF_NOME').toUpperCase().includes('SOFISA');
  const account = isSofisa ? rawAccount : leftPad(rawAccount.slice(1), 7);
  const wallet = isSofisa ? '101' : '009';
  const our = leftPad(value(row, 'Num_Nosso_Num'), 13);
  const base = `0339${factor(row)}${originalAmountBarcode(row)}9${account}${our}0${wallet}`;
  const dv = barcodeDvGeneric(base);
  const barcode = `${base.slice(0, 4)}${dv}${base.slice(4)}`;
  return buildResult('033', barcode, `${agency} / ${rawAccount}`, our, wallet);
}

function c6(row: BoletoRow): BankDocument {
  const raw = value(row, 'Num_Nosso_Num');
  const agency = leftPad(splitAccount(row.Cod_Agencia).account.slice(0, 4), 4);
  const account = leftPad(splitAccount(row.Cod_Cedente).account, 12);
  const walletOriginal = String(Number(value(row, 'Cod_Var_Carteira') || '0')).padStart(3, '0');
  const wallet = walletOriginal.slice(1, 3);
  const our = raw.slice(1, 11);
  const ourDv = raw.slice(-1);
  const base = `3369${factor(row)}${originalAmountBarcode(row)}${account}${our}${wallet}4`;
  const dv = barcodeDvBradesco(base);
  const barcode = `3369${dv}${factor(row)}${originalAmountBarcode(row)}${account}${our}${wallet}4`;
  return buildResult('336', barcode, `${agency} / ${value(row, 'Cod_Cedente')}`, `${wallet}/${raw.slice(0, 11)}-${ourDv}`, walletOriginal, '1');
}

function safra(row: BoletoRow): BankDocument {
  const agency = leftPad(value(row, 'Cod_Agencia'), 3);
  const rawAccount = value(row, 'Cod_Cedente');
  const account = leftPad(rawAccount.slice(1), 9);
  const wallet = '002';
  const our = leftPad(value(row, 'Num_Nosso_Num'), 9);
  const base = `4229${factor(row)}${originalAmountBarcode(row)}7${agency}${account}${our}${wallet.slice(-1)}`;
  const dv = barcodeDvGeneric(base);
  const barcode = `${base.slice(0, 4)}${dv}${base.slice(4)}`;
  return buildResult('422', barcode, `${agency} / ${rawAccount}`, `${our.slice(0, -1)}-${our.slice(-1)}`, wallet);
}

function daycoval(row: BoletoRow): BankDocument {
  const agency = leftPad(splitAccount(row.Cod_Agencia).account.slice(0, 4), 3);
  const wallet = '121';
  const agreement = '1601501';
  const our = leftPad(value(row, 'Num_Nosso_Num'), 11);
  const base = `7079${factor(row)}${originalAmountBarcode(row)}${agency}${wallet}${agreement}${our}`;
  const dv = barcodeDvGeneric(base);
  const barcode = `${base.slice(0, 4)}${dv}${base.slice(4)}`;
  return buildResult('707', barcode, `${agency} / ${value(row, 'Cod_Cedente')}`, our, wallet);
}

function sicoob(row: BoletoRow): BankDocument {
  const agency = leftPad(splitAccount(row.Cod_Agencia).account.slice(0, 4), 3);
  const agreement = leftPad(value(row, 'Cod_Cedente').replace(/-/g, ''), 7);
  const wallet = value(row, 'Cod_Var_Carteira');
  const product = wallet === '001' ? '1' : '3';
  const mode = wallet === '001' ? '01' : '03';
  const our = leftPad(value(row, 'Num_Nosso_Num'), 8);
  const base = `7569${factor(row)}${originalAmountBarcode(row)}${product}${agency}${mode}${agreement}${our}001`;
  const dv = barcodeDvGeneric(base);
  const barcode = `${base.slice(0, 4)}${dv}${base.slice(4)}`;
  return buildResult('756', barcode, `${agency} / ${value(row, 'Cod_Cedente')}`, our, wallet);
}

function votorantim(row: BoletoRow): BankDocument {
  const agency = leftPad(value(row, 'Cod_Agencia'), 3);
  const company = value(row, 'EMPRESA').toUpperCase();
  const agreement = leftPad(company === 'MIXC' ? '1732' : '1317', 10);
  const wallet = '500';
  const our = leftPad(value(row, 'Num_Nosso_Num'), 10);
  const base = `6559${factor(row)}${originalAmountBarcode(row)}${agreement}${wallet}${our}00`;
  const dv = barcodeDvGeneric(base);
  const barcode = `${base.slice(0, 4)}${dv}${base.slice(4)}`;
  return buildResult('655', barcode, `${agency} / ${value(row, 'Cod_Cedente')}`, our, wallet);
}

function citi(row: BoletoRow): BankDocument {
  const agency = leftPad(value(row, 'Cod_Agencia'), 3);
  const cedentOriginal = value(row, 'Cod_Cedente');
  const cedent = cedentOriginal.replace(/\./g, '');
  const account = leftPad(cedent.slice(1, 9), 8);
  const accountDv = leftPad(cedentOriginal.slice(-1), 1);
  const wallet = '112';
  const our = leftPad(value(row, 'Num_Nosso_Num'), 12);
  const base = `7459${factor(row)}${originalAmountBarcode(row)}3${wallet}${account}${accountDv}${our}`;
  const dv = barcodeDvGeneric(base);
  const barcode = `${base.slice(0, 4)}${dv}${base.slice(4)}`;
  return buildResult('745', barcode, `${agency} / ${cedentOriginal}`, our, wallet);
}

function hsbc(row: BoletoRow): BankDocument {
  const agency = '0609';
  const cedent = '2028879';
  const our = value(row, 'Num_Nosso_Num');
  const wallet = 'CSB';
  const agencyAccount = `${agency}${cedent}`;
  const base = `3999${factor(row)}${originalAmountBarcode(row)}${our}${agencyAccount}001`;
  const dv = barcodeDvHsbc(base);
  const barcode = `${base.slice(0, 4)}${dv}${base.slice(4)}`;
  return buildResult('399', barcode, `${agency}-${cedent}`, our, wallet);
}

export function buildBankDocument(row: BoletoRow): BankDocument {
  const bank = value(row, 'Cod_Banco').padStart(3, '0');
  const company = value(row, 'EMPRESA').toUpperCase();
  if (ZAMBONI_ONLY_BANKS.has(bank) && company !== 'ZAMB') {
    throw new BoletoError(422, 'Este banco não possui convênio para a empresa informada.');
  }

  switch (bank) {
    case '001': return brazil(row);
    case '033': return santander(row);
    case '104': return caixa(row);
    case '237': return bradesco(row);
    case '336': return c6(row);
    case '341': return itau(row);
    case '399': return hsbc(row);
    case '422': return safra(row);
    case '655': return votorantim(row);
    case '707': return daycoval(row);
    case '745': return citi(row);
    case '756': return sicoob(row);
    default: throw new BoletoError(422, `Banco ${bank} não possui layout de boleto implementado.`);
  }
}

function formatDocument(value: unknown): string {
  const clean = String(value ?? '').replace(/[^0-9A-Za-z]/g, '').toUpperCase();
  if (clean.length === 14) return `${clean.slice(0, 2)}.${clean.slice(2, 5)}.${clean.slice(5, 8)}/${clean.slice(8, 12)}-${clean.slice(12)}`;
  if (clean.length === 11) return `${clean.slice(0, 3)}.${clean.slice(3, 6)}.${clean.slice(6, 9)}-${clean.slice(9)}`;
  return clean;
}

function formatCep(value: unknown): string {
  const clean = digits(value);
  return clean.length >= 8 ? `${clean.slice(0, 5)}-${clean.slice(5, 8)}` : clean;
}

function money(value: unknown): string {
  return new Intl.NumberFormat('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(numeric(value));
}

function escapeHtml(value: unknown): string {
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

function instructionLines(row: BoletoRow, adjustment: ReturnType<typeof displayedDueAndAmount>): string[] {
  const invoices = [value(row, 'Num_Nota_Fiscal1'), value(row, 'Num_Nota_Fiscal2')].filter(Boolean).join(' / ');
  const lines = [
    `JUROS POR DIA DE ATRASO R$ ${money(row.Juros_mora_dia)}`,
    `PEDIDO ABAIXO DO VALOR MÍNIMO R$ ${money(row.Val_Acrescimo)}`,
    `VALOR DA PARCELA R$ ${money(row.Val_Titulo)}`,
    `ACRÉSCIMO DO PRAZO R$ ${money(row.Val_Vendor)}`
  ];
  if (adjustment.lateDays > 0) lines.push(`${adjustment.lateDays} dia(s) de atraso x R$ ${money(row.Juros_mora_dia)} = R$ ${money(adjustment.lateFee)}`);
  if (invoices) lines.push(`Referente Nota(s) Fiscal(is): ${invoices}`);
  for (const key of ['Desconto_Boleto', 'Desconto_Escala', 'MSGB3']) {
    const extra = value(row, key);
    if (extra) lines.push(extra.toUpperCase());
  }
  return lines;
}

function barcodeHtml(barcode: string): string {
  const patterns = ['00110', '10001', '01001', '11000', '00101', '10100', '01100', '00011', '10010', '01010'];
  let text = barcode.length % 2 ? `0${barcode}` : barcode;
  const bars: string[] = [
    '<i class="bar black narrow"></i>', '<i class="bar white narrow"></i>',
    '<i class="bar black narrow"></i>', '<i class="bar white narrow"></i>'
  ];
  while (text.length) {
    const first = Number(text[0]);
    const second = Number(text[1]);
    text = text.slice(2);
    let pattern = '';
    for (let i = 0; i < 5; i++) pattern += patterns[first][i] + patterns[second][i];
    for (let i = 0; i < 10; i++) {
      bars.push(`<i class="bar ${i % 2 === 0 ? 'black' : 'white'} ${pattern[i] === '0' ? 'narrow' : 'wide'}"></i>`);
    }
  }
  bars.push('<i class="bar black wide"></i>', '<i class="bar white narrow"></i>', '<i class="bar black narrow"></i>');
  return `<div class="barcode" aria-label="Código de barras ${barcode}">${bars.join('')}</div>`;
}

function cell(label: string, content: string, classes = ''): string {
  return `<div class="cell ${classes}"><span>${escapeHtml(label)}</span><strong>${content || '&nbsp;'}</strong></div>`;
}

export function renderBoleto(row: BoletoRow): string {
  const bank = buildBankDocument(row);
  const adjustment = displayedDueAndAmount(row);
  const originalDue = dueDate(row);
  const today = todayInSaoPaulo();
  const beneficiaryName = value(row, 'BENEF_NOME') || 'Zamboni Comercial Ltda.';
  const beneficiaryDocument = formatDocument(row.BENEF_CNPJ);
  const beneficiary = `${escapeHtml(beneficiaryName)}${beneficiaryDocument ? ` (${escapeHtml(beneficiaryDocument)})` : ''}`;
  const payerDocument = formatDocument(row.Cgc_Cpf_Cliente);
  const payer = `${escapeHtml(value(row, 'Nom_Razao_Social'))}${payerDocument ? ` (${escapeHtml(payerDocument)})` : ''}`;
  const payerAddress = `${escapeHtml(value(row, 'Endereco'))} ${escapeHtml(value(row, 'Bairro'))}`.trim();
  const payerCity = `${escapeHtml(value(row, 'Nom_Municipio'))}-${escapeHtml(value(row, 'Sgl_Estado'))} ${escapeHtml(formatCep(row.Cep))}`.trim();
  const beneficiaryAddress = [value(row, 'BENEF_STRAS'), `${value(row, 'BENEF_ORT01')}/${value(row, 'BENEF_REGIO')}`, value(row, 'BENEF_CEP')].filter(Boolean).join(' - ');
  const avalistName = value(row, 'SAC_NOME');
  const avalistDocument = formatDocument(value(row, 'SAC_CPF') || value(row, 'SAC_CNPJ'));
  const avalistAddress = [value(row, 'SAC_STRAS'), `${value(row, 'SAC_CITY1')}/${value(row, 'SAC_REGIO')}`].filter((part) => part && part !== '/').join(' - ');
  const instructions = instructionLines(row, adjustment).map((line) => `<div>${escapeHtml(line)}</div>`).join('');
  const documentNumber = escapeHtml(value(row, 'Cod_Documento'));
  const currentDate = formatCivil(today);
  const displayedAmount = money(adjustment.amount);

  return `<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Boleto ${escapeHtml(value(row, 'Num_Nosso_Num'))}</title>
<style>
*{box-sizing:border-box}body{margin:0;background:#f4f1f0;color:#111;font-family:Arial,Helvetica,sans-serif}.toolbar{max-width:940px;margin:28px auto 0;background:#fff;border:1px solid #ddd4d5;border-bottom:3px solid #c8102e;border-radius:7px 7px 0 0;padding:18px 22px;display:flex;align-items:center;gap:18px}.toolbar-title{flex:1}.toolbar-title strong{display:block;font-size:21px;color:#231719}.toolbar-title span{display:block;margin-top:4px;color:#74696b;font-size:12px}.actions{display:flex;gap:10px}.actions button{height:42px;padding:0 18px;border:1px solid #c8102e;border-radius:6px;background:#fff;color:#b00827;font-weight:700;cursor:pointer}.actions .primary{background:#bd0627;color:#fff}.paper{width:940px;margin:0 auto 36px;background:#fff;border:1px solid #ddd4d5;border-top:0;padding:26px 34px 38px}.cut{font-size:9px;color:#333;margin:10px 0 6px}.dash{border-top:1px dashed #333;margin-bottom:10px}.bank-head{display:grid;grid-template-columns:185px 72px 1fr;align-items:center;border-bottom:2px solid #111;min-height:48px}.bank-name{font-size:18px;font-weight:800}.bank-code{font-size:17px;font-weight:800;border-left:2px solid #111;border-right:2px solid #111;text-align:center}.line{font-size:17px;font-weight:800;text-align:right;letter-spacing:.02em;white-space:nowrap}.grid{display:grid;border-left:1px solid #111;border-top:1px solid #111}.grid.receipt{grid-template-columns:2.3fr 1.35fr .65fr .7fr}.grid.row5{grid-template-columns:1fr 1fr 1fr 1fr 1fr}.grid.row4{grid-template-columns:1.5fr .8fr .7fr 1fr}.grid.row3{grid-template-columns:1.7fr 1fr 1fr}.cell{min-height:42px;padding:4px 7px;border-right:1px solid #111;border-bottom:1px solid #111;overflow:hidden}.cell span{display:block;font-size:8px;color:#333;margin-bottom:4px}.cell strong{display:block;font-size:11px;font-weight:600}.cell.right strong{text-align:right}.cell.amount strong{font-size:13px}.wide{grid-column:span 2}.payment{display:grid;grid-template-columns:1fr 170px;border-left:1px solid #111;border-top:1px solid #111}.instructions{display:grid;grid-template-columns:1fr 170px;border-left:1px solid #111}.instructions .text{min-height:120px;padding:7px;border-right:1px solid #111;border-bottom:1px solid #111;font-size:9px;line-height:1.45}.side{display:flex;flex-direction:column}.side .cell{flex:1}.payer{border:1px solid #111;border-top:0;padding:6px 8px;min-height:64px;font-size:10px;line-height:1.45}.payer span{font-size:8px;display:block;margin-bottom:4px}.avalist{display:flex;justify-content:space-between;border-bottom:0;padding:6px 8px;min-height:46px;font-size:9px}.barcode-wrap{padding:12px 4px 2px;overflow:hidden}.barcode{height:52px;display:flex;align-items:stretch;white-space:nowrap}.bar{display:inline-block;height:52px;flex:0 0 auto}.bar.black{background:#000}.bar.white{background:#fff}.bar.narrow{width:1px}.bar.wide{width:3px}.meta-note{font-size:8px;text-align:right;margin-top:4px;color:#333}.benef-address{font-size:8px;color:#444;margin:3px 0 10px}.spacer{height:34px}@media(max-width:980px){.toolbar,.paper{width:100%;margin-left:0;margin-right:0}.toolbar{margin-top:0;border-radius:0}.paper{padding:20px 12px;overflow-x:auto}.document{min-width:850px}}@media print{@page{size:A4 portrait;margin:8mm}body{background:#fff}.toolbar{display:none}.paper{width:auto;margin:0;padding:0;border:0}.document{min-width:0}.spacer{height:20px}.barcode-wrap{break-inside:avoid}}
</style>
</head>
<body>
<header class="toolbar"><div class="toolbar-title"><strong>Visualização do boleto</strong><span>Documento bancário gerado pelo Portal Zamboni</span></div><div class="actions"><button onclick="window.print()">Salvar em PDF</button><button class="primary" onclick="window.print()">Imprimir</button></div></header>
<main class="paper"><div class="document">
<div class="cut">Corte na linha pontilhada</div><div class="dash"></div>
<div class="bank-head"><div class="bank-name">${escapeHtml(bank.bankName)}</div><div class="bank-code">${escapeHtml(bank.bankDisplay)}</div><div class="line">${escapeHtml(bank.digitableLine)}</div></div>
<div class="grid receipt">${cell('Beneficiário', beneficiary)}${cell('Agência / Código do Beneficiário', escapeHtml(bank.agencyCode))}${cell('Espécie', 'R$')}${cell('Nosso número', escapeHtml(bank.ourNumber))}</div>
<div class="benef-address">${escapeHtml(beneficiaryAddress)}</div>
<div class="grid row5">${cell('Número do documento', documentNumber)}${cell('Carteira', escapeHtml(bank.wallet))}${cell('CPF/CNPJ do Beneficiário', escapeHtml(beneficiaryDocument))}${cell('Vencimento', escapeHtml(formatCivil(adjustment.due)), 'right')}${cell('Valor documento', `R$ ${escapeHtml(displayedAmount)}`, 'right amount')}</div>
<div class="grid row5">${cell('(-) Desconto / Abatimento', '')}${cell('(-) Outras deduções', '')}${cell('(+) Mora / Multa', adjustment.lateFee ? `R$ ${escapeHtml(money(adjustment.lateFee))}` : '')}${cell('(+) Outros acréscimos', '')}${cell('(=) Valor cobrado', `R$ ${escapeHtml(displayedAmount)}`, 'right amount')}</div>
<div class="payer"><span>Pagador</span><strong>${payer}</strong><br>${payerAddress}<br>${payerCity}</div><div class="meta-note">Autenticação mecânica</div>
<div class="spacer"></div>
<div class="cut">Corte na linha pontilhada</div><div class="dash"></div>
<div class="bank-head"><div class="bank-name">${escapeHtml(bank.bankName)}</div><div class="bank-code">${escapeHtml(bank.bankDisplay)}</div><div class="line">${escapeHtml(bank.digitableLine)}</div></div>
<div class="payment"><div class="cell"><span>Local de pagamento</span><strong>PAGÁVEL EM QUALQUER BANCO ATÉ O VENCIMENTO</strong></div>${cell('Vencimento', escapeHtml(formatCivil(adjustment.due)), 'right')}</div>
<div class="payment"><div class="cell"><span>Beneficiário</span><strong>${beneficiary}</strong><div class="benef-address">${escapeHtml(beneficiaryAddress)}</div></div>${cell('Agência / Código do Beneficiário', escapeHtml(bank.agencyCode), 'right')}</div>
<div class="grid row5">${cell('Data do documento', currentDate)}${cell('Número do documento', documentNumber)}${cell('Espécie doc.', 'DM')}${cell('Aceite', 'A')}${cell('Data processamento', currentDate)}</div>
<div class="grid row5">${cell('Uso do banco / Carteira', escapeHtml(bank.wallet))}${cell('Espécie', 'R$')}${cell('Quantidade', '')}${cell('Valor', '')}${cell('(=) Valor documento', `R$ ${escapeHtml(displayedAmount)}`, 'right amount')}</div>
<div class="instructions"><div class="text"><strong>Instruções (texto de responsabilidade do beneficiário)</strong>${instructions}</div><div class="side">${cell('(-) Desconto / Abatimento', '')}${cell('(-) Outras deduções', '')}${cell('(+) Mora / Multa', adjustment.lateFee ? `R$ ${escapeHtml(money(adjustment.lateFee))}` : '')}${cell('(+) Outros acréscimos', '')}${cell('(=) Valor cobrado', `R$ ${escapeHtml(displayedAmount)}`, 'right amount')}</div></div>
<div class="payer"><span>Pagador</span><strong>${payer}</strong><br>${payerAddress}<br>${payerCity}</div>
<div class="avalist"><div><strong>Sacador/Avalista</strong>${avalistName ? `<br>${escapeHtml(avalistName)}${avalistDocument ? ` (${escapeHtml(avalistDocument)})` : ''}<br>${escapeHtml(avalistAddress)}` : ''}</div><div>Autenticação mecânica / Ficha de Compensação</div></div>
<div class="barcode-wrap">${barcodeHtml(bank.barcode)}</div>
<div class="meta-note">Código de barras: ${escapeHtml(bank.barcode)} · Vencimento original: ${escapeHtml(formatCivil(originalDue))}</div>
</div></main>
</body></html>`;
}
TS_BOLETO

cat > apps/boleto_api/test/boleto.test.ts <<'TS_TEST'
import test from 'node:test';
import assert from 'node:assert/strict';
import { BoletoError, buildBankDocument, factorDueDate, renderBoleto, type BoletoRow } from '../src/boleto.js';

const base: BoletoRow = {
  Dat_Venc: '25/08/2026',
  Val_total: 790.72,
  Juros_mora_dia: 3.16,
  EMPRESA: 'MIXC',
  Nom_Razao_Social: 'CLIENTE TESTE LTDA',
  Cgc_Cpf_Cliente: '12345678000199',
  BENEF_NOME: 'ZAMBONI COMERCIAL LTDA',
  BENEF_CNPJ: '12345678000100'
};

const fixtures: Array<[string, BoletoRow, string, string]> = [
  ['Banco do Brasil', { ...base, Cod_Banco: '001', Num_Nosso_Num: '37626630002777359', Cod_Agencia: '1234', Cod_Cedente: '56789012-3' }, '00191154900000790720000003762663000277735917', '00190.00009 03762.663007 02777.359171 1 15490000079072'],
  ['Bradesco', { ...base, Cod_Banco: '237', Num_Nosso_Num: '12345678901', Cod_Agencia: '1234-5', Cod_Cedente: '6829-2', Cod_Var_Carteira: '09' }, '23794154900000790721234091234567890100068290', '23791.23405 91234.567898 01000.682904 4 15490000079072'],
  ['Caixa', { ...base, Cod_Banco: '104', Num_Nosso_Num: '00123456123456789', Cod_Agencia: '1234', Cod_Cedente: '635918-3' }, '10495154900000790726359183123145641234567890', '10496.35913 83123.145647 12345.678903 5 15490000079072'],
  ['Itaú', { ...base, Cod_Banco: '341', Num_Nosso_Num: '12345678' }, '34191154900000790721091234567872938185671000', '34191.09123 34567.872931 81856.710009 1 15490000079072'],
  ['Santander', { ...base, Cod_Banco: '033', Num_Nosso_Num: '1234567890123', Cod_Agencia: '123', Cod_Cedente: '01234567' }, '03397154900000790729123456712345678901230009', '03399.12347 56712.345679 89012.300094 7 15490000079072'],
  ['C6', { ...base, Cod_Banco: '336', Num_Nosso_Num: '012345678901', Cod_Agencia: '1234', Cod_Cedente: '123456789012-3', Cod_Var_Carteira: '109' }, '33691154900000790721234567890121234567890094', '33691.23454 67890.121238 45678.900940 1 15490000079072'],
  ['Safra', { ...base, Cod_Banco: '422', Num_Nosso_Num: '123456789', Cod_Agencia: '123', Cod_Cedente: '012345678901' }, '42291154900000790727123123456789011234567892', '42297.12312 23456.789017 12345.678929 1 15490000079072'],
  ['Daycoval', { ...base, Cod_Banco: '707', Num_Nosso_Num: '12345678901', Cod_Agencia: '1234', Cod_Cedente: '1234567-8' }, '70791154900000790721234121160150112345678901', '70791.23415 21160.150112 23456.789017 1 15490000079072'],
  ['Sicoob', { ...base, Cod_Banco: '756', Num_Nosso_Num: '12345678', Cod_Agencia: '1234', Cod_Cedente: '1234567', Cod_Var_Carteira: '001' }, '75697154900000790721123401123456712345678001', '75691.12340 01123.456715 23456.780016 7 15490000079072'],
  ['Votorantim', { ...base, Cod_Banco: '655', Num_Nosso_Num: '1234567890', Cod_Agencia: '123', Cod_Cedente: '123456789-0' }, '65594154900000790720000001732500123456789000', '65590.00002 01732.500127 34567.890008 4 15490000079072'],
  ['Citi', { ...base, EMPRESA: 'ZAMB', Cod_Banco: '745', Num_Nosso_Num: '123456789012', Cod_Agencia: '123', Cod_Cedente: '012345678-9' }, '74599154900000790723112123456789123456789012', '74593.11218 23456.789124 34567.890123 9 15490000079072'],
  ['HSBC', { ...base, EMPRESA: 'ZAMB', Cod_Banco: '399', Num_Nosso_Num: '12345678901' }, '39991154900000790721234567890106092028879001', '39991.23452 67890.106098 20288.790015 1 15490000079072']
];

test('mantém o novo ciclo do fator de vencimento da FEBRABAN', () => {
  assert.equal(factorDueDate('22/02/2025'), 1000);
  assert.equal(factorDueDate('25/08/2026'), 1549);
});

for (const [name, row, barcode, line] of fixtures) {
  test(`gera ${name} com os mesmos cálculos do PHP legado`, () => {
    const boleto = buildBankDocument(row);
    assert.equal(boleto.barcode, barcode);
    assert.equal(boleto.digitableLine, line);
  });
}

test('mantém bancos exclusivos da Zamboni protegidos por empresa', () => {
  assert.throws(
    () => buildBankDocument({ ...base, Cod_Banco: '399', Num_Nosso_Num: '12345678901' }),
    (error: unknown) => error instanceof BoletoError && error.status === 422
  );
});

test('renderização é autônoma e não referencia o PHP via2', () => {
  const html = renderBoleto({
    ...base,
    Dat_Venc: '25/08/2030',
    Cod_Banco: '001',
    Num_Nosso_Num: '37626630002777359',
    Cod_Agencia: '1234',
    Cod_Cedente: '56789012-3'
  });
  assert.match(html, /Visualização do boleto/);
  assert.match(html, /window\.print\(\)/);
  assert.doesNotMatch(html, /via2|cobi\.php|LEGACY_BOLETO/i);
});
TS_TEST

rm -f apps/boleto_api/src/legacy-boleto.ts
rm -f apps/boleto_api/test/legacy-boleto.test.ts
rm -f cobi.php

echo
echo "== Validando patch TypeScript =="
pnpm --filter boleto_api test
pnpm typecheck
pnpm build
git diff --check

echo
echo "Patch aplicado e validado. Revise com:"
echo "  git status --short"
echo "  git diff --stat"
echo
echo "Depois faça o commit:"
echo "  git add -A"
echo "  git commit -m \"refactor: gera boletos nativamente em typescript\""
echo "  git push"
echo
echo "No servidor: git pull && pnpm build && pnpm pm2:restart"
echo "LEGACY_BOLETO_BASE_URL não é mais utilizado."
