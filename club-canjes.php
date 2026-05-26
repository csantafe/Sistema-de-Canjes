<?php
/**
 * Plugin Name: Sistema de Canjes de Club
 * Description: Plugin personalizado para gestionar convenios, base de datos de socios en tablas seguras y emisión de certificados en PDF con QR.
 * Version: 1.0.0
 * Author: Asistente de Programación
 */

// Regla de seguridad: Evita que alguien acceda a este archivo directamente desde el navegador
if ( ! defined( 'ABSPATH' ) ) exit;

// 1. Incluimos los archivos
require_once plugin_dir_path( __FILE__ ) . 'includes/class-cc-database.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-cc-convenios.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-cc-importador.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-cc-formulario.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-cc-configuracion.php'; 
require_once plugin_dir_path( __FILE__ ) . 'includes/class-cc-verificacion.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-cc-tablero.php';

register_activation_hook( __FILE__, array( 'CC_Database', 'crear_tablas_ocultas' ) );

function cc_iniciar_plugin() {
    new CC_Convenios();
    new CC_Importador();
    new CC_Formulario();
    new CC_Configuracion();
    new CC_Verificacion();
    new CC_Tablero();
}
add_action( 'plugins_loaded', 'cc_iniciar_plugin' );

/**
 * 2. Hook de Activación:
 * Cuando hagas clic en "Activar" en WordPress, ejecutará la función para crear las tablas
 */
register_activation_hook( __FILE__, array( 'CC_Database', 'crear_tablas_ocultas' ) );

// Le decimos a WordPress que ejecute nuestra función iniciadora
add_action( 'plugins_loaded', 'cc_iniciar_plugin' );