# Adivina el Color · API de ranking

![CI/CD](https://github.com/Sozap9/adivina-el-color-api/actions/workflows/ci.yml/badge.svg)

Backend en PHP para el ranking online de **Adivina el color**, un juego web en el que tienes que encontrar el color que corresponde a un código RGB. Cada partida se guarda en MySQL y el ranking mundial se mantiene en Redis, en directo.

- 🎮 **Jugar:** https://sozap9.github.io/AdivinaElColor/
- 🔌 **API:** https://adivina-api-55014307816.europe-west1.run.app/
- 🎨 **Repo del juego:** https://github.com/Sozap9/AdivinaElColor

## Arquitectura

```
 ┌──────────────────┐  fetch   ┌──────────────────────────┐  PDO + TLS  ┌──────────────────────────┐
 │ Juego            │ ───────▶ │ API · PHP 8.3 + Slim 4   │ ──────────▶ │ MySQL (TiDB Cloud)       │
 │ HTML, CSS y JS   │          │ Docker · Google Cloud Run│             │ historial de partidas    │
 │ en GitHub Pages  │ ◀─────── │                          │ ──────────▶ ├──────────────────────────┤
 └──────────────────┘   JSON   └──────────────────────────┘ Predis + TLS│ Redis (Upstash)          │
                                                                        │ ranking (sorted set)     │
                                                                        └──────────────────────────┘
```

Cuando termina una partida, el juego envía la puntuación a `POST /scores`. La API valida los datos, guarda la partida en MySQL y actualiza el ranking en Redis. El top 10 se lee de Redis con `GET /leaderboard`.

## Tecnologías

| Pieza | Para qué | Por qué |
| --- | --- | --- |
| PHP 8.3 + Slim 4 | API REST | Microframework con rutas, middleware y respuestas PSR-7 |
| MySQL | Historial completo de partidas | Datos permanentes y consultas por jugador |
| Redis | Ranking | Los sorted sets mantienen el ranking siempre ordenado |
| Docker | Mismo entorno en local y en la nube | Dockerfile multietapa (`dev` y `prod`) y docker compose |
| Google Cloud Run | Alojamiento de la API | Escala a cero cuando no hay tráfico |
| GitHub Actions | CI/CD | Tests en cada push y despliegue automático si pasan |
| PHPUnit | Tests | Validación de las puntuaciones |
| Postman | Pruebas manuales | Colección incluida en `postman/` |

## Endpoints

| Método | Ruta | Descripción | Respuestas |
| --- | --- | --- | --- |
| GET | `/` | Información de la API | 200 |
| GET | `/health` | Estado de la API | 200 |
| POST | `/scores` | Guarda una partida | 201, 400 |
| GET | `/leaderboard` | Top 10 del ranking | 200 |
| GET | `/players/{name}` | Últimas 10 partidas y mejor puntuación de un jugador | 200, 404 |

Ejemplo:

```bash
curl -X POST https://adivina-api-55014307816.europe-west1.run.app/scores \
  -H "Content-Type: application/json" \
  -d '{"player": "Diego", "points": 90, "hits": 3}'
```

```json
{ "id": 1, "player": "Diego", "points": 90, "hits": 3 }
```

## Decisiones técnicas

- **Ranking en un sorted set de Redis.** `ZADD leaderboard GT puntos jugador` guarda la mejor puntuación de cada jugador en una sola operación, y `ZREVRANGE` devuelve el top 10 ya ordenado, sin ordenar en cada petición.
- **MySQL como fuente de verdad.** Guarda todas las partidas. Si el ranking de Redis se perdiera, se podría reconstruir desde MySQL.
- **Validación en una clase propia (`ScoreInput`)** con constructor privado: un `ScoreInput` solo existe si sus datos son válidos. Los tests encontraron un caso real: un nombre enviado como array se guardaba como `"Array"`.
- **Consultas preparadas con PDO** para evitar la inyección SQL.
- **Conexiones cifradas (TLS)** con MySQL y Redis en la nube.
- **CORS restringido** al dominio del juego en producción, con preflight `OPTIONS`.
- **Secretos fuera del código y de la imagen.** La configuración llega por variables de entorno, y `.dockerignore` impide que los archivos de credenciales entren en la imagen.
- **Errores sin detalles en producción** (`APP_ENV=production`) y sin la cabecera `X-Powered-By`.
- **Despliegue sin claves.** GitHub Actions se autentica en Google Cloud con Workload Identity Federation: no hay ninguna clave guardada que se pueda filtrar, y solo este repositorio puede desplegar.

## Arrancar en local

Requisitos: [Docker Desktop](https://www.docker.com/products/docker-desktop/).

```bash
git clone https://github.com/Sozap9/adivina-el-color-api.git
cd adivina-el-color-api
docker compose run --rm api composer install
docker compose up -d
```

La API queda en http://localhost:8080 con MySQL y Redis en sus propios contenedores. La tabla `scores` se crea sola a partir de `database/init.sql`.

```bash
curl http://localhost:8080/health
```

Tests:

```bash
docker compose run --rm api composer test
```

Para probar todos los endpoints, importa en Postman la colección de la carpeta `postman/` y usa el entorno **Local**.

## Despliegue

Cada `push` a `main` ejecuta el workflow [`.github/workflows/ci.yml`](.github/workflows/ci.yml):

1. **test:** instala las dependencias y ejecuta PHPUnit.
2. **deploy** (solo si los tests pasan): construye la imagen `prod`, la sube a Artifact Registry y la despliega en Cloud Run.

Variables de entorno en producción: `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`, `DB_SSL`, `REDIS_URL` y `CORS_ORIGIN`.

## Próximas mejoras

- Calcular los puntos en el servidor a partir de los aciertos y el modo, en vez de confiar en el número que envía el juego.
- Limitar las peticiones por IP para evitar abusos.
- Script para reconstruir el ranking de Redis desde MySQL.
- Sustituir el servidor integrado de PHP por un servidor web de producción (por ejemplo, Nginx con PHP-FPM).

## Autor

Diego Pazos · [GitHub](https://github.com/Sozap9)
