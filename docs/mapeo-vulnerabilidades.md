# Mapeo de Vulnerabilidades — Billetera Digital SecDevOps

Tabla consolidada de las 13 vulnerabilidades OWASP Top 10 implementadas en V1 
y su remediacion correspondiente en V2. Cada fila referencia el comentario 
`SEC-FIX #N` presente en el codigo fuente de V2.

---

| # | Categoria OWASP | Endpoint | Vulnerabilidad en V1 | Remediacion en V2 |
|---|---|---|---|---|
| 1 | A03:2025 – Injection | `login.php` | Concatenacion directa de `email`/`password` en la query SQL, permite bypass de autenticacion | Prepared statements con PDO (`:email`) |
| 2 | A07:2025 – Identification and Authentication Failures | `registro.php` | Sin politica de complejidad de contrasena, password en texto plano | Validacion de longitud/mayuscula/numero + `password_hash()` bcrypt |
| 3 | A01:2025 – Broken Access Control (IDOR) | `index.php` | `user_id` tomado de `$_GET`, permite ver saldo/datos de otro usuario | `user_id` siempre derivado de `$_SESSION` |
| 4 | A01:2025 – Broken Access Control | `transferir.php` | `cuenta_origen_id` tomado del formulario, permite transferir dinero de cuentas ajenas | Cuenta origen derivada de la sesion; transaccion atomica con `FOR UPDATE` |
| 5 | A03:2025 – Injection | `historial.php` | Filtro de busqueda concatenado en `LIKE`, SQLi post-autenticacion | Prepared statement con bind de parametro |
| 6 | A04:2025 – Insecure Design / Unrestricted Upload | `perfil.php` (antes `subir_foto.php`, consolidado en el panel de perfil) | Sin validar tipo real de archivo, permite subir `.php` y lograr RCE; archivo servido directo desde `uploads/` | Validacion MIME por magic bytes, whitelist, nombre aleatorio, almacenamiento fuera del webroot; imagen servida via `ver_foto.php` con `basename()` para prevenir Path Traversal |
| 7 | A03:2025 – Injection | `exportar.php` | Uso de `system()` con input de usuario en generacion de reportes | Whitelist estricta de formatos, generacion via funciones nativas de PHP, sin invocar shell |
| 8 | A03:2025 – Injection (XSS) | `transferir.php` / `historial.php` | Campo `nota` renderizado sin escapar, XSS almacenado y reflejado | `htmlspecialchars()` en cada punto de salida |
| 9 | A07:2025 – Identification and Authentication Failures | `recuperar_password.php` | Token predecible (`md5(email+hora)`), sin expiracion, mostrado en pantalla | Token con `random_bytes(32)`, expiracion de 15 min, nunca se muestra |
| 10 | A01:2025 – Broken Access Control | `admin/panel.php` | Solo valida sesion activa, no valida rol. Ademas incluye gestion de roles (`cambiar_rol`): cualquier usuario autenticado puede auto-promoverse a `admin` sin ninguna verificacion | Verificacion explicita `rol !== 'admin'` con `http_response_code(403)` antes de exponer la pagina y de procesar `cambiar_rol`; se protege ademas contra que un admin se auto-revoque el rol |
| 11 | A05:2025 – Security Misconfiguration | Global (`db.php`, `.env`, `php.ini`) | Credenciales hardcodeadas, `display_errors=On`, `.env` accesible por HTTP | Variables de entorno via Docker, errores logueados no mostrados, `.env` bloqueado por Nginx |
| 12 | A02:2025 – Cryptographic Failures | Tabla `usuarios`, cookies de sesion | Passwords en texto plano, cookies sin flags de seguridad | Bcrypt para passwords, cookies `secure`/`httponly`/`samesite=Strict` |
| 13 | A06:2025 – Vulnerable and Outdated Components | `composer.json` | PHPMailer 5.2.16 (CVE-2016-10033, RCE conocido) | PHPMailer `^6.9`, sin CVEs criticos conocidos |

---

## Vulnerabilidades adicionales identificadas

| Hallazgo | Endpoint | Categoria OWASP | Estado en V2 |
|---|---|---|---|
| XSS reflejado en parametro de busqueda | `historial.php?buscar=` | A03:2025 – Injection | Resuelto junto con #8 |
| Logging & Monitoring Failures | Global (tabla `logs`) | A09:2025 – Security Logging and Monitoring Failures | V1 nunca la llenaba; V2 registra login, transferencias, accesos admin e intentos fallidos via `logger.php` |
| Escalamiento de privilegios via CSRF | `admin/panel.php` (accion `cambiar_rol`) | A01:2025 – Broken Access Control | Presente en ambas versiones: el formulario de cambio de rol no usa token CSRF. En V1 el impacto es critico (cualquier usuario ya puede llegar al endpoint sin ser admin). En V2 el endpoint esta protegido por rol, pero un admin autenticado podria ser inducido a ejecutar la accion via un request forjado — se recomienda agregar token CSRF como mejora futura, fuera del alcance de las 13 vulnerabilidades originales |
| Path Traversal en visualizacion de fotos de perfil | `ver_foto.php` (V2) | A01:2025 – Broken Access Control | No explotable: la ruta se sanea con `basename()` antes de leer el archivo del volumen privado. Se documenta como control preventivo, no como hallazgo abierto |

---

## Resumen de severidad

| Severidad | Cantidad | Vulnerabilidades |
|---|---|---|
| Critica | 4 | #1, #6, #10, #12 |
| Alta | 6 | #2, #4, #5, #7, #9, #13 |
| Media | 3 | #3, #8, #11 |

## Cobertura OWASP Top 10:2025

Cubiertas: A01, A02, A03, A04, A05, A06, A07, A09.
No cubiertas explicitamente: A08 (Software and Data Integrity Failures), 
A10 (Server-Side Request Forgery) — mencionar como alcance futuro si la 
rubrica lo permite.

---

## Changelog de implementacion

Cambios introducidos durante la construccion del proyecto en el IDE, 
posteriores al diseño inicial documentado en este archivo:

- **`subir_foto.php` → `perfil.php`**: la subida de foto se consolido dentro 
  de un panel de perfil unificado (datos de cuenta + cambio de foto). El 
  vector de la VULN #6 se mantiene igual en V1.
- **`ver_foto.php` (nuevo, solo V2)**: endpoint dedicado para servir las 
  imagenes desde el volumen privado (`uploads_privados_v2`), ya que este 
  volumen no es accesible directamente por Nginx. Sanea la ruta con 
  `basename()` antes de leer el archivo.
- **Gestion de roles en `admin/panel.php`**: se agrego la posibilidad de 
  promover/degradar usuarios a `admin` desde el propio panel, en ambas 
  versiones. Ver hallazgo de CSRF arriba.
- **UI completa**: se agrego `css/style.css`, banners de identificacion de 
  version y navegacion consistente entre paginas — no afecta la superficie 
  de vulnerabilidades, solo mejora la usabilidad del laboratorio.
- **`docker-compose.yml` (raiz)**: se agregaron `network aliases` (`db` para 
  `v1-db`, `app` para `v2-app`) para que el codigo PHP y la configuracion de 
  Nginx (`fastcgi_pass app:9000`) sigan funcionando sin cambios al combinar 
  las tres piezas en un solo compose.
