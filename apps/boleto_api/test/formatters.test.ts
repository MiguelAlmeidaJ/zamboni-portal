import test from 'node:test';
import assert from 'node:assert/strict';
import { bankInfo, formatCnpj, formatDate, formatDateTime, isValidCnpjShape, normalizeCnpj } from '../src/formatters.js';

test('normaliza e formata CNPJ alfanumérico', () => {
  const value = normalizeCnpj('12.ABC.345/01DE-35');
  assert.equal(value, '12ABC34501DE35');
  assert.equal(formatCnpj(value), '12.ABC.345/01DE-35');
  assert.equal(isValidCnpjShape(value), true);
});

test('exige os dois dígitos finais numéricos', () => {
  assert.equal(isValidCnpjShape('12ABC34501DEAB'), false);
});

test('identifica o banco pelo código cadastrado', () => {
  assert.deepEqual(bankInfo('237'), { code: '237', name: 'Bradesco' });
});

test('preserva a data civil de vencimento', () => {
  assert.equal(
    formatDate(new Date('2026-08-25T00:00:00.000Z')),
    '25/08/2026'
  );
});

test('converte Data_Geracao de UTC para horario de Sao Paulo', () => {
  assert.equal(
    formatDateTime(new Date('2026-08-21T18:40:00.000Z')),
    '21/08/2026, 15:40'
  );
});