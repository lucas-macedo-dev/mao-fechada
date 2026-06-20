# Guia de Implementação

Este arquivo é um manual prático para continuar evoluindo o projeto sem precisar conhecer React e TypeScript em profundidade.

## 1. Como pensar no projeto

O sistema é dividido em duas partes:

- `api/`: backend em Laravel, responsável por autenticação, regras de negócio e persistência.
- `web/`: frontend em React + TypeScript, responsável pela interface, formulários e consumo da API.

A ideia central é simples: o frontend mostra dados e dispara ações, e o backend valida, salva e devolve respostas padronizadas.

## 2. Fluxo mental para implementar uma nova funcionalidade

Quando quiser adicionar algo novo, siga esta ordem:

1. Descubra qual dado a tela precisa exibir ou enviar.
2. Verifique se a API já possui o endpoint necessário.
3. Se o endpoint não existir, crie a rota, controller, request e teste no backend.
4. No frontend, adicione a função no client de API.
5. Crie ou atualize o hook de React Query.
6. Monte a tela ou ajuste a existente.
7. Adicione traduções e estilos.
8. Rode build e, se fizer sentido, teste no navegador.

Se você seguir sempre esse fluxo, evita misturar regra de negócio com interface.

## 3. Estrutura do frontend

O ponto de entrada do frontend é [web/src/main.tsx](../web/src/main.tsx), que carrega estilos globais, Font Awesome e o componente principal da aplicação.

As rotas ficam em [web/src/Router.tsx](../web/src/Router.tsx):

- páginas públicas: login e cadastro;
- páginas protegidas: início, categorias, extrato, perfil e assinatura.

O layout autenticado fica em [web/src/components/Layout.tsx](../web/src/components/Layout.tsx), que decide entre sidebar no desktop e barra inferior no mobile.

## 4. Onde cada responsabilidade vive

### Interface

- Páginas: [web/src/pages](../web/src/pages)
- Componentes compartilhados: [web/src/components](../web/src/components)
- Estilos globais: [web/src/index.css](../web/src/index.css), [web/src/App.css](../web/src/App.css)
- Estilos por área: [web/src/styles](../web/src/styles)

### Dados e API

- Client HTTP: [web/src/services/api.ts](../web/src/services/api.ts)
- Hooks de dados: [web/src/hooks/api.ts](../web/src/hooks/api.ts)
- Tipos do contrato: [web/src/types/api.ts](../web/src/types/api.ts)

### Texto e idioma

- Traduções: [web/src/i18n/config.ts](../web/src/i18n/config.ts)
- O idioma atual fica salvo em `localStorage` com a chave `app_locale`.

## 5. Como adicionar uma nova tela

Se você quiser criar uma nova página, faça assim:

1. Crie o arquivo em `web/src/pages/NovaPagina.tsx`.
2. Use `useTranslation()` sempre que houver texto visível ao usuário.
3. Se precisar de dados da API, crie primeiro a função no client e o hook correspondente.
4. Registre a rota em [web/src/Router.tsx](../web/src/Router.tsx).
5. Se a tela fizer parte da área autenticada, envolva-a com `PrivateRoute` e `Layout`.
6. Inclua o texto novo em [web/src/i18n/config.ts](../web/src/i18n/config.ts).
7. Coloque o estilo específico em `web/src/styles/pages.css` ou em um arquivo novo de estilos, se a tela ficar grande.

### Exemplo mental

Para criar uma tela de relatórios, o caminho seria:

- backend expõe `/api/v1/reports`;
- `api.ts` ganha `getReports()`;
- `hooks/api.ts` ganha `useReports()`;
- `Router.tsx` registra `/reports`;
- `pages/ReportsPage.tsx` monta a interface;
- `i18n/config.ts` recebe as traduções;
- estilos entram em `pages.css`.

## 6. Como trabalhar com formulários

Os formulários do projeto seguem um padrão simples:

- guardam estado com `useState`;
- validam o básico antes de enviar;
- chamam um hook de mutação do React Query;
- tratam sucesso e erro com a resposta da API;
- atualizam a tela com invalidação de cache quando necessário.

Na prática, isso aparece em categorias, extrato, perfil e cadastro.

Regras úteis:

- nunca envie campos vazios se o backend não aceitar;
- se a API exigir confirmação de senha, mantenha o campo `password_confirmation`;
- se houver idioma ou preferência de usuário, persista no backend e no `localStorage` quando fizer sentido.

## 7. Como lidar com React Query

React Query é a camada que busca, cacheia e atualiza os dados.

Use queries para leitura e mutations para escrita:

- `useQuery` para listar categorias, transações, perfil, assinatura e dashboard;
- `useMutation` para criar, atualizar e deletar dados.

Depois de uma mutation bem-sucedida, invalide as queries afetadas. Por exemplo:

- criar categoria deve invalidar `categories`;
- criar transação deve invalidar `transactions` e `dashboard`;
- atualizar perfil deve atualizar `auth/me` e, se necessário, a assinatura.

## 8. Como trabalhar com TypeScript sem travar

TypeScript está aqui para evitar erro de contrato entre tela e API.

Pense assim:

- tipos descrevem a forma do dado;
- interfaces e aliases ajudam a documentar o que cada função espera;
- se o compilador reclamar, provavelmente o contrato mudou em algum lugar.

Boas práticas simples:

- prefira tipar payloads e respostas da API;
- não use `any` para escapar de erro;
- se o dado vem do backend, modele isso em `web/src/types/api.ts`;
- se um componente recebe props, escreva o tipo inline ou crie um alias pequeno.

## 9. Como atualizar textos e idiomas

Quando adicionar um texto novo na interface:

1. escolha uma chave estável, por exemplo `reports.title`;
2. crie a chave nos dois idiomas em [web/src/i18n/config.ts](../web/src/i18n/config.ts);
3. use `t('reports.title')` no componente.

Se o texto for uma lista fixa, como tipos de transação ou planos, também coloque o valor traduzido no mesmo arquivo.

## 10. Como trabalhar com ícones

O app usa Font Awesome.

Os ícones ficam globais em [web/src/main.tsx](../web/src/main.tsx) e a lógica de categorias fica em [web/src/constants/categoryIcons.ts](../web/src/constants/categoryIcons.ts).

Para mostrar um ícone:

- use a classe `fa-solid fa-...`;
- mantenha um ícone padrão quando o backend não devolver um específico;
- se for um conjunto reutilizável, centralize a lista em um arquivo de constantes.

## 11. Como testar uma mudança

Sempre que terminar uma alteração, tente seguir esta sequência:

1. rodar o build do frontend;
2. rodar os testes do backend quando a API mudar;
3. abrir a aplicação no navegador;
4. fazer o fluxo real da tela que você alterou.

Comandos mais úteis:

- `make up`
- `docker compose exec web npm run build`
- `docker compose exec api php artisan test`

## 12. Erros comuns para evitar

- colocar regra de negócio no componente React;
- esquecer de traduzir texto novo;
- atualizar a API e não atualizar os tipos do frontend;
- criar uma mutation sem invalidar o cache afetado;
- adicionar um campo no formulário sem validar o backend;
- mexer no layout mobile sem testar em tela pequena.

## 13. Regra prática para continuar sozinho

Se estiver em dúvida, pergunte sempre:

- esse dado nasce no frontend ou no backend?
- a mudança exige rota nova, apenas tela nova, ou as duas coisas?
- preciso traduzir isso?
- essa alteração precisa atualizar cache?

Se a resposta para uma dessas perguntas for sim, trate isso como parte obrigatória da entrega.