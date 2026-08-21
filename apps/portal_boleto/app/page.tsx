'use client';

import Image from 'next/image';
import { useEffect, useState } from 'react';
import type { FormEvent, ReactNode } from 'react';

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:3331';

const BANK_IMAGES: Record<string, string> = {
  '001': '/img/lg-brasil.jpg',
  '033': '/img/lg-sant.jpg',
  '104': '/img/lg-cx.jpg',
  '237': '/img/lg-bra.jpg',
  '336': '/img/lg-c6.jpg',
  '341': '/img/lg-i.jpg',
  '399': '/img/lg-hsb.jpg',
  '422': '/img/lg-safra.jpg',
  '655': '/img/lg-vot.jpg',
  '707': '/img/lg-day.jpg',
  '745': '/img/lg-citi.jpg',
  '756': '/img/lg-sic.jpg'
};

type IconName = 'arrow' | 'building' | 'calendar' | 'check' | 'document' | 'lock' | 'logout' | 'phone' | 'shield';

interface PortalData {
  cliente: {
    razaoSocial: string;
    cnpj: string;
    endereco: string;
    bairro: string;
    cep: string;
    municipio: string;
    uf: string;
    telefone: string;
  };
  resumo: {
    quantidade: number;
    total: string;
    atualizadoEm: string;
  };
  titulos: Array<{
    nossoNumero: string;
    empresa: string;
    banco: { code: string; name: string };
    emissao: string;
    vencimento: string;
    notasFiscais: string;
    valor: string;
    moraDia: string;
  }>;
}

function Icon({ name }: { name: IconName }) {
  const paths: Record<IconName, ReactNode> = {
    arrow: <><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></>,
    building: <><path d="M3 21h18"/><path d="M6 21V7l6-4 6 4v14"/><path d="M9 10h.01M15 10h.01M9 14h.01M15 14h.01M9 18h.01M15 18h.01"/></>,
    calendar: <><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 11h18"/></>,
    check: <path d="m5 12 4 4L19 6"/>,
    document: <><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6M8 13h8M8 17h5"/></>,
    lock: <><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></>,
    logout: <><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5M21 12H9"/></>,
    phone: <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.79 19.79 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.69 2.8a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.9.33 1.84.56 2.8.69A2 2 0 0 1 22 16.92Z"/>,
    shield: <path d="M20 13c0 5-3.5 7.5-8 9-4.5-1.5-8-4-8-9V5l8-3 8 3v8Z"/>
  };
  return <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">{paths[name]}</svg>;
}

function formatCnpjInput(value: string): string {
  const clean = value.toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 14);
  return [
    clean.slice(0, 2),
    clean.length > 2 ? `.${clean.slice(2, 5)}` : '',
    clean.length > 5 ? `.${clean.slice(5, 8)}` : '',
    clean.length > 8 ? `/${clean.slice(8, 12)}` : '',
    clean.length > 12 ? `-${clean.slice(12, 14).replace(/\D/g, '')}` : ''
  ].join('');
}

async function api<T>(path: string, options: RequestInit = {}): Promise<T> {
  const headers = new Headers(options.headers);
  headers.set('Content-Type', 'application/json');
  const response = await fetch(`${API_URL}${path}`, {
    ...options,
    credentials: 'include',
    headers
  });
  const body = await response.json() as T & { erro?: string };
  if (!response.ok) throw new Error(body.erro || 'Não foi possível concluir a solicitação.');
  return body;
}

function Header({ authenticated, onLogout }: { authenticated: boolean; onLogout: () => void }) {
  return (
    <header className="topbar">
      <a className="brand" href="#inicio" aria-label="Zamboni Portal de Boletos">
        <Image className="brand-logo" src="/img/zamboni-novo.webp" alt="Zamboni" width={320} height={115} priority />
        <span className="brand-divider"></span>
        <strong>Portal de boletos</strong>
      </a>
      <div className="topbar-actions">
        <div className="secure"><Icon name="shield"/><span>Ambiente seguro</span></div>
        {authenticated && <button className="logout" onClick={onLogout}><Icon name="logout"/>Sair</button>}
      </div>
    </header>
  );
}

