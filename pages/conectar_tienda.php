<?php
// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
/*
 * Array de datos para el formulario
 */

$provincias = redla_provincias();
$categorias = redla_categorias();
$categorias_tienda = redla_feed_categorias();
$cat = array();
$x = 0;
foreach ($categorias_tienda as $c) {
    if ($x != 0) {
        $cat[] = $c['value'];
    }
    $x++;
}
$keys_cat = implode(', ', $cat);

/*
 * Iniciamos variables
 */

$wpname = get_bloginfo('name');
$nombre = get_option('se_tiendas_nombre', $wpname);
$categoria = get_option('se_tiendas_categoria');
$provincia = get_option('se_tiendas_provincia');
$img_tienda = get_option('se_tiendas_imagen');
$url = get_bloginfo('wpurl');
$url_json = get_rest_url();
$email = get_option('se_tiendas_email');
$tlf = get_option('se_tiendas_tlf');
$descripcion = get_option('se_tiendas_descripcion');
$keys = get_option('se_tiendas_keys', $keys_cat);
$token = get_option('se_tiendas_token', '1234567890');

$tag = 'error';
$msj = 'Parece que tu tienda no esta conectada... Rellena el formulario y pulsa en "Conectar mi tienda".';
$respuesta = false;

/*
 * Si se envia el form, enviamos datos y actualizamos con update_option
 */
$respuesta = false;
if (isset($_POST['submit']) and!empty($_POST['submit'])) {
    $nonce = $_REQUEST['_wpnonce'];
    if (!wp_verify_nonce($nonce, 'senciya_conectar_tienda')) {
        die(__('Security check'));
    } else {

        $enviar = true;
        $nombre_form = sanitize_text_field($_POST['nombre']);
        $email_form = sanitize_email($_POST['email']);
        $tlf_form = sanitize_text_field($_POST['tlf']);
        $categoria_form = sanitize_text_field($_POST['categoria']);
        $provincia_form = sanitize_text_field($_POST['provincia']);
        $keys_form = sanitize_text_field($_POST['keys']);
        $descripcion_form = sanitize_text_field($_POST['descripcion']);
        $imagen_form = esc_url_raw($_POST['imagen']);

        //Comprobamos y limpiamos variables antes de enviar nada
        if (empty($nombre_form)) {
            $enviar = false;
        }
        if (empty($email_form)) {
            $enviar = false;
        } else {
            if (!is_email($email_form)) {
                $enviar = false;
            }
        }
        if (empty($tlf_form)) {
            $enviar = false;
        }
        if (empty($categoria_form)) {
            $enviar = false;
        }
        if (empty($provincia_form)) {
            $enviar = false;
        }
        if (empty($descripcion_form)) {
            $enviar = false;
        }
        if (empty($imagen_form)) {
            $enviar = false;
        }
        $nombre = $nombre_form;
        $email = $email_form;
        $tlf = $tlf_form;
        $categoria = $categoria_form;
        $provincia = $provincia_form;
        $descripcion = $descripcion_form;
        $keys = $keys_form;
        $img_tienda = $imagen_form;
    }

    if ($enviar) {
        $datos_form = array(
                'nombre' => $nombre_form,
                'email' => $email_form,
                'tlf' => $tlf_form,
                'categoria' => $categoria_form,
                'provincia' => $provincia_form,
                'keys' => $keys_form,
                'descripcion' => $descripcion_form,
                'imagen' => $imagen_form,
                'url_comercio' => $url,
                'url_json_comercio' => $url_json,
                'se_token' => $token
            );
        $response = wp_remote_post(REDLA_URL_APP, array(
            'method' => 'POST',
            'timeout' => 45,
            'redirection' => 5,
            'httpversion' => '1.0',
            'blocking' => true,
            'headers' => array(),
            'body' => $datos_form,
            'cookies' => array()
                )
        );

        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            echo esc_html("Something went wrong: $error_message");
        } else {
            $datos_res = json_decode($response['body']);
            //ponemos el token = false para no realizar la segunda consulta
            $token = false;
            if ($datos_res->estado == 'ERROR') {
                $respuesta = $datos_res->aviso;
                $tag = 'error';
                $msj = false;
            } elseif ($datos_res->estado == 'REVISION') {
                $respuesta = $datos_res->aviso;
                $tag = 'info';
                $msj = false;
            }

            if ($datos_res->estado != 'ERROR') {
                update_option('se_tiendas_nombre', $nombre_form);
                update_option('se_tiendas_categoria', $categoria_form);
                update_option('se_tiendas_provincia', $provincia_form);
                update_option('se_tiendas_imagen', $imagen_form);
                update_option('se_tiendas_email', $email_form);
                update_option('se_tiendas_tlf', $tlf_form);
                update_option('se_tiendas_descripcion', $descripcion_form);
                update_option('se_tiendas_keys', $keys_form);
                update_option('se_tiendas_token', $datos_res->token);
            }
        }
    } else {
        $tag = 'error';
        $respuesta = 'Tienes que rellenar todos los campos para poder conectar tu tienda.';
        $msj = false;
    }
}
/*
 * Realizamos consulta a la web principal para ver si todo esta OK, enviamos el token y esperamos OK o ERROR.
 */

