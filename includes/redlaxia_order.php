<?php

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/*
 * Marcar pedidos realizados desde la app, item comprados, etc
 * Añadir columna en listado de pedidos
 */


/*
 * Item comprados desde redlaxia
 */
add_action('woocommerce_checkout_create_order_line_item', 'wdm_add_custom_order_line_item_meta', 10, 4);
function wdm_add_custom_order_line_item_meta($item, $cart_item_key, $values, $order) {

    $session_var = 'set_redlaxia_order';
    $session_data = WC()->session->get($session_var);
    if (!empty($session_data)) {
        $item->add_meta_data('_redla_app_order', 'redlaxia');
    }
}

/*
 * Orden creada correctamente desde redlaxia
 */
add_action('woocommerce_checkout_create_order', 'before_checkout_create_order', 20, 2);
function before_checkout_create_order( $order, $data ) {
    $session_var = 'set_redlaxia_order';
    $session_data = WC()->session->get($session_var);
    if (!empty($session_data)) {
        $order->update_meta_data( '_redla_app_order_ok', 'redlaxia' );
        WC()->session->__unset($session_var);
    }
}

/*
 * Añadir columna en listado de pedidos para ver los pedidos realizados con Redlaxia
 */
add_filter( 'manage_edit-shop_order_columns', 'redla_custom_shop_order_column', 20 );
function redla_custom_shop_order_column($columns)
{
    $reordered_columns = array();

    // Inserting columns to a specific location
    foreach( $columns as $key => $column){
        $reordered_columns[$key] = $column;
        if( $key ==  'order_status' ){
            // Inserting after "Status" column
            $reordered_columns['redlaxia'] = 'Redlaxia';
        }
    }
    return $reordered_columns;
}

// Adding custom fields meta data for each new column (example)
add_action( 'manage_shop_order_posts_custom_column' , 'redla_custom_orders_list_column_content', 20, 2 );
function redla_custom_orders_list_column_content( $column, $post_id )
{
    switch ( $column )
    {
        case 'redlaxia' :
            // Get custom post meta data
            $my_var_one = get_post_meta( $post_id, '_redla_app_order_ok', true );
            if(!empty($my_var_one)){
                $url = plugins_url( 'redlaxia/images/logo-r-menu.png' );
                echo '<img src="'.$url.'" width="16">';
            }else{
                echo '-';
            }
            break;

    }
}

