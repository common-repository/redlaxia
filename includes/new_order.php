<?php

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/*
 * Crear nuevo pedido de cliente
 */

add_action('template_redirect', 'redla_order_woocommerce');

function redla_order_woocommerce() {

    if (isset($_GET['senciya_order']) and ! empty($_GET['senciya_order'])) {
        $ids = sanitize_text_field($_GET['senciya_order']);
        $feed = new Redla_order_woocommerce_class();
        $feed->ids = $ids;
        $feed->redla_order();
    }
    if (isset($_GET['senciya_order_now']) and ! empty($_GET['senciya_order_now'])) {
        $id_padre = sanitize_text_field($_GET['senciya_order_now']);
        $id_hijo = sanitize_text_field($_GET['id_child']);
        $cant = sanitize_text_field($_GET['cant']);
        if (is_numeric($cant) and is_numeric($id_padre) and is_numeric($id_hijo)) {
            $feed = new Redla_order_woocommerce_class();
            $feed->id_padre = $id_padre;
            $feed->id_hijo = $id_hijo;
            $feed->cant = $cant;
            $feed->redla_order_now();
        }
    }
}

class Redla_order_woocommerce_class {
    /*
     * $variation_id = 2248;
     * $variation = wc_get_product($variation_id);
     * var_dump($variation->get_parent_id());
     */

    var $id_padre;
    var $id_hijo;
    var $cant;
    var $ids;

    function __construct() {
        return true;
    }

    //WC_Cart::add_to_cart( $product_id, $quantity, $variation_id, $variation, $cart_item_data );
    public function redla_order_now() {
        WC()->cart->empty_cart();
        if ($this->id_hijo == "0") {
            WC()->cart->add_to_cart($this->id_padre, $this->cant);
        } else {
            WC()->cart->add_to_cart($this->id_padre, $this->cant, $this->id_hijo);
        }
        $this->go_to_checkot();
    }

    public function redla_order() {
        WC()->cart->empty_cart();
        $ids = $this->ids;
        $productos = explode('b', $ids);
        $productos = array_filter($productos);
        foreach ($productos as $res) {
            list($id_padre, $cant, $id_hijo) = explode('a', $res);
            if (is_numeric($cant) and is_numeric($id_padre) and is_numeric($id_hijo)) {
                if ($id_hijo == "0") {
                    WC()->cart->add_to_cart($id_padre, $cant);
                } else {
                    WC()->cart->add_to_cart($id_padre, $cant, $id_hijo);
                }
            }
        }
        $this->go_to_checkot();
    }

    public function go_to_checkot() {
        /*
         * Guardamos en sesion el carrito de redlaxia
         */
        WC()->session->set( 'set_redlaxia_order', array('redlaxia' => true));
        /*
         * Url para finalizar compra
         */
        $url = wc_get_checkout_url();
        wp_safe_redirect($url);
        exit();
    }

}