function Login({ onSuccess }: { onSuccess: (data: PortalData) => void }) {
  const [cnpj, setCnpj] = useState('');
  const [senha, setSenha] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setLoading(true);
    setError('');
    try {
      onSuccess(await api<PortalData>('/api/consulta', {
        method: 'POST', body: JSON.stringify({ cnpj, senha })
      }));
    } catch (requestError: unknown) {
      setError(requestError instanceof Error ? requestError.message : 'Não foi possível concluir a solicitação.');
    } finally {
      setLoading(false);
    }
  }

  return (
    <main id="inicio" className="login-page">
      <section className="hero-copy">
        <span className="eyebrow">Autoatendimento Zamboni</span>
        <h1>Seus boletos,<br/><em>sem complicação.</em></h1>
        <p>Consulte títulos em aberto e acompanhe vencimentos em poucos segundos.</p>
        <div className="benefits">
          <div><span><Icon name="check"/></span><p><strong>Consulta rápida</strong><small>Todos os seus títulos em um só lugar</small></p></div>
          <div><span><Icon name="shield"/></span><p><strong>Acesso protegido</strong><small>Seus dados tratados com segurança</small></p></div>
          <div><span><Icon name="document"/></span><p><strong>Informações claras</strong><small>Acompanhe valores e dados de cobrança</small></p></div>
        </div>
      </section>

      <section className="login-card" aria-labelledby="login-title">
        <div className="card-icon"><Icon name="document"/></div>
        <span className="eyebrow">Área do cliente</span>
        <h2 id="login-title">Consulte seus boletos</h2>
        <p className="card-intro">Informe os dados de acesso enviados pela Zamboni.</p>
        <form onSubmit={submit}>
          <label htmlFor="cnpj">CNPJ</label>
          <div className="input-wrap"><Icon name="building"/><input id="cnpj" value={cnpj} onChange={(event) => setCnpj(formatCnpjInput(event.target.value))} placeholder="00.000.000/0000-00" maxLength={18} autoComplete="username" required /></div>
          <label htmlFor="senha">Senha</label>
          <div className="input-wrap"><Icon name="lock"/><input id="senha" type="password" value={senha} onChange={(event) => setSenha(event.target.value.slice(0, 6))} placeholder="Digite sua senha" maxLength={6} autoComplete="current-password" required /></div>
          {error && <div className="form-error" role="alert">{error}</div>}
          <button className="primary-button" disabled={loading}>{loading ? 'Consultando…' : 'Consultar boletos'}{!loading && <Icon name="arrow"/>}</button>
        </form>
        <div className="help"><Icon name="phone"/><span>Precisa de ajuda?<strong>(32) 3462-0072</strong></span></div>
      </section>
    </main>
  );
}

function Results({ data }: { data: PortalData }) {
  return (
    <main className="results-page">
      <section className="welcome">
        <div><span className="eyebrow">Olá, cliente</span><h1>{data.cliente.razaoSocial}</h1><p>{data.cliente.cnpj}</p></div>
        <div className="address"><strong>{data.cliente.endereco} · {data.cliente.bairro}</strong><span>{data.cliente.cep} · {data.cliente.municipio}/{data.cliente.uf}{data.cliente.telefone && ` · ${data.cliente.telefone}`}</span></div>
      </section>

      <section className="summary-grid" aria-label="Resumo financeiro">
        <article><span className="summary-icon red"><Icon name="document"/></span><div><small>Títulos disponíveis</small><strong>{data.resumo.quantidade}</strong></div></article>
        <article><span className="summary-icon gold">R$</span><div><small>Total em aberto</small><strong>R$ {data.resumo.total}</strong></div></article>
        <article><span className="summary-icon blue"><Icon name="calendar"/></span><div><small>Última atualização</small><strong>{data.resumo.atualizadoEm || '—'}</strong></div></article>
      </section>

      <section className="titles-section">
        <div className="section-heading"><div><span className="eyebrow">Cobranças</span><h2>Seus títulos</h2><p>Acompanhe os detalhes das cobranças disponíveis.</p></div><span className="count-pill">{data.titulos.length} {data.titulos.length === 1 ? 'título' : 'títulos'}</span></div>
        <div className="title-list">
          {data.titulos.map((title) => (
            <article className="title-card" key={`${title.empresa}-${title.nossoNumero}`}>
              <div className="bank"><div className="bank-logo">{BANK_IMAGES[title.banco.code] ? <Image src={BANK_IMAGES[title.banco.code]} alt={title.banco.name} width={150} height={40} /> : title.banco.name}</div><span className="available"><i></i>Disponível</span></div>
              <div className="number"><small>Nosso número</small><strong>{title.nossoNumero}</strong><span>{title.empresa}</span></div>
              <div className="title-meta"><div><small>Emissão</small><strong>{title.emissao}</strong></div><div><small>Vencimento</small><strong>{title.vencimento}</strong></div><div><small>Nota(s) fiscal(is)</small><strong>{title.notasFiscais || '—'}</strong></div></div>
              <div className="amount"><small>Valor</small><strong>R$ {title.valor}</strong><span>Mora/dia: R$ {title.moraDia}</span></div>
            </article>
          ))}
          {!data.titulos.length && <div className="empty">Nenhum título disponível para visualização.</div>}
        </div>
      </section>

      <section className="support"><div className="support-icon"><Icon name="phone"/></div><div><small>Não encontrou o título que procura?</small><strong>Fale com o Setor de Cobrança</strong></div><a href="tel:+553234620072">(32) 3462-0072 <Icon name="arrow"/></a></section>
    </main>
  );
}

export default function Home() {
  const [data, setData] = useState<PortalData | null>(null);
  const [checking, setChecking] = useState(true);

  useEffect(() => {
    api<PortalData>('/api/session').then(setData).catch(() => {}).finally(() => setChecking(false));
  }, []);

  async function logout() {
    await api<{ status: string }>('/api/logout', { method: 'POST' }).catch(() => {});
    setData(null);
  }

  return (
    <div className="site-shell">
      <Header authenticated={Boolean(data)} onLogout={logout}/>
      {checking ? <main className="loading-page"><div className="spinner"></div><span>Carregando portal…</span></main> : data ? <Results data={data}/> : <Login onSuccess={setData}/>} 
      <footer><span>© {new Date().getFullYear()} Zamboni Comercial Ltda.</span><span>Desenvolvido por <a href="https://nivel3ti.com.br" target="_blank" rel="noopener noreferrer">Nível 3 TI</a></span></footer>
    </div>
  );
}
