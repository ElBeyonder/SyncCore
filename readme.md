# SyncCore 🔄

**Sincronización de bases de datos inteligente y no destructiva**

[![Licencia MIT](https://img.shields.io/badge/Licencia-MIT-blue.svg)](https://opensource.org/licenses/MIT)
![Versión](https://img.shields.io/badge/Versión-1.0.0-brightgreen)

SyncCore es una solución open source para sincronizar estructuras de bases de datos MySQL/MariaDB de manera segura y controlada, preservando los datos existentes y mostrando cambios detallados.

## ✨ Características Principales

- **Sincronización no destructiva**  
  Modifica solo lo necesario sin eliminar datos existentes

- **Detección inteligente de diferencias**  
  Compara:
    - Columnas faltantes/modificadas
    - Tipos de datos
    - Restricciones (NULL, DEFAULT, etc.)
    - Posición de columnas
    - Comentarios

- **Modo seguro integrado**  
  Conversión automática de datos antes de cambios críticos

- **Multiplataforma**  
  Funciona con cualquier entorno que soporte PHP (próximamente más lenguajes)

- **Reporte detallado**  
  Muestra todos los cambios realizados y posibles errores

- **Extensible**  
  Arquitectura modular para añadir nuevos adaptadores

## 🚀 Casos de Uso

- Actualizar bases de producción sin perder datos
- Sincronizar entornos de desarrollo/staging
- Migraciones controladas entre versiones
- Auditoría de estructuras de bases de datos

## 📦 Requisitos

- PHP 7.4+
- MySQL 5.7+ o MariaDB 10.3+
- Extensión mysqli habilitada

## 👨‍💻 Creado por

Este proyecto fue creado por Andrés Felipe Chantre (TheLastBeyonder),
creador de JELODA y CEO y creador de Ondersoft.

- [JELODA](https://www.jeloda.com)
- [ONDERSOFT](https://www.ondersoft.com.co)
- [QUILICHAO DIGITAL](https://quilichao.digital)
- [KHRONOTIK](https://khronotik.com)


## 🛠 Instalación

1. Clonar repositorio:
```bash
https://github.com/ElBeyonder/SyncCore.git
