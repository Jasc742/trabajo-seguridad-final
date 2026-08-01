# Arquitectura de Despliegue

## V1 — Vulnerable

```
Cliente (navegador)
      |
      | HTTP :8080 (sin TLS)
      v
+-------------------------------------------+
| Docker network: billetera_v1_net           |
|                                             |
|  app (Apache + PHP 8.1)                     |
|   - display_errors = On                     |
|   - uploads/ con chmod 777                  |
|        |                                    |
|        v                                    |
|  db (MySQL 8.0) :3306 <- expuesto al host   |
|   - credenciales debiles                    |
|                                              |
|  phpmyadmin :8081 <- expuesto al host       |
+-------------------------------------------+
```

Caracteristicas clave:
- Sin reverse proxy: Apache recibe el trafico directamente.
- Puerto de base de datos expuesto al host (mala practica intencional).
- Sin certificado TLS: todo el trafico viaja en texto plano.
- Carpeta `uploads/` ejecutable por Apache (vector de RCE).

## V2 — Remediada

```
Cliente (navegador)
      |
      | HTTPS :8443 (TLS 1.2/1.3)
      v
+---------------------------------------------------+
| Docker network: billetera_v2_net                    |
|                                                       |
|  nginx (reverse proxy)                                |
|   - cert.pem / key.pem (OpenSSL)                      |
|   - headers: HSTS, CSP, X-Frame-Options, etc.         |
|   - bloquea acceso a .env y ejecucion de .php en      |
|     /uploads/                                          |
|        |                                               |
|        | fastcgi :9000                                 |
|        v                                                |
|  app (PHP-FPM 8.1)                                       |
|   - prepared statements (PDO)                            |
|   - password_hash / password_verify (bcrypt)             |
|   - display_errors = Off                                 |
|        |                                                   |
|        v                                                    |
|  db (MySQL 8.0) <- puerto NO expuesto al host                |
|                                                                 |
|  volumen uploads_privados_v2 <- fuera del webroot              |
+---------------------------------------------------+
```

Caracteristicas clave:
- Nginx como reverse proxy, unico punto de entrada, termina TLS.
- HTTP (puerto 8090) redirige automaticamente a HTTPS.
- MySQL no expone su puerto al host — solo accesible dentro de la red Docker.
- Uploads de usuarios se almacenan fuera del `document root`, en un volumen 
  separado (`uploads_privados_v2`), inaccesibles via URL directa. Se sirven 
  unicamente a traves de `ver_foto.php`, que valida sesion, sanea la ruta 
  con `basename()` y valida el MIME real antes de devolver el archivo.
- Headers de seguridad HTTP activos (HSTS, CSP, X-Frame-Options, X-Content-Type-Options).

## Comparacion de superficie expuesta

| Aspecto | V1 | V2 |
|---|---|---|
| Protocolo | HTTP (8080) | HTTPS (8443), HTTP redirige |
| Puerto MySQL expuesto al host | Si (3306) | No |
| Reverse proxy | No | Si (Nginx) |
| Headers de seguridad | Ninguno | HSTS, CSP, X-Frame-Options, etc. |
| PHP visible en headers | Si (`expose_php`) | No |
| `.env` accesible por HTTP | Si | Bloqueado por Nginx |
| Ejecucion de PHP en `/uploads/` | Si | Bloqueada explicitamente |
