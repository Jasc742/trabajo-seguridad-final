# Billetera Digital — Laboratorio SecDevOps

Proyecto academico que implementa una aplicacion web de billetera digital en 
dos versiones:

- **V1 (vulnerable)**: implementa deliberadamente 13 vulnerabilidades mapeadas 
  al OWASP Top 10, incluyendo SQL Injection y RCE.
- **V2 (remediada)**: misma aplicacion con todas las vulnerabilidades corregidas, 
  desplegada detras de un reverse proxy Nginx con HTTPS (TLS).

## Stack tecnologico

| Componente | Tecnologia |
|---|---|
| Backend | PHP 8.1 |
| Base de datos | MySQL 8.0 |
| Servidor web (V1) | Apache (embebido en la imagen PHP) |
| Servidor web (V2) | Nginx (reverse proxy) + PHP-FPM |
| TLS | OpenSSL (certificado autofirmado) |
| Contenedores | Docker + Docker Compose |
| Pentest automatizado | sqlmap |

## Estructura del repositorio

```
billetera-digital-secdevops/
├── README.md                    <- este archivo
├── ARQUITECTURA.md              <- diagrama y descripcion de arquitectura
├── GUIA_INSTALACION.md          <- como levantar V1 y V2
├── v1-vulnerable/               <- codigo fuente version vulnerable
├── v2-remediada/                <- codigo fuente version remediada (HTTPS)
├── pentest/                     <- guia y comandos de sqlmap
└── docs/
    └── mapeo-vulnerabilidades.md  <- tabla V1 -> V2 con las 13 vulnerabilidades
```

## Inicio rapido

### Opcion A — Ambas versiones a la vez (un solo comando, desde la raiz)

```bash
cp v2-remediada/.env.example v2-remediada/.env   # editar con tus propias claves
docker compose up -d --build
```

Levanta V1 en http://localhost:8080 y V2 en https://localhost:8443 
simultaneamente, cada una en su propia red Docker aislada.

### Opcion B — Cada version por separado

```bash
# V1 - vulnerable (HTTP, puerto 8080)
cd v1-vulnerable
docker compose up -d --build

# V2 - remediada (HTTPS, puerto 8443)
cd ../v2-remediada
cp .env.example .env   # editar con tus propias claves
docker compose up -d --build
```

Ver `GUIA_INSTALACION.md` para el detalle completo, incluyendo la generacion 
de hashes bcrypt y el certificado TLS.

## Advertencia

Este proyecto contiene codigo **intencionalmente vulnerable** (V1). No debe 
desplegarse en un entorno accesible desde internet ni usarse fuera de un 
laboratorio local aislado. Uso exclusivamente academico.
