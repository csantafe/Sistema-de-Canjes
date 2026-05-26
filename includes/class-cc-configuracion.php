<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class CC_Configuracion {
    public function __construct() {
        add_action('admin_menu', array($this, 'agregar_menu_config'));
        add_action('admin_enqueue_scripts', array($this, 'cargar_scripts_media'));
    }

    public function agregar_menu_config() {
        add_submenu_page(
            'edit.php?post_type=convenio', 'Configuración de PDF', 'Ajustes del PDF',
            'manage_options', 'cc-config-pdf', array($this, 'pantalla_configuracion')
        );
    }

    public function cargar_scripts_media($hook) {
        if ($hook != 'convenio_page_cc-config-pdf') return;
        wp_enqueue_media(); 
    }

    public function pantalla_configuracion() {
        if (isset($_POST['cc_guardar_config'])) {
            update_option('cc_pdf_logo', sanitize_text_field($_POST['cc_pdf_logo']));
            update_option('cc_pdf_firma_admin', sanitize_text_field($_POST['cc_pdf_firma_admin']));
            update_option('cc_pdf_nombre_admin', sanitize_text_field($_POST['cc_pdf_nombre_admin']));
            update_option('cc_pdf_cargo_admin', sanitize_text_field($_POST['cc_pdf_cargo_admin']));
            echo '<div class="notice notice-success is-dismissible"><p>Configuración guardada exitosamente.</p></div>';
        }

        $logo = get_option('cc_pdf_logo', '');
        $firma = get_option('cc_pdf_firma_admin', '');
        $nombre = get_option('cc_pdf_nombre_admin', '');
        $cargo = get_option('cc_pdf_cargo_admin', '');
        ?>
        <div class="wrap">
            <h1>Configuración Global del Documento PDF</h1>
            <div class="card" style="max-width: 600px; padding: 20px; margin-top: 20px;">
                <form method="post">
                    <h3>1. Membrete</h3>
                    <p>
                        <label><strong>Logo de la Empresa (Superior Derecha):</strong></label><br>
                        <input type="text" id="cc_pdf_logo" name="cc_pdf_logo" value="<?php echo esc_attr($logo); ?>" style="width: 70%;">
                        <button type="button" class="button cc-btn-imagen" data-target="cc_pdf_logo">Subir/Elegir Logo</button>
                    </p>
                    <hr>
                    <h3>2. Datos de Autorización</h3>
                    <p>
                        <label><strong>Nombre del que autoriza:</strong></label><br>
                        <input type="text" name="cc_pdf_nombre_admin" value="<?php echo esc_attr($nombre); ?>" style="width: 100%;">
                    </p>
                    <p>
                        <label><strong>Cargo:</strong></label><br>
                        <input type="text" name="cc_pdf_cargo_admin" value="<?php echo esc_attr($cargo); ?>" style="width: 100%;" placeholder="Ej: COORDINADOR DEL GRUPO DE GESTIÓN...">
                    </p>
                    <p>
                        <label><strong>Firma Digitalizada del Autorizador:</strong></label><br>
                        <input type="text" id="cc_pdf_firma_admin" name="cc_pdf_firma_admin" value="<?php echo esc_attr($firma); ?>" style="width: 70%;">
                        <button type="button" class="button cc-btn-imagen" data-target="cc_pdf_firma_admin">Subir/Elegir Firma</button>
                    </p>
                    <p style="margin-top: 20px;">
                        <input type="submit" name="cc_guardar_config" class="button button-primary button-large" value="Guardar Configuración">
                    </p>
                </form>
            </div>
        </div>

        <script>
            jQuery(document).ready(function($){
                var marcoMedios;
                var campoDestino; // Guardará el ID del campo correcto

                $('.cc-btn-imagen').on('click', function(e) {
                    e.preventDefault();
                    campoDestino = $(this).data('target'); // Memoriza qué botón presionaste
                    
                    if (marcoMedios) { 
                        marcoMedios.open(); 
                        return; 
                    }
                    
                    marcoMedios = wp.media({ title: 'Seleccionar Imagen', button: { text: 'Usar esta imagen' }, multiple: false });
                    
                    marcoMedios.on('select', function() {
                        var attachment = marcoMedios.state().get('selection').first().toJSON();
                        $('#' + campoDestino).val(attachment.url); // Pega la URL en el campo correcto
                    });
                    
                    marcoMedios.open();
                });
            });
        </script>
        <?php
    }
}