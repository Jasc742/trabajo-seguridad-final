# Guia de Instalacion — Billetera Digital SecDevOps

Requisitos previos: Docker y Docker Compose instalados, OpenSSL disponible 
en el sistema (ya viene preinstalado en la mayoria de distros Linux/macOS; 
en Windows usar Git Bash o WSL).

---

## 1. Levantar V1 (vulnerable)

```bash
cd v1-vulnerable
docker compose up -d --build
docker compose ps
```

Accede a la aplicacion:
- App: **http://localhost:8080**
- phpMyAdmin: **http://localhost:8081** (user: `root`, password: `root123`)

Usuarios de prueba (login.php):

| Email | Password | Rol |
|---|---|---|
| admin@billetera.com | Admin123! | admin |
| jack@billetera.com | Jack2024! | user |
| maria@billetera.com | Maria2024! | user |

Detener:
```bash
docker compose down -v
```

---

## 2. Levantar V2 (remediada, con HTTPS)

El certificado SSL/TLS (`v2-remediada/nginx/certs/cert.pem` y `key.pem`) ya 
viene generado en este repositorio, listo para usar. Si prefieres regenerarlo:

```bash
cd v2-remediada/nginx/certs
openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
  -keyout key.pem \
  -out cert.pem \
  -subj "/C=PE/ST=Huanuco/L=TingoMaria/O=UNAS/OU=SecDevOps/CN=localhost"
```

### 2.1 Configurar variables de entorno

```bash
cd v2-remediada
cp .env.example .env
```

Edita `.env` y define claves reales:
```env
DB_PASSWORD=CambiaEstaClaveSegura2026!
DB_ROOT_PASSWORD=OtraClaveSeguraDistinta2026!
```

### 2.2 Usuarios de prueba

El `init.sql` de V2 ya incluye los hashes bcrypt reales y funcionales para 
estos usuarios (mismas credenciales que V1, pero ahora hasheadas):

| Email | Password | Rol |
|---|---|---|
| admin@billetera.com | Admin123! | admin |
| jack@billetera.com | Jack2024! | user |
| maria@billetera.com | Maria2024! | user |

Si quieres regenerar los hashes tu mismo (por ejemplo con otras contrasenas), 
usa este comando dentro del contenedor una vez levantado:
```bash
docker compose exec app php -r "echo password_hash('TuNuevaPass1', PASSWORD_BCRYPT) . PHP_EOL;"
```
Y actualiza el registro correspondiente:
```sql
UPDATE usuarios SET password = '<hash_generado>' WHERE email = '...';
```

### 2.3 Levantar los contenedores

```bash
docker compose up -d --build
```

Accede a la aplicacion:
- **https://localhost:8443** (certificado autofirmado — el navegador mostrara 
  advertencia, es esperado; acepta el riesgo para continuar)
- http://localhost:8090 redirige automaticamente a HTTPS

Detener:
```bash
docker compose down -v
```

---

## 3. Levantar ambos entornos en paralelo

V1 usa los puertos 8080/8081/3306. V2 usa 8443/8090. No hay conflicto entre 
ellos, asi que puedes tener ambos corriendo simultaneamente para comparar 
comportamiento en vivo durante la sustentacion. Hay dos formas de hacerlo:

### Opcion A — Compose combinado (recomendado, un solo comando)

Desde la **raiz del proyecto** existe un `docker-compose.yml` que levanta 
las 6 piezas (V1: app+db+phpmyadmin, V2: nginx+app+db) en un solo paso, 
cada una en su propia red Docker aislada:

```bash
# Asegurate de tener v2-remediada/.env ya creado (paso 2.1)
docker compose up -d --build
docker compose ps
```

Para detener todo:
```bash
docker compose down -v
```

### Opcion B — Compose independientes

Si prefieres levantar solo una version o mantenerlas totalmente separadas, 
cada carpeta conserva su propio `docker-compose.yml` funcional:

```bash
cd v1-vulnerable && docker compose up -d --build   # solo V1
cd v2-remediada  && docker compose up -d --build   # solo V2
```

---

## 4. Troubleshooting comun

| Problema | Causa probable | Solucion |
|---|---|---|
| `Connection refused` al abrir la app | Contenedor `db` aun inicializando | Esperar ~10s y refrescar; revisar `docker compose logs db` |
| Certificado no confiable en navegador | Autofirmado (esperado) | Aceptar riesgo, o usar `mkcert` para evitarlo |
| Login no funciona en V2 | `.env` no configurado o hashes no coinciden | Revisar paso 2.1 y 2.2 |
| Error 502 en Nginx | PHP-FPM (`app`) no esta listo aun | `docker compose restart nginx` |
| Puerto ocupado | Otro servicio usando 8080/8443/3306/8090 | Cambiar el mapeo de puertos en `docker-compose.yml` |
| Foto de perfil no se muestra en V2 (`perfil.php`) | El volumen `uploads_privados_v2` no fue creado o el contenedor `app` no tiene permisos sobre el | Revisar `docker compose logs app`; confirmar que el volumen existe con `docker volume ls` |

---

## 5. Generar certificado con mkcert (alternativa sin advertencia del navegador)

```bash
mkcert -install
mkcert -key-file v2-remediada/nginx/certs/key.pem \
       -cert-file v2-remediada/nginx/certs/cert.pem \
       localhost 127.0.0.1
docker compose -f v2-remediada/docker-compose.yml up -d --build
```
