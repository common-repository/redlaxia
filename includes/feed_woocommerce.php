<?php

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/*
 * Mostrar productos, categorias, etc
 */

add_action('rest_api_init', function () {
    register_rest_route('redlaxia/v1', '/productos', array(
        'methods' => 'POST',
        'callback' => 'redla_feed_productos'
    ));
});
add_action('rest_api_init', function () {
    register_rest_route('redlaxia/v1', '/productostest', array(
        'methods' => 'GET',
        'callback' => 'redla_feed_productos'
    ));
});
add_action('rest_api_init', function () {
    register_rest_route('redlaxia/v1', '/categorias', array(
        'methods' => 'GET',
        'callback' => 'redla_feed_categorias'
    ));
});
add_action('rest_api_init', function () {
    register_rest_route('redlaxia/v1', '/producto/(?P<post_id>\d+)', array(
        'methods' => 'GET',
        'callback' => 'redla_feed_producto'
    ));
});

function redla_feed_productos($data) {
    
    $noimg = REDLA_URL.'images/no-img.png';
    
    $args = array(
        'limit' => -1,
        'status' => 'publish',
        'type' => array('simple', 'variable'),
            //'type' => array('variable'),
    );

    if (isset($data['buscar']) and ! empty($data['buscar'])) {
        $args['s'] = $data['buscar'];
    }
    if (isset($data['categoria']) and ! empty($data['categoria'])) {
        $args['category'] = array($data['categoria']);
    }
    $products = wc_get_products($args);
    $json = array();
    $x = 0;
    foreach ($products as $product) {
        $x++;
        $id = $product->get_id();
        $type = $product->get_type();
        $name = $product->get_name();
        $img_check = get_the_post_thumbnail_url($id, 'thumbnail');
        if(!$img_check){
            $img_url = $noimg;
        }else{
            $img_url = $img_check;
        }
        $terms = get_the_terms($id, 'product_cat');
        $oferta = 'n';
        $precio_antes = '';
        /*
          $_product->get_regular_price();
          $_product->get_sale_price();
          $_product->get_price();
         */
        if ($type == 'variable') {
            $precio1 = wc_price($product->get_variation_price('min', true));
            $precio1 = str_replace('&euro;', '€', $precio1);
            $precio1 = str_replace('&nbsp;', '', $precio1);
            $precio2 = wc_price($product->get_variation_price('max', true));
            $precio2 = str_replace('&euro;', '€', $precio2);
            $precio2 = str_replace('&nbsp;', '', $precio2);
            if ($precio1 != $precio2) {
                $precio = $precio1 . '-' . $precio2;
            } else {
                $precio = $precio1;
            }
        } else {
            if ($product->get_regular_price() != $product->get_price()) {
                $oferta = 's';
                $precio_antes = wc_price($product->get_regular_price());
                $precio_antes = str_replace('&euro;', '€', $precio_antes);
                $precio_antes = str_replace('&nbsp;', '', $precio_antes);
            }
            $precio = wc_price($product->get_price());
            $precio = str_replace('&euro;', '€', $precio);
            $precio = str_replace('&nbsp;', '', $precio);
            $precio = $precio;
        }

        $cat = array();
        foreach ($terms as $term) {
            $cat[] = $term->name;
        }
        $cat = implode(', ', $cat);
        
        $stock = $product->get_stock_quantity();
        $stock_status = $product->get_stock_status();
        
        if($stock_status == "instock"){
            $json[] = array(
                'num' => $x,
                'type' => $type,
                'id' => $id,
                'name' => $name,
                'price' => strip_tags($precio),
                'price_antes' => strip_tags($precio_antes),
                'oferta' => $oferta,
                'img' => $img_url,
                'cat' => $cat,
                'stock' => $stock,
                'stock_status' => $stock_status
            );
        }
    }

    return $json;
}

function redla_feed_categorias() {
    $terms = get_terms(array(
        'taxonomy' => 'product_cat',
        'hide_empty' => false,
    ));
    $cat = array();
    $cat[] = array(
        'value' => '',
        'label' => 'Ver todas',
    );
    foreach ($terms as $term) {
        $cat[] = array(
            //'value' => $term->term_id,
            'value' => $term->slug,
            'label' => $term->name,
        );
    }
    return $cat;
}

