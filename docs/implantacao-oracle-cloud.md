# Implantação na Oracle Cloud

Este guia descreve como colocar o sistema em produção na Oracle Cloud seguindo as especificações atuais do projeto.

## 1. Premissas da arquitetura de produção

A implantação esperada para produção é esta:

- API Laravel rodando em `php8.2-fpm` nativo.
- Nginx nativo fazendo o papel de servidor HTTP.
- MySQL e Redis instalados na própria VM e acessíveis por `127.0.0.1`.
- Frontend React compilado no CI e publicado como arquivos estáticos.
- Subdomínios separados para a SPA e para a API, por exemplo `app.seudominio.com` e `api.seudominio.com`.
- `VITE_API_URL` apontando para a API pública, por exemplo `https://api.seudominio.com/api`.

O projeto usa autenticação por token Bearer com Sanctum, então o frontend fala com a API por HTTP e não depende de sessão compartilhada entre domínios.

## 2. O que preparar na Oracle Cloud

Crie uma instância Linux na OCI, preferencialmente Ubuntu LTS.

Instale e configure:

- Nginx.
- PHP 8.2 FPM.
- Extensões PHP necessárias para Laravel: `pdo_mysql`, `mbstring`, `exif`, `pcntl`, `bcmath`, `gd`, `zip` e `redis`.
- Composer.
- MySQL 8.
- Redis 7.
- Certificado SSL, de preferência com Let's Encrypt.

Se preferir separar responsabilidades, a mesma estrutura continua válida com banco gerenciado, mas o padrão documentado no projeto assume `DB_HOST=127.0.0.1` e `REDIS_HOST=127.0.0.1`.

## 3. DNS e domínios

Reserve os domínios ou subdomínios antes de publicar:

- `app.seudominio.com` para o frontend.
- `api.seudominio.com` para a API.

Apontamentos esperados:

- `app` → IP público da VM.
- `api` → IP público da VM.

Se usar um único domínio principal com subdomínios, configure o TLS para ambos.

## 4. Estrutura de diretórios na VM

Uma organização simples funciona bem:

- `/var/www/api` para o backend.
- `/var/www/app` para o build do frontend.
- `/etc/nginx/sites-available` e `/etc/nginx/sites-enabled` para os vhosts.

Você pode usar outro caminho, desde que os arquivos de configuração apontem para ele.

## 5. Variáveis de ambiente do backend

Crie o arquivo de produção da API com valores reais.

Exemplo base:

```env
APP_NAME="Mão Fechada"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.seudominio.com
APP_LOCALE=pt-BR
APP_FALLBACK_LOCALE=en

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pao_duro
DB_USERNAME=pao_duro
DB_PASSWORD=senha_forte

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=local
```

Depois, gere e guarde com segurança:

- `APP_KEY`
- credenciais do banco
- credenciais do Redis, se houver
- eventual segredo do CI/CD

## 6. Variáveis de ambiente do frontend

O frontend precisa apenas apontar para a API pública:

```env
VITE_API_URL=https://api.seudominio.com/api
```

Esse valor deve entrar no build do frontend antes da publicação.

## 7. Implantação do backend

Passo a passo recomendado:

1. Envie o código da API para a VM, por `git pull`, `rsync` ou artefato do CI.
2. Instale as dependências de produção com Composer.
3. Configure o `.env` de produção.
4. Gere a chave da aplicação se ainda não existir.
5. Rode as migrations em modo forçado.
6. Limpe caches antigos.
7. Aponte o Nginx para `api/public`.

Comandos típicos na pasta da API:

```bash
composer install --no-dev --optimize-autoloader
php artisan key:generate --force
php artisan migrate --force
php artisan optimize:clear
```

Se o projeto já tiver a `APP_KEY` definida no `.env` da produção, não gere outra chave sem necessidade.

## 8. Implantação do frontend

O frontend deve ser gerado no CI e publicado como arquivos estáticos.

Fluxo recomendado:

