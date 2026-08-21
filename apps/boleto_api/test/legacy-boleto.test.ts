import test from 'node:test';
import assert from 'node:assert/strict';
import { rendererForLegacyBoleto, rewriteLegacyAssets } from '../src/legacy-boleto.js';

test('seleciona o renderer legado pelo banco', () => {
  assert.equal(rendererForLegacyBoleto('001', 'MIXC'), 'cobBrasil.php');
  assert.equal(rendererForLegacyBoleto('237', 'ZAMB'), 'cobBradesco.php');
  assert.equal(rendererForLegacyBoleto('422', 'ZAMB'), 'cobSafra.php');
});

test('restringe bancos exclusivos da Zamboni', () => {
  assert.equal(rendererForLegacyBoleto('399', 'MIXC'), null);
  assert.equal(rendererForLegacyBoleto('399', 'ZAMB'), 'cobHSBC.php');
});

test('reescreve imagens do PHP para o proxy autenticado', () => {
  const html = '<img src="./img/lg-brasil.jpg"><style>x{background:url(./img/bar-p.png)}</style>';
  assert.equal(
    rewriteLegacyAssets(html),
    '<img src="/api/boleto-asset?file=lg-brasil.jpg"><style>x{background:url(/api/boleto-asset?file=bar-p.png)}</style>'
  );
});