function redla_feed_producto($data) {

    $id = $data['post_id'];
    $product = wc_get_product($id);
    if(!$product){
        return array();
    }
    
    $name = $product->get_name();
    $type = $product->get_type();
    $img_url = get_the_post_thumbnail_url($id, 'large');
    if(!$img_url){
        $img_url = REDLA_URL.'images/noimage.png';
    }

    $oferta = 'n';
    $precio_antes = '';
    /*
      $_product->get_regular_price();
      $_product->get_sale_price();
      $_product->get_price();
     */
    if ($type == 'variable') {
        $precio1 = wc_price($product->get_variation_price('min', true));
        $precio1 = str_replace('&euro;', '€', $precio1);
        $precio1 = str_replace('&nbsp;', '', $precio1);
        $precio2 = wc_price($product->get_variation_price('max', true));
        $precio2 = str_replace('&euro;', '€', $precio2);
        $precio2 = str_replace('&nbsp;', '', $precio2);
        if ($precio1 != $precio2) {
            $precio = $precio1 . '-' . $precio2;
        } else {
            $precio = $precio1;
        }
    } else {
        if ($product->get_regular_price() != $product->get_price()) {
            $oferta = 's';
            $precio_antes = wc_price($product->get_regular_price());
            $precio_antes = str_replace('&euro;', '€', $precio_antes);
            $precio_antes = str_replace('&nbsp;', '', $precio_antes);
        }
        $precio = wc_price($product->get_price());
        $precio = str_replace('&euro;', '€', $precio);
        $precio = str_replace('&nbsp;', '', $precio);
        $precio = $precio;
    }



    $descripcion = $product->get_description();
    $descripcion = strip_tags($descripcion);
    if(empty($descripcion)){
        $descripcion = 'Este producto no tiene descripción';
    }
    $images = $product->get_gallery_image_ids();
    $galeria = array();
    $galeria[] = $img_url;
    foreach ($images as $res) {
        $img = wp_get_attachment_image_src($res, 'large');
        $galeria[] = $img[0];
    }
    $url = get_bloginfo('wpurl');
    $json = array(
        'id' => $id,
        'type' => 'simple',
        'name' => $name,
        'img_url' => $img_url,
        'galeria' => $galeria,
        'precio' => strip_tags($precio),
        'price_antes' => strip_tags($precio_antes),
        'oferta' => $oferta,
        'descripcion' => $descripcion,
        'url_cart' => $url,
        'nombre_tienda' => get_bloginfo('name'),
        'id_primer_hijo' => "0",
        'precio_primer_hijo' => strip_tags($precio),
        'name_variation' => '',
        'variation' => array()
    );

    if ($type == 'variable') {
        $json['type'] = 'variable';
        $current_products = $product->get_children();
        foreach ($current_products as $id_variation) {
            $json['variation'][] = redla_get_variation_data_from_variation_id($id_variation,$img_url);
        }
        $json['id_primer_hijo'] = $json['variation'][0]['id'];
        $json['precio_primer_hijo'] = $json['variation'][0]['precio'];
        $json['name_variation'] = $json['variation'][0]['atributos'];
    }
    
    

    return $json;
}

function redla_get_variation_data_from_variation_id($item_id,$img_url) {
    $datos = array();
    $product = new WC_Product_Variation($item_id);
    $variation_data = $product->get_variation_attributes();
    $variation_detail = woocommerce_get_formatted_variation($variation_data, true);  // this will give all variation detail in one line
    //$variation_detail = woocommerce_get_formatted_variation( $variation_data, false);  // this will give all variation detail one by one
    //return $variation_detail; // $variation_detail will return string containing variation detail which can be used to print on website
    //return $variation_data; // $variation_data will return only the data which can be used to store variation data
    if(empty($variation_detail)){
        $variation_detail = ' ';
    }
    $datos['atributos'] = $variation_detail;
    $datos['id'] = strval($item_id);
    $img = get_the_post_thumbnail_url($item_id, 'thumbnail');
    if(!$img){
        $img = $img_url;
    }
    $datos['img_url'] = $img;
    $precio = wc_price($product->get_price());
    $precio = str_replace('&euro;', '€', $precio);
    $precio = str_replace('&nbsp;', '', $precio);
    $datos['precio'] = strip_tags($precio);
    return $datos;
}
