<?php 

/**
 * This function is where we register our routes for our example endpoint.
 */
function awai_register_routes() {
  // register_rest_route() handles more arguments but we are going to stick to the basics for now.
  register_rest_route( 'awai/v1', '/get', array(
      // By using this constant we ensure that when the WP_REST_Server changes our readable endpoints will work as intended.
      'methods'  => WP_REST_Server::READABLE,
      // Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
      'callback' => 'awai_get_all_agendas',
  ) );

  register_rest_route( 'awai/v1', '/post', array(
    // By using this constant we ensure that when the WP_REST_Server changes our readable endpoints will work as intended.
    'methods'  => WP_REST_Server::READABLE,
    // Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
    'callback' => 'awai_create_agenda',
) );

}

add_action( 'rest_api_init', 'awai_register_routes' );

function awai_create_agenda(){
  //
}

function awai_get_all_agendas(){

  $agenda = new Ag_agenda(array(
    'aantal' => 100,
    'omgeving' => 'pagina'
  ));

  $return_arr = array();

  foreach($agenda->agendastukken as $as) {

    $plek = wp_get_post_terms($as->ID, 'plek');
    $type =  wp_get_post_terms($as->ID, 'type');

    $plek_terms_used = array_map(function($plek_term){
        return $plek_term->slug;
    }, $plek);

    $type_terms_used = array_map(function($type_term){
        return $type_term->slug;
    }, $type);

    if (!function_exists('get_field')){
      echo "ERR";
    }

    $return_arr[] = array(
      'WP_id'   => $as->ID,
      'title'   => $as->post_title,
      'content' => $as->post_content,
      'plek'    => $plek_terms_used,
      'type'    => $type_terms_used,
      'event_date'    => get_field('datum', $as->ID),
      'image'   => get_the_post_thumbnail_url($as),
      'summary' => $as->post_excerpt
   );
 }
 return $return_arr;

}    

function create_agenda_from_rest(){
    return "HALLO";
}