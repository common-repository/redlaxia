<?php

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

function redla_desactivar_plugin() {
    $token = get_option('se_tiendas_token');
    if (!empty($token)) {
        $response = wp_remote_post(REDLA_URL_APP, array(
            'method' => 'POST',
            'timeout' => 45,
            'redirection' => 5,
            'httpversion' => '1.0',
            'blocking' => true,
            'headers' => array(),
            'body' => array(
                'action_se' => 'delete_shop',
                'se_token' => $token
            ),
            'cookies' => array()
                )
        );
    }
}

function redla_url_exists($url = NULL) {

    if (empty($url)) {
        return false;
    }

    $response = wp_remote_head($url, array('timeout' => 5));

    // Aceptar solo respuesta 200 (Ok), 301 (redirección permanente) o 302 (redirección temporal)
    $accepted_response = array(200, 301, 302);
    if (!is_wp_error($response) && in_array(wp_remote_retrieve_response_code($response), $accepted_response)) {
        return true;
    } else {
        return false;
    }
}

/*
 * Actualizar conexión si cambian los permalinks
 */

add_action('update_option_permalink_structure', 'redla_update_json_url_check_permalink', 10, 2);

function redla_update_json_url_check_permalink($old_value, $new_value) {

    update_option('se_peram_a', $old_value);
    update_option('se_peram_b', $new_value);

    $token = get_option('se_tiendas_token');
    error_log($token);
    if (!empty($token)) {
        $url_json = get_rest_url();
        $response = wp_remote_post(REDLA_URL_APP, array(
            'method' => 'POST',
            'timeout' => 45,
            'redirection' => 5,
            'httpversion' => '1.0',
            'blocking' => true,
            'headers' => array(),
            'body' => array(
                'action_se' => 'update_json',
                'url_json_comercio' => $url_json,
                'se_token' => $token
            ),
            'cookies' => array()
                )
        );
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            error_log($error_message);
        } else {
            error_log($response['body']);
        }
    }
}

add_action('init', 'redla_update_json_url');

function redla_update_json_url() {
    $a = get_option('se_peram_a', 'ok');
    $b = get_option('se_peram_b', 'ok');

    if ($a != $b) {
        $token = get_option('se_tiendas_token');
        if (!empty($token)) {
            $url_json = get_rest_url();
            $response = wp_remote_post(REDLA_URL_APP, array(
                'method' => 'POST',
                'timeout' => 45,
                'redirection' => 5,
                'httpversion' => '1.0',
                'blocking' => true,
                'headers' => array(),
                'body' => array(
                    'action_se' => 'update_json',
                    'url_json_comercio' => $url_json,
                    'se_token' => $token
                ),
                'cookies' => array()
                    )
            );
            if (is_wp_error($response)) {
                $error_message = $response->get_error_message();
                error_log($error_message);
            } else {
                if ($response['body'] == 'ACTUALIZADO') {
                    update_option('se_peram_a', 'ok');
                    update_option('se_peram_b', 'ok');
                } else {
                    add_action('admin_notices', 'redla_error_al_actualizar_url_json');
                }
            }
        }
    }
}

function redla_error_al_actualizar_url_json() {
    $class = 'notice notice-error';
    $message = 'Tienes que actualizar la conexión de tu tienda con la app o no se verán tus productos.';

    printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
}

function redla_provincias() {
    $provincias = ['Alava', 'Albacete', 'Alicante', 'Almería', 'Asturias', 'Avila', 'Badajoz', 'Barcelona', 'Burgos', 'Cáceres',
        'Cádiz', 'Cantabria', 'Castellón', 'Ciudad Real', 'Córdoba', 'La Coruña', 'Cuenca', 'Gerona', 'Granada', 'Guadalajara',
        'Guipúzcoa', 'Huelva', 'Huesca', 'Islas Baleares', 'Jaén', 'León', 'Lérida', 'Lugo', 'Madrid', 'Málaga', 'Murcia', 'Navarra',
        'Orense', 'Palencia', 'Las Palmas', 'Pontevedra', 'La Rioja', 'Salamanca', 'Segovia', 'Sevilla', 'Soria', 'Tarragona',
        'Santa Cruz de Tenerife', 'Teruel', 'Toledo', 'Valencia', 'Valladolid', 'Vizcaya', 'Zamora', 'Zaragoza'];

    return $provincias;
}

function redla_categorias() {
    $categorias = array(
        'grocery' => 'Alimentación y bebidas',
        'baby' => 'Bebé',
        'beauty' => 'Belleza',
        'diy' => 'Bricolaje y herramientas',
        'automotive' => 'Coche y Moto - Piezas y accesorios',
        'sporting' => 'Deportes y aire libre',
        'electronics' => 'Electrónica',
        'luggage' => 'Equipaje',
        'kitchen' => 'Hogar y cocina',
        'lighting' => 'Iluminación',
        'industrial' => 'Industria y ciencia',
        'computers' => 'Informática',
        'mi' => 'Instrumentos musicales',
        'lawngarden' => 'Jardín',
        'jewelry' => 'Joyería',
        'toys' => 'Juguetes y juegos',
        'stripbooks' => 'Libros',
        'fashion' => 'Moda',
        'digital-music' => 'Música Digital',
        'popular' => 'Música: CDs y vinilos',
        'office-products' => 'Oficina y papelería',
        'dvd' => 'Películas y TV',
        'pets' => 'Productos para mascotas',
        'watches' => 'Relojes',
        'apparel' => 'Ropa y accesorios',
        'hpc' => 'Salud y cuidado personal',
        'software' => 'Software',
        'videogames' => 'Videojuegos',
        'shoes' => 'Zapatos y complementos',
    );
    return $categorias;
}
