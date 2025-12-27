# CyberCore – Alojamento Web & Soluções Digitais

Estrutura base (PHP 8, MySQL, HTML/CSS/JS) compatível com Plesk. Sem lógica de negócio, apenas skeleton organizado.

## Estrutura
- public/ — site público (index.php é o entrypoint)
- client/ — área de cliente
- admin/ — painel administrativo
- config/ — config.php, database.php (PDO)
- inc/ — includes seguros (bootstrap, futuramente helpers)
- assets/ — css, js, img
- api/ — endpoints futuros
- .htaccess — proteção de pastas sensíveis
- all_old/ — backup legado (mantido)

## Entrypoints
- public/index.php — homepage
- client/index.php — placeholder área de cliente
- admin/index.php — placeholder admin

## Configuração
Edite `config/config.php` com credenciais reais.

## Notas
- Não há lógica de negócio implementada.
- Ajuste a policy do .htaccess conforme o ambiente Plesk.
- Adicione rotas e controllers conforme necessário.
