# Catalogue de détection de stack

Procédure de scan utilisée par `/stack`. Organisée par **couche**. Pour chaque couche : les fichiers à chercher, et le signal à en extraire. Ne lis que les fichiers réellement présents (un `Glob`/`find` d'abord). Chaque techno retenue doit citer le fichier qui la prouve.

> Règle d'or : si aucun fichier de cette liste n'atteste une techno, elle ne va **pas** dans la section « Détecté ». Soit c'est un trou à poser en question (Phase 3), soit ça n'existe pas dans ce projet.

## 1. Langages & runtimes

| Fichier | Signal |
|---|---|
| `.tool-versions` (asdf/mise) | Tous les langages + versions épinglés |
| `.nvmrc`, `.node-version` | Version Node |
| `.python-version`, `runtime.txt` | Version Python |
| `.ruby-version` | Version Ruby |
| `composer.json` → `require.php` | Version PHP |
| `go.mod` → `go X.Y` | Version Go |
| `Cargo.toml` → `edition`/`rust-version` | Rust |
| `package.json` → `engines.node` | Contrainte Node |
| `pyproject.toml` → `requires-python` | Contrainte Python |
| `pom.xml`, `build.gradle`, `.sdkmanrc` | Java/Kotlin + version |
| `Gemfile` → `ruby "..."` | Ruby |

Distingue la **contrainte déclarée** (`^8.2`) de la **version résolue** (lockfile). N'écris une version résolue que si tu as lu `composer.lock` / `package-lock.json` / `yarn.lock` / `pnpm-lock.yaml` / `Cargo.lock` / `go.sum`.

## 2. Backend

| Fichier | Signal |
|---|---|
| `composer.json` → `require` | `symfony/framework-bundle` (Symfony), `sylius/sylius` (Sylius), `laravel/framework` (Laravel), `api-platform/*` |
| `package.json` → `dependencies` | `express`, `fastify`, `@nestjs/*`, `next` (full-stack), `koa` |
| `requirements.txt` / `pyproject.toml` / `Pipfile` | `django`, `flask`, `fastapi` |
| `go.mod` | `gin-gonic/gin`, `labstack/echo`, `gofiber/fiber` |
| `Gemfile` | `rails`, `sinatra` |
| `pom.xml` / `build.gradle` | `spring-boot`, `quarkus`, `micronaut` |

Note aussi : l'**ORM / couche data** (`doctrine/orm`, `prisma`, `typeorm`, `sequelize`, SQLAlchemy, Eloquent), le **moteur de templates**, les **libs structurantes** (messenger/queue, auth, admin). Renvoie vers les skills `symfony:*` si stack Symfony/Sylius.

## 3. Frontend

| Fichier | Signal |
|---|---|
| `package.json` → `dependencies`/`devDependencies` | Framework : `react`, `vue`, `svelte`, `@angular/core`, `solid-js`. Méta-framework : `next`, `nuxt`, `@remix-run/*`, `astro`, `@sveltejs/kit` |
| `vite.config.*`, `webpack.config.*`, `rollup.config.*` | Bundler |
| `webpack.config.js` + `@symfony/webpack-encore` | Webpack Encore (Symfony) |
| `assets/` + `@hotwired/stimulus`, `@hotwired/turbo` | Stimulus/Turbo (Symfony UX) |
| `tailwind.config.*`, `postcss.config.*` | CSS : Tailwind, PostCSS, Sass (`.scss`), CSS Modules |
| `package.json` | State : `redux`, `zustand`, `pinia`, `@tanstack/query` ; UI kit : `@mui/*`, `antd`, `chakra-ui`, `bootstrap` |
| `tsconfig.json` | TypeScript actif |
| `angular.json`, `next.config.*`, `nuxt.config.*`, `svelte.config.*`, `astro.config.*` | Confirme le méta-framework |

## 4. Données & stockage

Souvent prouvé par `docker-compose.yml` (services) **et/ou** les DSN dans `.env` / `.env.dist` / `.env.example`.

| Source | Signal |
|---|---|
| `docker-compose*.yml` → `services.*.image` | `postgres`, `mysql`, `mariadb`, `redis`, `memcached`, `elasticsearch`, `opensearch`, `meilisearch`, `mongo`, `rabbitmq`, `kafka`, `minio` |
| `.env*` → `DATABASE_URL` | SGBD relationnel + version implicite |
| `.env*` → `REDIS_URL`, `REDIS_DSN` | Cache/sessions Redis |
| `.env*` → `MESSENGER_TRANSPORT_DSN`, `RABBITMQ_*`, `KAFKA_*` | Broker / file de messages |
| `.env*` → `MAILER_DSN` | Service mail |
| `.env*` → `*_S3_*`, `AWS_BUCKET`, `MINIO_*` | Stockage objet |
| deps backend | client confirmant le service (`predis/predis`, `doctrine/dbal`, `elasticsearch/elasticsearch`) |

Attention aux services docker en **profil dev** (`profiles: [dev]`) ou de test — ne pas les ranger en runtime de prod sans confirmation.

## 5. Ops / Infrastructure

| Fichier | Signal |
|---|---|
| `Dockerfile`, `*.dockerfile` | Conteneurisation, image de base, multi-stage |
| `docker-compose*.yml` | Orchestration locale |
| `*.tf`, `*.tfvars`, `.terraform/` | Terraform (IaC) — lire les `provider` (aws/google/azurerm) |
| `Pulumi.yaml` | Pulumi |
| `**/Chart.yaml`, `values*.yaml` | Helm / Kubernetes |
| `*.yaml` avec `kind:` + `apiVersion:` | Manifestes Kubernetes |
| `fly.toml` | Fly.io |
| `vercel.json` | Vercel |
| `netlify.toml` | Netlify |
| `Procfile`, `app.json` | Heroku |
| `app.yaml`, `cloudbuild.yaml` | Google App Engine / Cloud |
| `serverless.yml` | Serverless Framework (Lambda) |
| `.platform.app.yaml`, `.platform/` | Platform.sh |
| `captain-definition` | CapRover |
| `ansible/`, `*.yml` avec `hosts:`/`tasks:` | Ansible |
| `Vagrantfile` | Vagrant (dev) |

L'**hébergement de production réel** n'est souvent pas dans le dépôt → question en Phase 3.

## 6. DevOps / CI-CD / Qualité

| Fichier | Signal |
|---|---|
| `.github/workflows/*.yml` | GitHub Actions — lire les jobs (build, test, deploy, lint) |
| `.gitlab-ci.yml` | GitLab CI |
| `Jenkinsfile` | Jenkins |
| `.circleci/config.yml` | CircleCI |
| `azure-pipelines.yml` | Azure Pipelines |
| `bitbucket-pipelines.yml` | Bitbucket |
| `.drone.yml` | Drone |
| `Makefile`, `Taskfile.yml`, `justfile` | Commandes projet (build/test/QA) |
| `phpunit.xml*`, `jest.config.*`, `vitest.config.*`, `pytest.ini`, `tox.ini`, `playwright.config.*`, `cypress.config.*` | Frameworks de test |
| `phpstan.neon*`, `psalm.xml`, `.php-cs-fixer*.php`, `rector.php` | Analyse statique / style PHP |
| `.eslintrc*`, `eslint.config.*`, `.prettierrc*`, `biome.json` | Lint/format JS |
| `.rubocop.yml`, `ruff.toml`, `.flake8`, `mypy.ini` | Lint/types Ruby/Python |
| `.pre-commit-config.yaml`, `.husky/`, `lefthook.yml` | Hooks git |
| `renovate.json`, `.github/dependabot.yml` | Mises à jour de deps automatisées |

## 7. Monitoring / observabilité

Rarement un fichier dédié — surtout des deps + `.env` + question.

| Source | Signal |
|---|---|
| deps : `sentry/sentry`, `@sentry/*` ; `.sentryclirc` | Sentry |
| deps : `datadog`, `dd-trace` | Datadog |
| deps : `newrelic` | New Relic |
| `.env*` → `SENTRY_DSN`, `DD_*`, `NEW_RELIC_*` | Confirme le service |
| `prometheus.yml`, `grafana/` | Prometheus / Grafana |
| config logs (`monolog.yaml`, `winston`, `pino`) | Logging structuré |

Si rien : c'est un trou explicite à poser, pas une absence à supposer.

## Synthèse à produire

Range chaque techno trouvée sous sa couche, **avec sa source**. Sépare nettement runtime de prod et outillage de dev. Tout ce qui n'est attesté par aucun fichier devient une question (Phase 3), pas une supposition.