1. O CI instala dependências em `web/`.
2. O CI executa `npm run build` com `VITE_API_URL` apontando para a API pública.
3. O resultado de `web/dist` é enviado para a VM.
4. O Nginx do subdomínio `app` serve esse diretório.

Comandos locais equivalentes:

```bash
npm ci
npm run build
```

O build precisa terminar com a URL correta da API, porque o Vite injeta essa variável durante a compilação.

## 9. Configuração do Nginx

Use dois vhosts, um para a API e outro para a SPA.

### 9.1 API

O vhost da API deve:

- apontar para `/var/www/api/public`;
- encaminhar arquivos `.php` para `php8.2-fpm`;
- bloquear arquivos ocultos;
- permitir que `/api/v1/*` seja resolvido pelo Laravel.

O config local do projeto segue essa lógica em [docker/nginx/default.conf](../docker/nginx/default.conf).

### 9.2 SPA

O vhost do frontend deve:

- apontar para `/var/www/app/dist`;
- servir `index.html` como fallback para rotas do React Router;
- ativar compressão e cache de assets estáticos, se possível.

Exemplo mínimo:

```nginx
server {
    listen 80;
    server_name app.seudominio.com;
    root /var/www/app/dist;
    index index.html;

    location / {
        try_files $uri $uri/ /index.html;
    }
}
```

## 10. Banco e cache

O projeto assume os serviços locais da VM.

Banco de dados:

- MySQL na porta `3306`.
- Usuário e senha próprios para produção.
- Backup automático configurado fora da aplicação.

Cache e filas:

- Redis na porta `6379`.
- Laravel usando `CACHE_STORE=database` e `QUEUE_CONNECTION=database` conforme a configuração atual.

Se quiser usar Redis também para cache ou fila, ajuste as variáveis e valide o comportamento antes de liberar.

## 11. SSL e segurança

Depois que os vhosts estiverem funcionando em HTTP, ative TLS.

Checklist mínimo:

- certificado válido para `app` e `api`.
- redirecionamento de HTTP para HTTPS.
- portas públicas limitadas apenas ao necessário.
- acesso SSH restrito.
- arquivos `.env` fora do web root.
- permissões corretas em `storage/` e `bootstrap/cache/`.

## 12. Passos de verificação depois do deploy

Valide nesta ordem:

1. A URL da API responde em `/up`.
2. O login e o cadastro funcionam.
3. O dashboard carrega sem erro.
4. As categorias aparecem e podem ser criadas.
5. O extrato lista e cria lançamentos.
6. O perfil salva idioma e dados do usuário.
7. A página de assinatura carrega os planos.
8. O frontend consegue acessar a API sem erro de CORS ou URL.

## 13. Checklist de CI/CD recomendado

Se usar GitHub Actions, o pipeline ideal é:

- rodar testes do backend;
- rodar build do frontend;
- publicar o build da SPA;
- atualizar a API na VM;
- aplicar migrations;
- reiniciar PHP-FPM e recarregar Nginx.

Os segredos do pipeline devem incluir:

- credenciais SSH da VM;
- `APP_KEY` de produção;
- credenciais do banco;
- `VITE_API_URL` para o build do frontend.

## 14. Troubleshooting rápido

Se algo quebrar, verifique primeiro:

- `APP_URL` e `VITE_API_URL` apontando para os domínios corretos.
- DNS dos subdomínios.
- permissões do Laravel em `storage/`.
- logs do Nginx e do PHP-FPM.
- migrations aplicadas.
- frontend rebuildado depois de mudar `VITE_API_URL`.

## 15. Resumo prático

O deploy na Oracle Cloud, para este projeto, é basicamente isto:

1. publicar a API Laravel em PHP-FPM + Nginx;
2. instalar MySQL e Redis na própria VM;
3. compilar o frontend no CI;
4. servir o `dist/` da SPA no subdomínio `app`;
5. apontar a SPA para `https://api.seudominio.com/api`;
6. validar login, dashboard, categorias, extrato, perfil e assinatura.