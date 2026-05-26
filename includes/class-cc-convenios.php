<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class CC_Convenios {

    public function __construct() {
        add_action( 'init', array( $this, 'registrar_tipo_convenio' ) );
        add_action( 'add_meta_boxes', array( $this, 'agregar_cajas_metadatos' ) );
        add_action( 'save_post', array( $this, 'guardar_datos_convenio' ) );
    }

    public function registrar_tipo_convenio() {
        $etiquetas = array(
            'name'               => 'Convenios',
            'singular_name'      => 'Convenio',
            'menu_name'          => 'Convenios',
            'add_new'            => 'Añadir Nuevo',
            'add_new_item'       => 'Añadir Nuevo Convenio',
            'edit_item'          => 'Editar Convenio',
            'all_items'          => 'Todos los Convenios',
        );

        $argumentos = array(
            'labels'             => $etiquetas,
            'public'             => false,
            'show_ui'            => true,
            'menu_icon'          => 'dashicons-tickets-alt',
            'supports'           => array( 'title' ),
        );

        register_post_type( 'convenio', $argumentos );
    }

    public function agregar_cajas_metadatos() {
        add_meta_box(
            'cc_datos_convenio',
            'Reglas y Datos del Convenio',
            array( $this, 'mostrar_campos_convenio' ),
            'convenio',
            'normal',
            'high'
        );
    }

    public function mostrar_campos_convenio( $post ) {
        wp_nonce_field( 'guardar_datos_convenio', 'cc_convenio_nonce' );

        $limite_anual = get_post_meta( $post->ID, '_cc_limite_anual', true );
        $limite_visita = get_post_meta( $post->ID, '_cc_limite_visita', true );
        
        // Recuperar los 3 correos electrónicos
        $correo_club_1 = get_post_meta( $post->ID, '_cc_correo_club_1', true );
        $correo_club_2 = get_post_meta( $post->ID, '_cc_correo_club_2', true );
        $correo_club_3 = get_post_meta( $post->ID, '_cc_correo_club_3', true );
        ?>
        <p>
            <label for="cc_correo_club_1"><strong>Correo Electrónico Destino 1 (Principal):</strong></label><br>
            <input type="email" id="cc_correo_club_1" name="cc_correo_club_1" value="<?php echo esc_attr( $correo_club_1 ); ?>" size="50" placeholder="principal@clubdestino.com" />
        </p>
        <p>
            <label for="cc_correo_club_2"><strong>Correo Electrónico Destino 2 (Opcional):</strong></label><br>
            <input type="email" id="cc_correo_club_2" name="cc_correo_club_2" value="<?php echo esc_attr( $correo_club_2 ); ?>" size="50" placeholder="copia1@clubdestino.com" />
        </p>
        <p>
            <label for="cc_correo_club_3"><strong>Correo Electrónico Destino 3 (Opcional):</strong></label><br>
            <input type="email" id="cc_correo_club_3" name="cc_correo_club_3" value="<?php echo esc_attr( $correo_club_3 ); ?>" size="50" placeholder="copia2@clubdestino.com" />
            <br><small>El certificado PDF se enviará automáticamente a todos los correos parametrizados aquí.</small>
        </p>
        <hr>
        <p>
            <label for="cc_limite_anual"><strong>Días disfrutables en el año (Límite Anual):</strong></label><br>
            <input type="number" id="cc_limite_anual" name="cc_limite_anual" value="<?php echo esc_attr( $limite_anual ); ?>" min="1" step="1" />
        </p>
        <p>
            <label for="cc_limite_visita"><strong>Días seguidos por canje (Límite por Visita):</strong></label><br>
            <input type="number" id="cc_limite_visita" name="cc_limite_visita" value="<?php echo esc_attr( $limite_visita ); ?>" min="1" step="1" />
        </p>
        <?php
    }

    public function guardar_datos_convenio( $post_id ) {
        if ( ! isset( $_POST['cc_convenio_nonce'] ) || ! wp_verify_nonce( $_POST['cc_convenio_nonce'], 'guardar_datos_convenio' ) ) return;
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        if ( isset( $_POST['cc_limite_anual'] ) ) {
            update_post_meta( $post_id, '_cc_limite_anual', sanitize_text_field( $_POST['cc_limite_anual'] ) );
        }
        if ( isset( $_POST['cc_limite_visita'] ) ) {
            update_post_meta( $post_id, '_cc_limite_visita', sanitize_text_field( $_POST['cc_limite_visita'] ) );
        }
        
        // Guardar/Actualizar los 3 campos de correo
        if ( isset( $_POST['cc_correo_club_1'] ) ) {
            update_post_meta( $post_id, '_cc_correo_club_1', sanitize_email( $_POST['cc_correo_club_1'] ) );
        }
        if ( isset( $_POST['cc_correo_club_2'] ) ) {
            update_post_meta( $post_id, '_cc_correo_club_2', sanitize_email( $_POST['cc_correo_club_2'] ) );
        }
        if ( isset( $_POST['cc_correo_club_3'] ) ) {
            update_post_meta( $post_id, '_cc_correo_club_3', sanitize_email( $_POST['cc_correo_club_3'] ) );
        }
    }
}