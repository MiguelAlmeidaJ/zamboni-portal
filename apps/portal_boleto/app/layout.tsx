import './globals.css';
import type { ReactNode } from 'react';

export const metadata = {
  title: 'Portal de Boletos | Zamboni',
  description: 'Consulte seus boletos e cobranças Zamboni.'
};

export default function RootLayout({ children }: Readonly<{ children: ReactNode }>) {
  return (
    <html lang="pt-BR">
      <body>{children}</body>
    </html>
  );
}
