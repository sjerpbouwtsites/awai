<?php

if (!defined('ABSPATH')) {
    echo 'bai';
    exit();
}

/**
 * This function is where we register our routes for our example endpoint.
 */
function awai_register_routes()
{
    // register_rest_route() handles more arguments but we are going to stick to the basics for now.
    register_rest_route('awai/v1', '/get', array(
        // By using this constant we ensure that when the WP_REST_Server changes our readable endpoints will work as intended.
        'methods'  => WP_REST_Server::READABLE,
        // Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
        'callback' => 'awai_get_all_agendas',
    ));

    register_rest_route('awai/v1', '/post', array(
      // By using this constant we ensure that when the WP_REST_Server changes our readable endpoints will work as intended.
      'methods'  => WP_REST_Server::CREATABLE,
      // Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
      'callback' => 'awai_create_agenda',
));
}

add_action('rest_api_init', 'awai_register_routes', 99);

function awai_create_agenda()
{
    if (!$_POST) {
        $res = [
            'err' => 'no request'
        ]        ;
        return $res;
    }

    if (!$_POST['awai-token']) {
        $res = [
            'err' => 'no awai token',
            'request'=> $_POST,
        ];
        return $res;
    }
    if (!awai_verify_token($_POST['awai-token'])) {
        $res = [
            'err' => 'awai token invalid',
            'request'=> $_POST,
        ];
        return $res;
    }


    $term_slugs = awai_get_agenda_term_slugs();

    return $term_slugs;

    try {
        $new_post = array(
            'post_title'    => wp_strip_all_tags("New title"),
            'post_content'  => 'some content',
            'post_status'   => 'publish',
            'post_type'     => 'agenda',
        );

        $new_post_id = wp_insert_post($new_post, true);
    } catch (\Throwable $th) {
        $res = [
            'err' => $th,
            'request'=> $_POST,
        ];
        return $res;
    }

    $date_update_res = update_field('field_61542c48ad4da', "15/11/2038 00:00", $new_post_id);


    $post_plekken = null;
    foreach (['plek', 'type'] as $tax_name) {
        try {
            $post_key = "post-$tax_name";
            if ($_POST[$post_key]) {
                $post_plekken = preg_split("/\s,/g", strtolower($_POST[$post_key]));
                //$post_plekken = explode(',', strtolower($_POST[$post_key]));
                foreach ($post_plekken as $pp) {
                    if (!array_key_exists($pp, $term_slugs[$tax_term])) {
                        wp_insert_term($pp, $tax_term);
                    }
                }
                wp_set_post_terms($new_post_id, $post_plekken, $tax_term);
            }
        } catch (\Throwable $th) {
            $res = [
                'text' => "failed updating agenda $tax_name values",
                'post_plekken' => $post_plekken,
                'bestaande terms' => $term_slugs[$tax_term],
                'err' => $th,
                'request'=> $_POST,
            ];
            return $res;
        }
    }
}

function awai_verify_token($token)
{
    return true;
}

function awai_get_all_agendas()
{
    $agenda = new Ag_agenda(array(
      'aantal' => 100,
      'omgeving' => 'pagina'
    ));

    $return_arr = array();

    foreach ($agenda->agendastukken as $as) {
        $plek = wp_get_post_terms($as->ID, 'plek');
        $type =  wp_get_post_terms($as->ID, 'type');

        $plek_terms_used = array_map(function ($plek_term) {
            return $plek_term->slug;
        }, $plek);

        $type_terms_used = array_map(function ($type_term) {
            return $type_term->slug;
        }, $type);

        if (!function_exists('get_field')) {
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

function create_agenda_from_rest()
{
    return "HALLO";
}


function awai_get_agenda_term_slugs()
{
    $plek_terms = get_terms(array(
      'taxonomy'   => 'plek',
      'hide_empty' => false,
    ));
    $type_terms = get_terms(array(
        'taxonomy'   => 'type',
        'hide_empty' => false,
    ));

    $plek_terms_slugs = array_map(function ($plek_term) {
        return $plek_term->slug;
    }, $plek_terms);

    $type_terms_slugs = array_map(function ($type_term) {
        return $type_term->slug;
    }, $type_terms);

    return [
        'type' => $type_terms_slugs,
        'plek' => $plek_terms_slugs
    ];
}
