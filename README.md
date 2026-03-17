# Website institucional (MVP)

Este diretório contém um website moderno de Web Hosting em formato estático.

## Ficheiros

- `index.html` — página principal
- `styles.css` — estilos responsivos
- `script.js` — interações leves (ano automático + smooth scroll)

## Como abrir localmente

Abre `index.html` diretamente no browser.

## Ajustes recomendados antes de produção

1. Substituir `NovaHost` pela tua marca.
2. Trocar `billing.teudominio.com` pelos teus URLs reais do FOSSBilling.
3. Atualizar email de suporte em `index.html`.
4. Ajustar preços e planos para os teus produtos reais.

## Deploy em Windows Server 2022 + Plesk

1. Criar domínio/subdomínio no Plesk.
2. Fazer upload dos 3 ficheiros (`index.html`, `styles.css`, `script.js`) para o `httpdocs`.
3. Ativar SSL no domínio.
4. Confirmar links para o billing (`https://billing.teudominio.com`).
