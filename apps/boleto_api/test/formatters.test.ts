import test from 'node:test';
import assert from 'node:assert/strict';
import { encryptLegacy, formatCnpj, isValidCnpjShape, normalizeCnpj } from '../src/formatters.js';

test('normaliza e formata CNPJ alfanumérico como o PHP legado', () => {
  const value = normalizeCnpj('12.ABC.345/01DE-35');
  assert.equal(value, '12ABC34501DE35');
  assert.equal(formatCnpj(value), '12.ABC.345/01DE-35');
  assert.equal(isValidCnpjShape(value), true);
});

test('exige os dois dígitos finais numéricos', () => {
  assert.equal(isValidCnpjShape('12ABC34501DEAB'), false);
});

test('gera hash compatível e escapado para o boleto legado', () => {
  assert.equal(encryptLegacy('123#237#ZAMB'), 'Y2lpVmhra1mQcX95');
});
