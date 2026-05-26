<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class CC_Importador {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'agregar_menu_importar' ) );
    }

    public function agregar_menu_importar() {
        add_submenu_page(
            'edit.php?post_type=convenio',
            'Importar Base de Datos',
            'Cargar Base de Datos',
            'manage_options',
            'cc-importar-socios',
            array( $this, 'pantalla_de_importacion' )
        );
    }

    /**
     * Esta es la función mágica que atrapa la 'Ñ', tildes y caracteres de Excel
     * y los purifica a UTF-8 para que no rompan la base de datos ni el JSON.
     */
    private function limpiar_texto($texto) {
        if (empty($texto)) return '';
        // Quita espacios en blanco innecesarios al inicio y final
        $texto = trim($texto);
        // Convierte el formato antiguo de Windows a formato Web Seguro
        return mb_convert_encoding($texto, 'UTF-8', 'UTF-8, ISO-8859-1, WINDOWS-1252');
    }

    public function pantalla_de_importacion() {
        global $wpdb;
        echo '<div class="wrap"><h1>Actualizar Base de Datos de Socios</h1>';

        if ( isset( $_POST['cc_importar_nonce'] ) && wp_verify_nonce( $_POST['cc_importar_nonce'], 'cc_accion_importar' ) ) {
            if ( ! empty( $_FILES['archivo_txt']['tmp_name'] ) ) {
                $archivo = $_FILES['archivo_txt']['tmp_name'];
                
                // Vaciamos la tabla de socios para cargar la nueva info
                $wpdb->query( "TRUNCATE TABLE datos_socios_x79q" );
                
                $registros_insertados = 0;
                
                if ( ( $gestor = fopen( $archivo, "r" ) ) !== FALSE ) {
                    
                    // Ahora usamos la coma (,) como separador porque así viene tu archivo real
                    $separador = ","; 
                    
                    // Leer la primera línea para atrapar los encabezados
                    $encabezados = fgetcsv( $gestor, 10000, $separador );
                    
                    if ( $encabezados ) {
                        // Purificamos los encabezados por si acaso
                        $encabezados = array_map(array($this, 'limpiar_texto'), $encabezados);
                        
                        // El sistema busca automáticamente en qué columna está cada dato!
                        $idx_id      = array_search('IDENTIFICACION', $encabezados);
                        $idx_nom     = array_search('NOMBRE_COMPLETO', $encabezados);
                        $idx_correo  = array_search('CORREO', $encabezados);
                        $idx_ced_tit = array_search('CEDULA_TITULAR', $encabezados);
                        $idx_nom_tit = array_search('NOMBRE_TITULAR', $encabezados);
                        
                        // Valores por defecto por si el archivo cambia ligeramente el nombre de la columna
                        if($idx_id === false) $idx_id = 0;
                        if($idx_nom === false) $idx_nom = 1;
                        if($idx_correo === false) $idx_correo = 5;
                        if($idx_ced_tit === false) $idx_ced_tit = 14;
                        if($idx_nom_tit === false) $idx_nom_tit = 15;

                        // Leemos el resto del archivo línea por línea
                        while ( ( $datos = fgetcsv( $gestor, 10000, $separador ) ) !== FALSE ) {
                            
                            // Extraemos y pasamos el texto por la función de limpieza de Ñ y tildes
                            $id_persona  = isset($datos[$idx_id]) ? $this->limpiar_texto($datos[$idx_id]) : '';
                            $nom_persona = isset($datos[$idx_nom]) ? $this->limpiar_texto($datos[$idx_nom]) : '';
                            $email       = isset($datos[$idx_correo]) ? sanitize_email($datos[$idx_correo]) : '';
                            $ced_titular = isset($datos[$idx_ced_tit]) ? $this->limpiar_texto($datos[$idx_ced_tit]) : '';
                            $nom_titular = isset($datos[$idx_nom_tit]) ? $this->limpiar_texto($datos[$idx_nom_tit]) : '';

                            // Si la fila está vacía o no tiene cédula titular, la ignoramos
                            if ( empty($ced_titular) ) continue;

                            // Insertamos en la base de datos ya limpio y seguro en UTF-8
                            $wpdb->insert(
                                'datos_socios_x79q',
                                array(
                                    'cedula_titular'  => $ced_titular,
                                    'nombre_titular'  => $nom_titular,
                                    'identificacion'  => $id_persona,
                                    'nombre_completo' => $nom_persona,
                                    'email'           => $email
                                ),
                                array( '%s', '%s', '%s', '%s', '%s' )
                            );
                            $registros_insertados++;
                        }
                    }
                    fclose( $gestor );
                    echo '<div class="notice notice-success is-dismissible"><p>¡Base de datos actualizada! ' . $registros_insertados . ' registros cargados correctamente sin errores de formato.</p></div>';
                }
            }
        }

        ?>
        <div class="card" style="max-width: 600px; padding: 20px; margin-top: 20px;">
            <p><strong>Importante:</strong> El sistema ha sido mejorado. Ahora puedes subir tu archivo CSV original <strong>separado por comas ( , )</strong> con todas sus columnas.</p>
            <p>El sistema detectará automáticamente y extraerá solo las columnas: <code>IDENTIFICACION</code>, <code>NOMBRE_COMPLETO</code>, <code>CORREO</code>, <code>CEDULA_TITULAR</code> y <code>NOMBRE_TITULAR</code> corrigiendo cualquier error con las "ñ" o tildes.</p>
            <form method="post" enctype="multipart/form-data">
                <?php wp_nonce_field( 'cc_accion_importar', 'cc_importar_nonce' ); ?>
                <input type="file" name="archivo_txt" accept=".txt,.csv" required>
                <p><input type="submit" class="button button-primary" value="Cargar y Actualizar"></p>
            </form>
        </div>
        <?php
        echo '</div>';
    }
}