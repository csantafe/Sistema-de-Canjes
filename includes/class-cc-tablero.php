<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class CC_Tablero {

    public function __construct() {
        add_action('admin_menu', array($this, 'agregar_menu_tablero'));
    }

    public function agregar_menu_tablero() {
        add_submenu_page(
            'edit.php?post_type=convenio', 
            'Tablero de Canjes Solicitados', 
            'Ver Solicitudes', 
            'manage_options', 
            'cc-tablero-canjes', 
            array($this, 'pantalla_tablero')
        );
    }

    public function pantalla_tablero() {
        global $wpdb;

        // Consultamos los últimos 100 canjes realizados, ordenados por fecha de registro (el más reciente primero)
        $registros = $wpdb->get_results("SELECT * FROM registro_canjes_h9k2 ORDER BY fecha_registro DESC LIMIT 100");

        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">Historial de Canjes Solicitados</h1>
            <p>A continuación se muestran las solicitudes de canje realizadas por los socios a través del formulario web.</p>

            <table class="wp-list-table widefat fixed striped posts" style="margin-top: 20px;">
                <thead>
                    <tr>
                        <th style="font-weight: bold; width: 15%;">Fecha de Solicitud</th>
                        <th style="font-weight: bold; width: 12%;">Cédula Socio</th>
                        <th style="font-weight: bold; width: 20%;">Socio Titular</th>
                        <th style="font-weight: bold; width: 20%;">Club Destino</th>
                        <th style="font-weight: bold; width: 10%;">Desde</th>
                        <th style="font-weight: bold; width: 10%;">Hasta</th>
                        <th style="font-weight: bold; width: 13%;">Beneficiarios</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( $registros ) : ?>
                        <?php foreach ( $registros as $reg ) : 
                            // Buscamos el nombre del socio en la tabla de socios para que el tablero sea más informativo
                            $nombre_socio = $wpdb->get_var($wpdb->prepare(
                                "SELECT nombre_titular FROM datos_socios_x79q WHERE cedula_titular = %s LIMIT 1", 
                                $reg->cedula_titular
                            ));
                            $club_nombre = get_the_title($reg->club_id);
                        ?>
                        <tr>
                            <td><?php echo date('d/m/Y h:i A', strtotime($reg->fecha_registro)); ?></td>
                            <td><strong><?php echo esc_html($reg->cedula_titular); ?></strong></td>
                            <td><?php echo esc_html($nombre_socio ?: 'No encontrado en BD'); ?></td>
                            <td><span class="dashicons dashicons-location" style="font-size:16px; margin-right:5px;"></span><?php echo esc_html($club_nombre); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($reg->fecha_inicio)); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($reg->fecha_fin)); ?></td>
                            <td>
                                <small style="color: #666;">
                                    <?php echo esc_html($reg->beneficiarios_viajeros ?: 'Solo titular'); ?>
                                </small>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 20px;">No se han encontrado solicitudes de canje registradas aún.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <p style="margin-top: 20px; font-style: italic; color: #777;">
                * El tablero muestra las últimas 100 solicitudes procesadas.
            </p>
        </div>
        <?php
    }
}   