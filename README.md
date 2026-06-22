# TechMind — Panel administrativo

Panel admin del sistema TechMind. PHP MVC + MySQL, empaquetado para Dokploy con Docker.

## Stack

- PHP 8.2 + Apache (`php:8.2-apache`)
- MySQL 8.0 (servicio compartido en Dokploy)
- Sesiones PHP nativas
- Extensiones: TCPDF para PDFs, mPDF, DataTables

## Variables de entorno

Copia `.env.example` a `.env` y ajusta:

```
DB_HOST=mysql
DB_PORT=3306
DB_NAME=bd_techmind
DB_USER=techmind
DB_PASS=cambiame
```

En Dokploy, define estas variables en la configuración de la app (no commitees `.env`).

## Build local

```bash
docker build -t techmind-panel .
docker run --rm -p 8080:80 \
  -e DB_HOST=host.docker.internal \
  -e DB_USER=root -e DB_PASS= \
  techmind-panel
```

Abre `http://localhost:8080`.

## Despliegue en Dokploy

1. Crea un servicio MySQL 8.0 en Dokploy con volumen persistente y red `techmind-net`. Importa `bd_techmind.sql` (no incluido en este repo).
2. Crea un volumen nombrado `techmind-uploads`.
3. Crea esta aplicación apuntando a este repo. En la configuración:
   - **Network**: `techmind-net`
   - **Volume mount**: `techmind-uploads` → `/var/www/html/vistas/img`
   - **Env vars**: las del `.env.example` con los valores reales.
   - **Dominio**: `panel.tudominio.com` (configurable en Dokploy).

El volumen `techmind-uploads` se monta también en el repo `pagina-nueva` para compartir las imágenes de productos y publicaciones entre ambas apps.

## Estructura

```
panel-nuevo/
├── Dockerfile
├── docker/                  # apache + php config
├── index.php                # entry point
├── ajax/                    # endpoints AJAX
├── controladores/           # controladores MVC
├── modelos/                 # modelos PDO (incluye conexion.php con getenv)
├── vistas/                  # vistas + assets
└── extensiones/             # tcpdf, logs
```

## Notas

- La conexión BD se lee de variables de entorno (`getenv()` en `modelos/conexion.php`).
- Los uploads (`vistas/img/`) deben mapearse a un volumen persistente en producción.
