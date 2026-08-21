import sql from 'mssql';
import { config } from './config.js';
import {
  bankInfo,
  ENABLED_BANKS,
  formatCep,
  formatCnpj,
  formatDate,
  formatDateTime,
  formatMoney,
  formatPhone
} from './formatters.js';

let poolPromise: Promise<sql.ConnectionPool> | undefined;

function pool(): Promise<sql.ConnectionPool> {
  if (!poolPromise) {
    poolPromise = new sql.ConnectionPool(config.database).connect().catch((error) => {
      poolPromise = undefined;
      throw error;
    });
  }
  return poolPromise;
}

function requestFor(poolConnection: sql.ConnectionPool, cnpj: string): sql.Request {
  return poolConnection.request().input('cnpj', sql.VarChar, cnpj);
}

export async function authenticate(cnpj: string, password: string): Promise<'ok' | 'not_found' | 'invalid_password'> {
  const connection = await pool();
  const result = await requestFor(connection, cnpj).query(
    'SELECT TOP 1 Senha_site FROM dbo.Boleto_Titulo_Ativo WHERE Cgc_Cpf_Cliente = @cnpj'
  );

  if (result.recordset.length !== 1) return 'not_found';
  return String(result.recordset[0].Senha_site) === String(password) ? 'ok' : 'invalid_password';
}

export async function customerPortal(cnpj: string) {
  const connection = await pool();
  const [titlesResult, totalResult, generatedResult] = await Promise.all([
    requestFor(connection, cnpj).query(`SELECT
      Nom_Razao_Social, Endereco, Bairro, Cep, Num_Telefone, Nom_Municipio,
      Sgl_Estado, Cod_Banco, Dat_Emissao, Dat_Venc, Num_Nota_Fiscal1,
      Num_Nota_Fiscal2, Num_Nosso_Num, Juros_mora_dia, Val_total, EMPRESA
      FROM dbo.Boleto_Titulo_Ativo
      WHERE Cgc_Cpf_Cliente = @cnpj
      ORDER BY Dat_Venc ASC`),
    requestFor(connection, cnpj).query(
      'SELECT SUM(Val_total) as Total FROM dbo.Boleto_Titulo_Ativo WHERE Cgc_Cpf_Cliente = @cnpj'
    ),
    requestFor(connection, cnpj).query(
      'SELECT Data_Geracao FROM dbo.Boleto_Titulo_Ativo WHERE Cgc_Cpf_Cliente = @cnpj'
    )
  ]);

  const rows = titlesResult.recordset;
  if (!rows.length) return null;
  const customer = rows[0];
  const titles = rows.filter((row) => ENABLED_BANKS.has(String(row.Cod_Banco).padStart(3, '0'))).map((row) => {
    const bank = bankInfo(row.Cod_Banco);
    const invoices = [row.Num_Nota_Fiscal1, row.Num_Nota_Fiscal2].filter((value) => String(value || '').trim());

    return {
      nossoNumero: String(row.Num_Nosso_Num || ''),
      empresa: String(row.EMPRESA || ''),
      banco: bank,
      emissao: formatDate(row.Dat_Emissao),
      vencimento: formatDate(row.Dat_Venc),
      notasFiscais: invoices.join(' / '),
      valor: formatMoney(row.Val_total),
      moraDia: formatMoney(row.Juros_mora_dia)
    };
  });

  return {
    cliente: {
      razaoSocial: String(customer.Nom_Razao_Social || ''),
      cnpj: formatCnpj(cnpj),
      endereco: String(customer.Endereco || ''),
      bairro: String(customer.Bairro || ''),
      cep: formatCep(customer.Cep),
      municipio: String(customer.Nom_Municipio || ''),
      uf: String(customer.Sgl_Estado || ''),
      telefone: String(customer.Num_Telefone || '').trim() ? formatPhone(customer.Num_Telefone) : ''
    },
    resumo: {
      quantidade: rows.length,
      total: formatMoney(totalResult.recordset[0]?.Total),
      atualizadoEm: formatDateTime(generatedResult.recordset[0]?.Data_Geracao)
    },
    titulos: titles
  };
}

export async function closePool(): Promise<void> {
  if (!poolPromise) return;
  const connection = await poolPromise;
  await connection.close();
  poolPromise = undefined;
}
