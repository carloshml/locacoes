# Deploy — Locações (HostGator)

Guia de deploy da aplicação Laravel na hospedagem compartilhada da HostGator.

## Ambiente do servidor

- **Acesso:** SSH na porta `2222`
  ```
  ssh -p 2222 -i C:\Users\<voce>\.ssh\id_rsa SEU_USUARIO@SEU_HOST.hostgator.com.br
  ```
- **PHP:** 8.3 (`/usr/local/bin/php`)
- **Composer:** NÃO instalado globalmente — usamos `composer.phar` local no projeto
- **Node/npm:** NÃO existem no servidor — o build do front-end é feito **localmente** e enviado por SCP
- **Banco:** MySQL (criado pelo cPanel → MySQL Databases; nome/usuario/senha ficam so no `.env` do servidor)
- **Projeto:** `~/repositories/locacoes` (vem do Git Version Control do cPanel)

## Como a aplicação é servida

- O domínio principal usa um **link simbólico**:
  `~/public_html -> ~/repositories/locacoes/public`
- A `public_html` original foi preservada em `~/public_html_backup`.
- O `.htaccess` dentro de `public/` roteia tudo para o `index.php` do Laravel.
- `APP_ENV=production` e `APP_DEBUG=false` no `.env`.

> Observação: o arquivo `.cpanel.yml` NÃO é usado neste modelo (não fazemos
> deploy copiando para `public_html` via cPanel). O deploy é manual via SSH.

## Como o front-end funciona (IMPORTANTE)

O servidor não tem Node, então **o Vite não roda em produção**. Servimos
apenas os arquivos já compilados:

1. Build local gera `public/build/` (manifest + CSS/JS minificados).
2. A pasta `public/build` está no `.gitignore`, então é enviada por **SCP**.
3. Em runtime, a diretiva `@vite(...)` no Blade lê o `public/build/manifest.json`
   e injeta as tags apontando para os assets em `public/build/assets`.

Consequência: **toda mudança de CSS/JS/Vue exige rebuild local + reenvio por SCP.**
`git pull` sozinho NÃO atualiza o front-end.

## Setup inicial (primeira vez) — executado no servidor

```bash
cd ~/repositories/locacoes

# 1. Composer local
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
php -r "unlink('composer-setup.php');"

# 2. Dependências de produção
php composer.phar install --no-dev --optimize-autoloader

# 3. Ambiente
cp .env.example .env
php artisan key:generate
nano .env   # ajustar APP_ENV, APP_DEBUG, APP_URL e credenciais do MySQL

# 4. Banco de dados
php artisan migrate --force
php artisan db:seed --force   # cria usuarios iniciais (trocar senhas depois)

# 5. Links e caches
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### `.env` de produção (trecho essencial)

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://SEU_DOMINIO

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=SEU_PREFIXO_banco
DB_USERNAME=SEU_PREFIXO_usuario
DB_PASSWORD=SUA_SENHA_FORTE
```

> NUNCA versione valores reais de `.env`. O `.env` ja esta no `.gitignore`.
> Os exemplos acima sao apenas placeholders.

## Apontar o domínio (uma vez)

```bash
cd ~
mv public_html public_html_backup
ln -s repositories/locacoes/public public_html
```

## Permissões (resolve erros 403 / 500)

Na HostGator as pastas vêm como `700` e o Apache (outro usuário) não as atravessa.

```bash
# atravessar o caminho ate a public
chmod 711 ~/repositories
chmod 711 ~/repositories/locacoes
chmod 755 ~/repositories/locacoes/public

# assets do front-end (apos cada SCP de public/build)
chmod 755 ~/repositories/locacoes/public/build
chmod 755 ~/repositories/locacoes/public/build/assets
chmod 644 ~/repositories/locacoes/public/build/assets/*
chmod 644 ~/repositories/locacoes/public/build/manifest.json

# Laravel precisa escrever
chmod -R 775 ~/repositories/locacoes/storage ~/repositories/locacoes/bootstrap/cache
```

Diagnóstico útil quando der 403 (mostra permissão de cada nível do caminho):
```bash
namei -l ~/public_html/index.php
```

## Atualizações futuras

### Mudanças só no BACK-END (PHP)

Local:
```
git add . && git commit -m "..." && git push origin main
```

Servidor (SSH):
```bash
cd ~/repositories/locacoes
git pull origin main
php artisan migrate --force      # se houver novas migrations
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Mudanças no FRONT-END (CSS / JS / Vue)

> IMPORTANTE: os 2 primeiros comandos rodam na SUA MAQUINA (PowerShell local);
> os `chmod` rodam no SERVIDOR (sessao SSH). O `chmod` e OBRIGATORIO: todo
> arquivo novo enviado por SCP entra com permissao restrita e o Apache nao
> consegue servi-lo (resulta em 403 no .js/.css novo).

**1) Na sua maquina — buildar e enviar:**
```bash
npm run build
scp -P 2222 -i C:\Users\<voce>\.ssh\id_rsa -r public\build SEU_USUARIO@SEU_HOST.hostgator.com.br:~/repositories/locacoes/public/
```

**2) No servidor (SSH) — liberar permissao dos novos assets:**
```bash
cd ~/repositories/locacoes && \
  find public/build -type d -exec chmod 755 {} \; && \
  find public/build -type f -exec chmod 644 {} \;
```
Este comando aplica a permissao correta recursivamente (755 nas pastas,
644 nos arquivos), independente de quantos/quais arquivos foram enviados.

**3) No navegador:** recarregar com `Ctrl+Shift+R` (ignora cache).

> O nome do arquivo JS muda a cada build (ex.: app-XXXX.js). Por isso sempre
> envie a pasta `build` inteira — o `manifest.json` referencia o nome correto.
> Se tambem mudou PHP/Blade, faca o fluxo de BACK-END (git pull + caches).

## Pendências de segurança

- Trocar a senha do usuário `admin@teste.com` (padrão `12345678`) ou criar
  usuário real e remover os de teste.
- Confirmar `APP_URL` com o domínio real (`grep APP_URL .env`) e rodar
  `php artisan config:cache` se alterar.
