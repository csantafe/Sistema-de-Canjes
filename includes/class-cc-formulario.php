<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class CC_Formulario {

    public function __construct() {
        add_shortcode( 'formulario_canje', array( $this, 'mostrar_formulario' ) );
        add_action( 'wp_ajax_cc_buscar_socio', array( $this, 'ajax_buscar_socio' ) );
        add_action( 'wp_ajax_nopriv_cc_buscar_socio', array( $this, 'ajax_buscar_socio' ) );
        add_action( 'wp_ajax_cc_procesar_canje', array( $this, 'ajax_procesar_canje' ) );
        add_action( 'wp_ajax_nopriv_cc_procesar_canje', array( $this, 'ajax_procesar_canje' ) );
        add_action( 'wp_ajax_cc_generar_pdf_servidor', array( $this, 'ajax_generar_pdf_servidor' ) );
        add_action( 'wp_ajax_nopriv_cc_generar_pdf_servidor', array( $this, 'ajax_generar_pdf_servidor' ) );
    }

    public function mostrar_formulario() {
        $clubes = get_posts( array( 'post_type' => 'convenio', 'posts_per_page' => -1, 'post_status' => 'publish' ) );
        wp_enqueue_script('jquery');
        ob_start(); 
        ?>
        <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.min.js"></script>

        <style>
            .cc-form-container { max-width: 600px; margin: 20px auto; padding: 25px; background: #fff; border-radius: 10px; border: 1px solid #e0e0e0; font-family: Arial, sans-serif; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
            .cc-form-group { margin-bottom: 20px; }
            .cc-form-group label { display: block; font-weight: bold; margin-bottom: 8px; color: #333; }
            .cc-form-group input, .cc-form-group select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box;}
            .cc-btn { background: #003366; color: #fff; padding: 12px 20px; border: none; cursor: pointer; border-radius: 5px; font-weight: bold; width: 100%; font-size: 16px;}
            .cc-btn:hover { background: #002244; }
            #cc_seccion_oculta { display: none; margin-top: 25px; border-top: 1px solid #eee; padding-top: 25px; }
            .cc-firma-wrapper { border: 2px dashed #003366; background: #fff; border-radius: 5px; margin-top: 10px; position: relative; }
            canvas#cc_firma_canvas { width: 100%; height: 200px; display: block; touch-action: none; }
            .cc-mensaje { display: none; margin: 15px 0; padding: 12px; border-radius: 5px; font-weight: bold; text-align: center;}
            .cc-error { background: #fdecea; color: #d32f2f; border: 1px solid #ef9a9a; }
            .cc-exito { background: #edf7ed; color: #1e4620; border: 1px solid #c8e6c9; }
        </style>

        <div class="cc-form-container">
            <h2 style="text-align:center; color:#003366;">Solicitud de Canje</h2>
            
            <div class="cc-form-group">
                <label>Número de Cédula del Titular:</label>
                <input type="text" id="cc_cedula_buscar" placeholder="Ej: 12345678">
                <button type="button" id="cc_btn_validar" class="cc-btn" style="margin-top:10px;">Verificar Socio</button>
                <div id="cc_msg_busqueda" class="cc-mensaje cc-error">Socio no encontrado.</div>
            </div>

            <div id="cc_seccion_oculta">
                <div class="cc-form-group">
                    <label>Nombre del Titular:</label>
                    <input type="text" id="cc_nombre_titular" readonly style="background:#f5f5f5;">
                </div>
                <div class="cc-form-group">
                    <label>Correo Electrónico:</label>
                    <input type="email" id="cc_email_titular">
                </div>
                <div class="cc-form-group">
                    <label>Club Destino:</label>
                    <select id="cc_club_destino">
                        <option value="">-- Seleccionar Club --</option>
                        <?php foreach ( $clubes as $club ) : ?>
                            <option value="<?php echo esc_attr( $club->ID ); ?>"><?php echo esc_html( $club->post_title ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="cc-form-group">
                    <label>Fecha de Inicio de Visita:</label>
                    <input type="date" id="cc_fecha_inicio">
                </div>
                <div class="cc-form-group">
                    <label>Fecha de Fin de Visita:</label>
                    <input type="date" id="cc_fecha_fin">
                </div>
                <div class="cc-form-group">
                    <label>Beneficiarios que lo acompañan (Opcional):</label>
                    <div id="cc_lista_beneficiarios"></div>
                </div>

                <div class="cc-form-group">
                    <label>Firma del Titular (Firme en el recuadro):</label>
                    <div class="cc-firma-wrapper">
                        <canvas id="cc_firma_canvas"></canvas>
                    </div>
                    <button type="button" id="cc_btn_borrar" style="margin-top:5px; background:none; border:none; color:#003366; cursor:pointer; text-decoration:underline;">Limpiar firma</button>
                </div>

                <div id="cc_msg_final" class="cc-mensaje"></div>
                <div style="text-align: center; margin-bottom: 10px;">
                     <button type="button" id="cc_btn_enviar" class="cc-btn">Generar Certificado y Enviar</button>
                </div>
            </div>
        </div>

        <script>
            jQuery(document).ready(function($) {
                var canvas = document.getElementById('cc_firma_canvas');
                var signaturePad = new SignaturePad(canvas, { penColor: "rgb(0, 51, 102)" });

                function resizeCanvas() {
                    var ratio = Math.max(window.devicePixelRatio || 1, 1);
                    canvas.width = canvas.offsetWidth * ratio;
                    canvas.height = canvas.offsetHeight * ratio;
                    canvas.getContext("2d").scale(ratio, ratio);
                    signaturePad.clear();
                }

                $('#cc_btn_borrar').on('click', function() { signaturePad.clear(); });
                
                $('#cc_btn_validar').on('click', function() {
                    var cedula = $('#cc_cedula_buscar').val().trim();
                    if(!cedula) return;
                    $('#cc_msg_busqueda').hide();
                    $(this).text('Buscando...');
                    
                    $.post('<?php echo admin_url('admin-ajax.php'); ?>', { action: 'cc_buscar_socio', cedula: cedula }, function(res) {
                        $('#cc_btn_validar').text('Verificar Socio');
                        if(res.success) {
                            $('#cc_seccion_oculta').slideDown(function() { resizeCanvas(); });
                            $('#cc_nombre_titular').val(res.data.titular.nombre);
                            $('#cc_email_titular').val(res.data.titular.email);
                            var html = '';
                            $.each(res.data.beneficiarios, function(i, b) {
                                html += '<div style="margin-bottom:5px;"><label><input type="checkbox" class="ben-cb" value="'+b.id+'|||'+b.nombre+'"> '+b.nombre+' ('+b.id+')</label></div>';
                            });
                            $('#cc_lista_beneficiarios').html(html || '<p>Sin beneficiarios asociados en la base de datos.</p>');
                        } else {
                            $('#cc_msg_busqueda').show();
                        }
                    });
                });

                $('#cc_btn_enviar').on('click', function() {
                    var btn = $(this);
                    var club_id = $('#cc_club_destino').val();
                    var inicio = $('#cc_fecha_inicio').val();
                    var fin = $('#cc_fecha_fin').val();
                    var email = $('#cc_email_titular').val();

                    if(!club_id || !inicio || !fin || !email) { 
                        $('#cc_msg_final').removeClass('cc-exito').addClass('cc-error').text('Complete todos los campos.').show(); return; 
                    }
                    var firmaData = signaturePad.toData();
                    if (firmaData.length === 0) {
                        $('#cc_msg_final').removeClass('cc-exito').addClass('cc-error').text('Debe firmar.').show(); return; 
                    }
                    var puntosFirma = 0;
                    for (var i = 0; i < firmaData.length; i++) { puntosFirma += firmaData[i].points.length; }
                    if (puntosFirma < 20) { 
                        $('#cc_msg_final').removeClass('cc-exito').addClass('cc-error').text('Firma inválida.').show(); return; 
                    }
                    
                    btn.prop('disabled', true).text('Procesando...');
                    var bens = [];
                    var bensNombresPuros = [];
                    $('.ben-cb:checked').each(function() { 
                        bens.push($(this).val()); 
                        bensNombresPuros.push($(this).val().split('|||')[1]);
                    });

                    $.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                        action: 'cc_procesar_canje',
                        cedula: $('#cc_cedula_buscar').val(),
                        club_id: club_id,
                        fecha_inicio: inicio,
                        fecha_fin: fin,
                        beneficiarios_historial: bensNombresPuros
                    }, function(res) {
                        if(res.success) {
                            btn.text('Generando Documento Oficial...');
                            var qr = new QRious({ value: res.data.url_verificacion, size: 200, level: 'M' });
                            
                            $.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                                action: 'cc_generar_pdf_servidor',
                                cedula: $('#cc_cedula_buscar').val(),
                                nombre_titular: $('#cc_nombre_titular').val(),
                                club_nombre: $('#cc_club_destino option:selected').text(),
                                club_id: club_id,
                                email_titular: email,
                                fecha_inicio: inicio,
                                fecha_fin: fin,
                                beneficiarios_data: bens,
                                firma_img: signaturePad.toDataURL(),
                                qr_img: qr.toDataURL('image/png')
                            }, function(pdfRes) {
                                btn.prop('disabled', false).text('Generar Certificado y Enviar');
                                if(pdfRes.success) {
                                    $('#cc_msg_final').removeClass('cc-error').addClass('cc-exito').text('¡Canje aprobado! Documento enviado con éxito.').show();
                                    var link = document.createElement('a');
                                    link.href = 'data:application/pdf;base64,' + pdfRes.data.pdf_base64;
                                    link.download = 'Certificado_Canje_' + $('#cc_cedula_buscar').val() + '.pdf';
                                    document.body.appendChild(link);
                                    link.click();
                                    document.body.removeChild(link);
                                    $('#cc_seccion_oculta').slideUp();
                                    $('#cc_cedula_buscar').val('');
                                    signaturePad.clear();
                                } else {
                                    $('#cc_msg_final').removeClass('cc-exito').addClass('cc-error').text('Error generando el PDF.').show();
                                }
                            });
                        } else {
                            btn.prop('disabled', false).text('Generar Certificado y Enviar');
                            $('#cc_msg_final').removeClass('cc-exito').addClass('cc-error').text(res.data).show();
                        }
                    });
                });
            });
        </script>
        <?php
        return ob_get_clean();
    }

    private function purificar_utf8($texto) {
        if (empty($texto)) return '';
        return mb_convert_encoding($texto, 'UTF-8', 'UTF-8, ISO-8859-1, WINDOWS-1252');
    }

    private function preparar_imagen_pdf($url) {
        if (empty($url)) return '';
        $attachment_id = attachment_url_to_postid($url);
        if ($attachment_id) {
            $path = get_attached_file($attachment_id);
            if ($path && file_exists($path)) {
                $type = pathinfo($path, PATHINFO_EXTENSION);
                $data = file_get_contents($path);
                return 'data:image/' . $type . ';base64,' . base64_encode($data);
            }
        }
        $data = @file_get_contents($url);
        if ($data) {
            $type = pathinfo($url, PATHINFO_EXTENSION);
            return 'data:image/' . $type . ';base64,' . base64_encode($data);
        }
        return '';
    }

    /**
     * CORRECCIÓN DE SEGURIDAD RELACIONAL: El buscador ahora exige que el beneficiario
     * pertenezca ESTRICTAMENTE a la cédula del titular en la base de datos limpiando espacios.
     */
    public function ajax_buscar_socio() {
        global $wpdb;
        $cedula = sanitize_text_field(trim($_POST['cedula']));
        
        // Buscamos los registros que correspondan exactamente a esta cédula de titular
        $res = $wpdb->get_results($wpdb->prepare("SELECT * FROM datos_socios_x79q WHERE TRIM(cedula_titular) = %s", $cedula));
        
        if ($res) {
            $data = [
                'titular' => [
                    'nombre' => $this->purificar_utf8($res[0]->nombre_titular), 
                    'email' => $res[0]->email
                ], 
                'beneficiarios' => []
            ];
            
            foreach($res as $f) {
                // Validación para evitar cruces: Solo agregamos si hay identificación de beneficiario 
                // Y si no es el mismo titular clonado en la fila
                if(!empty($f->identificacion) && trim($f->identificacion) !== trim($f->cedula_titular) && trim($f->nombre_completo) !== trim($f->nombre_titular)) {
                    $data['beneficiarios'][] = [
                        'id' => trim($f->identificacion), 
                        'nombre' => $this->purificar_utf8($f->nombre_completo)
                    ];
                }
            }
            wp_send_json_success($data);
        } else { 
            wp_send_json_error(); 
        }
        wp_die();
    }

    public function ajax_procesar_canje() {
        global $wpdb;
        $cedula = sanitize_text_field($_POST['cedula']);
        $club_id = intval($_POST['club_id']);
        $inicio = sanitize_text_field($_POST['fecha_inicio']);
        $fin = sanitize_text_field($_POST['fecha_fin']);
        $bens = isset($_POST['beneficiarios_historial']) ? array_map('sanitize_text_field', $_POST['beneficiarios_historial']) : [];

        $d1 = date_create($inicio); $d2 = date_create($fin);
        if ($d1 > $d2) { wp_send_json_error('La fecha de inicio debe ser anterior a la fecha de fin.'); wp_die(); }
        $diff = date_diff($d1, $d2)->days + 1;

        // Validación de traslapo
        $cruce_fechas = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM registro_canjes_h9k2 WHERE cedula_titular = %s AND fecha_inicio <= %s AND fecha_fin >= %s",
            $cedula, $fin, $inicio
        ));

        if ($cruce_fechas > 0) {
            wp_send_json_error("Ya existe una solicitud de canje activa para estas fechas o hay un cruce con un periodo ya reservado.");
            wp_die();
        }

        $l_anual = (int)get_post_meta($club_id, '_cc_limite_anual', true);
        $l_visita = (int)get_post_meta($club_id, '_cc_limite_visita', true);

        if($l_visita > 0 && $diff > $l_visita) { wp_send_json_error("Límite superado. Máximo $l_visita días por visita."); wp_die(); }
        
        $ano_actual = date('Y');
        $consumidos = (int) $wpdb->get_var($wpdb->prepare("SELECT SUM(dias_consumidos) FROM registro_canjes_h9k2 WHERE cedula_titular = %s AND club_id = %d AND ano_solicitud = %d", $cedula, $club_id, $ano_actual));
        
        if($l_anual > 0 && ($consumidos + $diff) > $l_anual) {
            $disponibles = $l_anual - $consumidos;
            wp_send_json_error("Excede el límite anual. Días disponibles: $disponibles."); wp_die();
        }

        $wpdb->insert('registro_canjes_h9k2', array(
            'cedula_titular' => $cedula, 'club_id' => $club_id, 'fecha_inicio' => $inicio, 'fecha_fin' => $fin,
            'dias_consumidos' => $diff, 'ano_solicitud' => $ano_actual, 'beneficiarios_viajeros' => implode(', ', $bens)
        ), array('%s', '%d', '%s', '%s', '%d', '%d', '%s'));

        $id_registro = $wpdb->insert_id;
        $hash_seguridad = substr(md5($id_registro . 'clubmilitar_secreto'), 0, 8);
        $url_verificacion = site_url('/?cc_verificar=' . $id_registro . '&h=' . $hash_seguridad);

        wp_send_json_success(array('url_verificacion' => $url_verificacion));
        wp_die();
    }

    public function ajax_generar_pdf_servidor() {
        require_once plugin_dir_path( __FILE__ ) . 'dompdf/autoload.inc.php';

        $cedula = sanitize_text_field($_POST['cedula']);
        $titular = sanitize_text_field($_POST['nombre_titular']);
        $club = sanitize_text_field($_POST['club_nombre']);
        $club_id = intval($_POST['club_id']);
        $email = sanitize_email($_POST['email_titular']);
        $firma_img = $_POST['firma_img'];
        $qr_img = $_POST['qr_img'];

        $meses = ["Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre"];
        $fecha_actual = date('d') . " de " . $meses[date('n')-1] . " de " . date('Y');
        $inicio_txt = date('d', strtotime($_POST['fecha_inicio'])) . " de " . $meses[date('n', strtotime($_POST['fecha_inicio']))-1] . " de " . date('Y', strtotime($_POST['fecha_inicio']));

        $logo_empresa = $this->preparar_imagen_pdf(get_option('cc_pdf_logo', ''));
        $firma_admin = $this->preparar_imagen_pdf(get_option('cc_pdf_firma_admin', ''));
        $nombre_admin = get_option('cc_pdf_nombre_admin', 'COORDINADOR DEL GRUPO');
        $cargo_admin = get_option('cc_pdf_cargo_admin', 'CLUB MILITAR');

        // Obtener los 3 correos electrónicos del convenio para el envío posterior
        $correo_club_1 = get_post_meta($club_id, '_cc_correo_club_1', true);
        $correo_club_2 = get_post_meta($club_id, '_cc_correo_club_2', true);
        $correo_club_3 = get_post_meta($club_id, '_cc_correo_club_3', true);

        $filas_beneficiarios = '';
        if (!empty($_POST['beneficiarios_data'])) {
            foreach ($_POST['beneficiarios_data'] as $ben_str) {
                $partes = explode('|||', sanitize_text_field($ben_str));
                $id_ben = isset($partes[0]) ? $partes[0] : '';
                $nom_ben = isset($partes[1]) ? $partes[1] : '';
                $filas_beneficiarios .= '<tr><td style="padding:5px; border:1px solid #000; text-align:center;">'.$id_ben.'</td><td style="padding:5px; border:1px solid #000;">'.$nom_ben.'</td></tr>';
            }
        } else {
            $filas_beneficiarios = '<tr><td colspan="2" style="padding:5px; border:1px solid #000; text-align:center; font-style:italic;">No se reportaron beneficiarios para esta visita.</td></tr>';
        }

        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
            <style>
                body { font-family: "Helvetica", Arial, sans-serif; font-size: 14px; padding: 0; margin: 0; }
                .marco-principal { border: 2px solid #000; padding: 40px; min-height: 900px; position: relative; }
                .texto-justificado { text-align: justify; line-height: 1.6; }
                .tabla-ben { width: 100%; border-collapse: collapse; margin-top: 15px; margin-bottom: 15px; }
            </style>
        </head>
        <body>
            <div class="marco-principal">
                <table style="width: 100%; margin-bottom: 30px;">
                    <tr>
                        <td style="width: 60%;"></td>
                        <td style="width: 40%; text-align: right; vertical-align: top;">
                            '.(!empty($logo_empresa) ? '<img src="'.$logo_empresa.'" height="80">' : '').'
                        </td>
                    </tr>
                </table>
                
                <div style="text-align: center; font-weight: bold;">
                    EL SUSCRITO COORDINADOR DEL GRUPO DE GESTIÓN DEL SOCIO<br>
                    DEL CLUB MILITAR
                </div>

                <div style="margin-top: 30px;">
                    Bogotá, D.C., ' . $fecha_actual . '
                </div>

                <div class="texto-justificado" style="margin-top: 30px;">
                    Que, una vez verificada la base de datos de socios del Club Militar, se evidencia que el señor(a) <strong>' . $titular . '</strong> con cédula de ciudadanía No. <strong>' . $cedula . '</strong>, se encuentra ACTIVO como socio(a) del Club Militar.
                    <br><br>
                    El señor(a) visitará las instalaciones del canje <strong>' . $club . '</strong>, haciendo uso del convenio suscrito con su prestigiosa entidad, junto con su familia.
                </div>

                <table class="tabla-ben">
                    <tr>
                        <th style="padding:5px; border:1px solid #000; background:#f0f0f0; width: 30%;">Identificación</th>
                        <th style="padding:5px; border:1px solid #000; background:#f0f0f0; width: 70%;">Nombres y Apellidos</th>
                    </tr>
                    ' . $filas_beneficiarios . '
                </table>

                <div class="texto-justificado" style="margin-top: 20px;">
                    La presente se expide a solicitud del interesado(a) para hacer uso de los servicios del convenio <strong>' . $club . '</strong>, a partir del día <strong>' . $inicio_txt . '</strong>, con una vigencia de tres meses.
                </div>

                <div style="margin-top: 30px;">
                    Cordialmente,
                </div>

                <table style="width: 100%; margin-top: 40px; table-layout: fixed;">
                    <tr>
                        <td style="width: 50%; text-align: center; vertical-align: bottom;">
                            '.(!empty($firma_admin) ? '<img src="'.$firma_admin.'" height="70" style="margin-bottom: 5px;"><br>' : '<br><br><br><br>').'
                            <div style="border-top: 1px solid #000; width: 80%; margin: 0 auto; padding-top: 5px;">
                                <strong>' . $nombre_admin . '</strong><br>
                                <span style="font-size: 12px;">' . $cargo_admin . '</span>
                            </div>
                        </td>
                        <td style="width: 50%; text-align: center; vertical-align: bottom;">
                            <img src="' . $firma_img . '" height="70" style="margin-bottom: 5px;"><br>
                            <div style="border-top: 1px solid #000; width: 80%; margin: 0 auto; padding-top: 5px;">
                                <strong>Firma del Socio Titular</strong><br>
                                <span style="font-size: 12px;">C.C. ' . $cedula . '</span>
                            </div>
                        </td>
                    </tr>
                </table>

                <div style="text-align: center; margin-top: 40px;">
                    <img src="' . $qr_img . '" style="width: 100px; height: 100px;"><br>
                    <span style="font-size: 10px; color: #555;">Escanear para verificar la vigencia y autenticidad en línea.</span>
                </div>
            </div>
        </body>
        </html>';

        $options = new \Dompdf\Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $pdf_output = $dompdf->output();
        $upload_dir = wp_upload_dir();
        $pdf_path = $upload_dir['path'] . '/certificado_' . $cedula . '.pdf';
        file_put_contents($pdf_path, $pdf_output);

        $admin_email = get_option('admin_email');
        $adjuntos = array($pdf_path);
        $cabeceras = array('Content-Type: text/html; charset=UTF-8');

        // 1. Envío de correo al socio titular
        wp_mail($email, 'Certificado de Canje Aprobado', '<p>Adjunto encontrará su documento.</p>', $cabeceras, $adjuntos);
        
        // 2. Envío multidestino: Correo automático a las 3 casillas del club si existen
        if(!empty($correo_club_1)) wp_mail($correo_club_1, 'Visita por Convenio', '<p>Adjuntamos el certificado de nuestro socio.</p>', $cabeceras, $adjuntos);
        if(!empty($correo_club_2)) wp_mail($correo_club_2, 'Visita por Convenio (Copia)', '<p>Adjuntamos el certificado de nuestro socio.</p>', $cabeceras, $adjuntos);
        if(!empty($correo_club_3)) wp_mail($correo_club_3, 'Visita por Convenio (Copia)', '<p>Adjuntamos el certificado de nuestro socio.</p>', $cabeceras, $adjuntos);
        
        // 3. Envío al administrador de control
        wp_mail($admin_email, 'Copia Interna de Certificado', '<p>Documento emitido.</p>', $cabeceras, $adjuntos);

        @unlink($pdf_path);
        wp_send_json_success(array('pdf_base64' => base64_encode($pdf_output)));
        wp_die();
    }
}