if ($token and !$respuesta) {
    $response = wp_remote_post(REDLA_URL_APP, array(
        'method' => 'POST',
        'timeout' => 45,
        'redirection' => 5,
        'httpversion' => '1.0',
        'blocking' => true,
        'headers' => array(),
        'body' => array(
            'token_tienda' => $token,
            'url_tienda' => $url
        ),
        'cookies' => array()
            )
    );

    if (is_wp_error($response)) {
        $error_message = $response->get_error_message();
        echo esc_html("Something went wrong: $error_message");
    } else {
        if ($response['body'] == 'OK') {
            $tag = 'success';
            $msj = 'Enhorabuena!! Tu tienda esta conectada';
        } elseif ($response['body'] == 'REVISION') {
            $tag = 'info';
            $msj = 'Estamos revisando la información enviada, te mandaremos un email cuando tu tienda este conectada.';
        } else {
            $tag = 'error';
            $msj = 'Parece que tu tienda no esta conectada... Rellena el formulario y pulsa en "Conectar mi tienda".';
        }
    }
}
$enviar = true;
if (!redla_url_exists($url_json)) {
    $enviar = false;
    $tag = 'url';
    $msj =  $url ;
}
?>
<div class="wrap">
    <h1><?php echo REDLA_NAME_APP; ?></h1>
    <?php
    if($respuesta){
        switch ($tag) {
            case "error":
                ?>
                <div class="notice notice-error is-dismissible"><p><strong><span class="dashicons dashicons-dismiss"></span>
                    <?php echo esc_html($respuesta); ?>            
                </strong></p></div>
                <?php
                break;
            case "info":
                ?>
                <div class="notice notice-info is-dismissible"><p><strong><span class="dashicons dashicons-info-outline"></span>    
                    <?php echo esc_html($respuesta); ?>
                </strong></p></div>    
                <?php
                break;
        }
    }
    ?>
    <?php
    if($msj){
        switch ($tag) {
            case "url":
                ?>
                <div class="notice notice-error is-dismissible"><p><strong><span class="dashicons dashicons-dismiss"></span>
                    Para que la app pueda ver tus productos, tienes que tener activados los permalinks de Wordpress.<br />
                    <a href="<?php echo esc_url($msj); ?>/wp-admin/options-permalink.php">Haz click aquí y pulsa en guardar.</a><br />
                </strong></p></div>
                <?php
                break;
            case "error":
                ?>
                <div class="notice notice-error is-dismissible"><p><strong><span class="dashicons dashicons-dismiss"></span>
                    <?php echo esc_html($msj); ?>            
                </strong></p></div>
                <?php
                break;
            case "info":
                ?>
                <div class="notice notice-info is-dismissible"><p><strong><span class="dashicons dashicons-info-outline"></span>    
                    <?php echo esc_html($msj); ?>
                </strong></p></div>    
                <?php
                break;
            case "success":
                ?>
                <div class="notice notice-success is-dismissible"><p><strong><span class="dashicons dashicons-yes-alt"></span>
                    <?php echo esc_html($msj); ?>
                </strong></p></div>    
                <?php
                break;
        }
    }
    ?>
    
    <form method="post" action="" >
        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row"><label for="nombre">Título del sitio</label></th>
                    <td><input name="nombre" type="text" id="nombre" value="<?php echo esc_html($nombre); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="email">Email de contacto</label></th>
                    <td><input name="email" type="text" id="email" value="<?php echo esc_html($email); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="tlf">Teléfono de contacto</label></th>
                    <td><input name="tlf" type="text" id="tlf" value="<?php echo esc_html($tlf); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="categoria">Categoría</label></th>
                    <td>
                        <select name="categoria" id="categoria">
                            <option value="">Selecciona tu categoría...</option>
                            <?php
                            foreach ($categorias as $key => $res) {
                                $sel = '';
                                if ($key == $categoria) {
                                    $sel = 'selected="selected"';
                                }
                                ?>
                                <option value="<?php echo esc_html($key); ?>" <?php echo esc_html($sel); ?>><?php echo esc_html($res); ?></option>
                                <?php
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="provincia">Provincia</label></th>
                    <td>
                        <select name="provincia" id="provincia">
                            <option value="">Selecciona tu provincia...</option>
                            <?php
                            foreach ($provincias as $res) {
                                $sel = '';
                                if ($res == $provincia) {
                                    $sel = 'selected="selected"';
                                }
                                ?>
                                <option value="<?php echo esc_html($res); ?>" <?php echo esc_html($sel); ?>><?php echo esc_html($res); ?></option>
                                <?php
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="descripcion">Descripción</label></th>
                    <td>
                        <textarea name="descripcion" id="descripcion" rows="6" cols="80"><?php echo esc_html($descripcion); ?></textarea>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="keys">Palabras clave</label></th>
                    <td>
                        <textarea name="keys" id="keys" rows="6" cols="80"><?php echo esc_html($keys); ?></textarea>
                        <p class="description">Valores separados por comas, estas palabras se usarán para mostrar su tienda si el usuario busca algunas de las palabras clave.</p>
                        <p class="description"><small>Ej: zapatillas, zapatos, nike, puma,</small></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="imagen">Imagen de la tienda</label></th>
                    <td>
                        <input id="image-url" name="imagen" type="text" value="<?php echo esc_url($img_tienda); ?>"/>
                        <input id="upload-button" type="button" class="button" value="Upload Image" />
                        <p class="description">Esta imagen será tu portada, puedes hacer una foto de tu local, escaparate, etc. Recuerda que será lo primero que vean los usuarios ;)<br/>La imagen tiene que ser rectangular como la muestra.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <?php if ($enviar) { ?>
                            <input type="submit" name="submit" id="submit" class="button button-primary" value="Conectar mi tienda">
                        <?php } ?>
                    </th>
                    <td>
                        <?php
                        if (empty($img_tienda)) {
                            $img_tienda = REDLA_URL . 'images/example.png';
                        }
                        ?>
                        <img src="<?php echo esc_url($img_tienda); ?>" />
                    </td>
                </tr>
            </tbody>
        </table>
        <?php wp_nonce_field('senciya_conectar_tienda'); ?>
    </form>
</div>