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
