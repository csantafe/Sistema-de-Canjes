<?php
// Seguridad básica
if ( ! defined( 'ABSPATH' ) ) exit;

class CC_Database {

    /**
     * Crea o actualiza las tablas personalizadas sin el prefijo wp_
     */
    public static function crear_tablas_ocultas() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        /**
         * TABLA DE SOCIOS (Actualizada)
         * - cedula_titular: ID del socio principal.
         * - nombre_titular: Nombre del socio principal (para el certificado).
         * - identificacion: ID del beneficiario (o del titular si es el mismo).
         * - nombre_completo: Nombre de la persona de esta fila.
         * - email: Correo electrónico para notificaciones.
         */
        $sql_socios = "CREATE TABLE datos_socios_x79q (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            cedula_titular varchar(50) NOT NULL,
            nombre_titular varchar(150) NOT NULL,
            identificacion varchar(50) DEFAULT '' NOT NULL,
            nombre_completo varchar(150) NOT NULL,
            email varchar(100) DEFAULT '' NOT NULL,
            PRIMARY KEY  (id),
            KEY cedula_titular (cedula_titular)
        ) $charset_collate;";

        dbDelta( $sql_socios );

        // La tabla de historial se mantiene igual, ya que ya contemplaba los datos necesarios
        $sql_historial = "CREATE TABLE registro_canjes_h9k2 (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            cedula_titular varchar(50) NOT NULL,
            club_id bigint(20) NOT NULL,
            fecha_inicio date NOT NULL,
            fecha_fin date NOT NULL,
            dias_consumidos int(11) NOT NULL,
            ano_solicitud int(4) NOT NULL,
            beneficiarios_viajeros text DEFAULT '' NOT NULL,
            fecha_registro datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY cedula_titular (cedula_titular)
        ) $charset_collate;";

        dbDelta( $sql_historial );
    }
}