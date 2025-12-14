# Sistema POS para Restaurante

Sistema de punto de venta para restaurantes desarrollado con PHP, MySQL y JavaScript vanilla.

## Requisitos
- PHP 7.4+
- MySQL 5.7+
- Apache/Nginx
- XAMPP/WAMP (desarrollo local)

## Instalación

1. Clonar repositorio
2. Copiar `.env.example` a `.env` y configurar
3. Crear base de datos: `mysql -u root -p < sql/schema.sql`
4. Configurar virtual host apuntando a `/public`
5. Acceder a `http://restaurant.local`

## Estructura
```
restaurant-pos/
├── config/         # Configuración
├── app/            # Lógica de aplicación (MVC)
├── public/         # Archivos públicos (punto de entrada)
├── storage/        # Logs y uploads
└── sql/            # Scripts de base de datos
```

## Arquitectura

- **Patrón MVC:** Separación de responsabilidades
- **Front Controller:** `public/index.php` como punto único de entrada
- **Singleton:** Conexión única a base de datos
- **Security by Design:** Validaciones, prepared statements, CSRF protection

## Autor
Tu nombre
