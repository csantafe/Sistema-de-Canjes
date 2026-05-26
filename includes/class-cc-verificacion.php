<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class CC_Verificacion {

    public function __construct() {
        add_action( 'init', array( $this, 'interceptar_verificacion' ) );
    }

    public function interceptar_verificacion() {
        if ( isset( $_GET['cc_verificar'] ) && isset( $_GET['h'] ) ) {
            
            $id = intval( $_GET['cc_verificar'] );
            $hash_recibido = sanitize_text_field( $_GET['h'] );
            
            // Recreamos el hash para validar que nadie intentó adivinar números
            $hash_esperado = substr(md5($id . 'clubmilitar_secreto'), 0, 8);
            
            if ( $hash_recibido === $hash_esperado ) {
                $this->mostrar_pantalla_verificacion( $id );
            } else {
                // Alerta de seguridad: El hash no coincide, enviamos ID 0 para forzar el estado Falso
                $this->mostrar_pantalla_verificacion( 0 ); 
            }
            exit; 
        }
    }

    private function mostrar_pantalla_verificacion( $registro_id ) {
        global $wpdb;
        
        // Buscamos el registro. Si es 0 o un ID inventado, devolverá nulo.
        $registro = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM registro_canjes_h9k2 WHERE id = %d", $registro_id ) );
        
        // VARIABLES DE ESTADO POR DEFECTO (Asumimos que es falso hasta demostrar lo contrario)
        $estado = 'falso';
        $mensaje = '¡ALERTA! DOCUMENTO FALSO O QR ALTERADO';
        
        if ( $registro ) {
            // EL DOCUMENTO ES AUTÉNTICO (Existe en la base de datos)
            $club_nombre = get_the_title( $registro->club_id );
            $socio = $wpdb->get_row( $wpdb->prepare( "SELECT nombre_titular FROM datos_socios_x79q WHERE cedula_titular = %s LIMIT 1", $registro->cedula_titular ) );
            $nombre_titular = $socio ? $socio->nombre_titular : 'Socio Desconocido';
            
            date_default_timezone_set('America/Bogota');
            $fecha_actual = date('Y-m-d');
            
            // Verificamos únicamente la vigencia de las fechas
            if ( $fecha_actual >= $registro->fecha_inicio && $fecha_actual <= $registro->fecha_fin ) {
                $estado = 'valido';
                $mensaje = 'Certificado Auténtico y Vigente';
            } elseif ( $fecha_actual < $registro->fecha_inicio ) {
                $estado = 'pendiente';
                $mensaje = 'Certificado Auténtico (Aún no inicia)';
            } else {
                $estado = 'vencido';
                $mensaje = 'Certificado Auténtico pero VENCIDO';
            }
        }

        // HTML DE LA PANTALLA MÓVIL
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Verificación Oficial</title>
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f7f6; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; padding: 20px; box-sizing: border-box; }
                .card { background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); max-width: 400px; width: 100%; text-align: center; border-top: 6px solid #003366; }
                
                /* Estilo especial cuando el documento es FALSO */
                .card.falso { border-top: 6px solid #c0392b; background-color: #fff5f5; }
                
                .logo { max-width: 140px; margin-bottom: 20px; }
                .status-icon { font-size: 60px; margin-bottom: 10px; }
                
                .valido .status-icon { color: #2ecc71; }
                .vencido .status-icon { color: #e67e22; }
                .pendiente .status-icon { color: #f39c12; }
                .falso .status-icon { color: #c0392b; font-size: 80px; } /* Ícono más grande para alertas */
                
                h1 { font-size: 20px; color: #333; margin: 0 0 10px; text-transform: uppercase; letter-spacing: 1px;}
                .mensaje { font-weight: bold; font-size: 18px; margin-bottom: 25px; padding: 15px; border-radius: 5px; }
                
                .valido .mensaje { color: #1e8449; background: #eafaf1; }
                .vencido .mensaje { color: #b9770e; background: #fdf2e9; }
                .pendiente .mensaje { color: #9c640c; background: #fef5e7; }
                .falso .mensaje { color: #fff; background: #c0392b; border: 2px solid #922b21; } /* Fondo rojo intenso */
                
                .detalle { text-align: left; background: #f9f9f9; padding: 20px; border-radius: 8px; font-size: 14px; color: #444; line-height: 1.6; margin-bottom: 20px; border: 1px solid #eee;}
                .detalle strong { color: #000; }
                .footer { font-size: 12px; color: #888; border-top: 1px solid #ddd; padding-top: 15px; }
            </style>
        </head>
        <body>
            <div class="card <?php echo $estado; ?>">
                <?php 
                $logo = get_option('cc_pdf_logo', '');
                if ($logo) echo '<img src="'.esc_url($logo).'" class="logo" alt="Logo de la Institución">';
                ?>
                
                <div class="status-icon">
                    <?php 
                    if ($estado == 'valido') echo '✓';
                    elseif ($estado == 'falso') echo '⚠';
                    elseif ($estado == 'vencido') echo '⏱';
                    else echo '⏳';
                    ?>
                </div>
                
                <h1>Verificador de Canjes</h1>
                <div class="mensaje"><?php echo $mensaje; ?></div>
                
                <?php if ( $registro ) : ?>
                <div class="detalle">
                    <strong>Titular:</strong> <?php echo esc_html( $nombre_titular ); ?><br>
                    <strong>Cédula:</strong> <?php echo esc_html( $registro->cedula_titular ); ?><br>
                    <strong>Club Destino:</strong> <?php echo esc_html( $club_nombre ); ?><br>
                    <strong>Vigencia:</strong> Del <?php echo date('d/m/Y', strtotime($registro->fecha_inicio)); ?> al <?php echo date('d/m/Y', strtotime($registro->fecha_fin)); ?><br>
                    <hr style="border: 0; border-top: 1px dashed #ccc; margin: 10px 0;">
                    <strong>Beneficiarios:</strong><br> <?php echo esc_html( $registro->beneficiarios_viajeros ?: 'Viaja solo el titular' ); ?>
                </div>
                <?php else : ?>
                <div class="detalle" style="text-align: center; color: #c0392b;">
                    No se encontraron registros de este documento en la base de datos oficial. <strong>Por favor, retenga el documento y notifique a la administración.</strong>
                </div>
                <?php endif; ?>
                
                <div class="footer">
                    Plataforma Oficial de Seguridad<br>
                    Consulta procesada el: <?php echo date('d/m/Y h:i A'); ?>
                </div>
            </div>
        </body>
        </html>
        <?php
    }
}