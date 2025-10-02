<?php
/**
 * Configuración simple de autenticación basada en sesión/cookie
 * No requiere base de datos.
 *
 * Cambia USERNAME y PASSWORD_HASH a tus valores.
 * Para establecer la contraseña: puedes dejar PASSWORD_PLAIN y su hash se calculará en runtime
 * si PASSWORD_HASH está vacío. Es preferible usar un hash ya generado.
 */

class AuthConfig {
    // Usuario permitido
    public const USERNAME = 'admin';

    // Opción 1: define aquí el hash de la contraseña (password_hash)
    // Genera uno con PHP: password_hash('tu_clave', PASSWORD_DEFAULT)
    public const PASSWORD_HASH = '';

    // Opción 2: contraseña en texto plano (solo para desarrollo local); se ignorará si PASSWORD_HASH no está vacío
    public const PASSWORD_PLAIN = 'cambia-esta-clave';

    // Nombre de la sesión
    public const SESSION_NAME = 'antipro_login';

    // Cookie "recuérdame"
    public const REMEMBER_COOKIE = 'antipro_remember';
    public const REMEMBER_LIFETIME_DAYS = 30; // días

    // Clave para firmar el token de remember-me (no es criptografía fuerte, pero suficiente para uso personal local)
    public const REMEMBER_SECRET = 'pon-aqui-una-clave-larga-y-secreta';

    // Ruta base para redirecciones (ajusta si la app no está en /antiprocastrinacion)
    public const BASE_PATH = '/antiprocastrinacion';
}
