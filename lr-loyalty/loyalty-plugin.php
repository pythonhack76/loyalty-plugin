<?php
/*
Plugin Name: Loyalty Plugin
Plugin URI: http://www.example.com
Description: Un plugin di raccolta punti e programmi fedeltà per premiare i clienti.
Version: 1.0
Author: Tuo Nome
Author URI: http://www.example.com
License: GPLv2 or later
Text Domain: loyalty-plugin
*/

// Funzione per registrare un nuovo cliente
function loyalty_register_customer($customer_id) {
  $customers = get_option('loyalty_customers', array());
  if (!array_key_exists($customer_id, $customers)) {
    $customers[$customer_id] = array(
      'points' => 0,
      'purchases' => array()
    );
    update_option('loyalty_customers', $customers);
  }
}

// Funzione per registrare un acquisto di un cliente
function loyalty_record_purchase($customer_id, $purchase_amount) {
  $customers = get_option('loyalty_customers', array());
  if (array_key_exists($customer_id, $customers)) {
    $customer = $customers[$customer_id];
    $customer['points'] += floor($purchase_amount / 10); // Assegna 1 punto ogni 10 unità di acquisto
    $customer['purchases'][] = $purchase_amount;
    $customers[$customer_id] = $customer;
    update_option('loyalty_customers', $customers);
  }
}

// Funzione per controllare i punti di un cliente
function loyalty_check_points($customer_id) {
  $customers = get_option('loyalty_customers', array());
  if (array_key_exists($customer_id, $customers)) {
    return $customers[$customer_id]['points'];
  }
  return 0;
}

// Funzione per controllare gli acquisti di un cliente
function loyalty_check_purchases($customer_id) {
  $customers = get_option('loyalty_customers', array());
  if (array_key_exists($customer_id, $customers)) {
    return $customers[$customer_id]['purchases'];
  }
  return array();
}

// Funzione per riscattare i punti di un cliente
function loyalty_redeem_points($customer_id, $points_to_redeem) {
  $customers = get_option('loyalty_customers', array());
  if (array_key_exists($customer_id, $customers)) {
    $customer = $customers[$customer_id];
    if ($customer['points'] >= $points_to_redeem) {
      $customer['points'] -= $points_to_redeem;
      // Aggiungi qui la logica per applicare uno sconto o una ricompensa al cliente
      $customers[$customer_id] = $customer;
      update_option('loyalty_customers', $customers);
      return true; // Il riscatto è avvenuto con successo
    }
  }
  return false; // Il cliente non ha abbastanza punti da riscattare
}

// Registra gli shortcode per il plugin
function loyalty_register_shortcodes() {
  add_shortcode('loyalty_points', 'loyalty_display_points');
  add_shortcode('loyalty_purchases', 'loyalty_display_purchases');
  add_shortcode('loyalty_redeem', 'loyalty_display_redeem_form');
}

// Funzione per visualizzare i punti di un cliente
function loyalty_display_points() {
  $customer_id = get_current_user_id(); // Assumi che il cliente sia autenticato nel sistema
  $points = loyalty_check_points($customer_id);
  return 'Punti fedeltà: ' . $points;
}

// Funzione per visualizzare gli acquisti di un cliente
function loyalty_display_purchases() {
  $customer_id = get_current_user_id(); // Assumi che il cliente sia autenticato nel sistema
  $purchases = loyalty_check_purchases($customer_id);
  $output = 'Ultimi acquisti:<ul>';
  foreach ($purchases as $purchase) {
    $output .= '<li>' . $purchase . '</li>';
  }
  $output .= '</ul>';
  return $output;
}

// Funzione per visualizzare il modulo di riscatto punti
function loyalty_display_redeem_form() {
  $customer_id = get_current_user_id(); // Assumi che il cliente sia autenticato nel sistema
  $points = loyalty_check_points($customer_id);
  $output = '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
  $output .= '<input type="hidden" name="action" value="loyalty_redeem">';
  $output .= 'Punti disponibili: ' . $points . '<br>';
  $output .= '<label for="points_to_redeem">Punti da riscattare:</label>';
  $output .= '<input type="number" name="points_to_redeem" id="points_to_redeem" min="1" max="' . $points . '">';
  $output .= '<input type="submit" value="Riscatta">';
  $output .= '</form>';
  return $output;
}

// Registra la gestione dell'azione per il riscatto punti
function loyalty_redeem_action() {
  $customer_id = get_current_user_id(); // Assumi che il cliente sia autenticato nel sistema
  $points_to_redeem = intval($_POST['points_to_redeem']);
  loyalty_redeem_points($customer_id, $points_to_redeem);
  wp_redirect(get_permalink()); // Ridireziona all'URL corrente dopo il riscatto
  exit;
}

// Aggiungi i gestori delle azioni al caricamento di WordPress
add_action('wp_loaded', 'loyalty_register_shortcodes');
add_action('admin_post_loyalty_redeem', 'loyalty_redeem_action');

// Installazione del plugin
function loyalty_install() {
  // Inizializza le opzioni del plugin
  $initial_customers = array(); // Puoi aggiungere eventuali clienti predefiniti qui
  add_option('loyalty_customers', $initial_customers);
}

// Disinstallazione del plugin
function loyalty_uninstall() {
  // Rimuovi le opzioni del plugin
  delete_option('loyalty_customers');
}

// Registra gli hook di attivazione e disattivazione del plugin
register_activation_hook(__FILE__, 'loyalty_install');
register_deactivation_hook(__FILE__, 'loyalty_uninstall');