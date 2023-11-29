<?php

require __DIR__ . '/vendor/autoload.php';

if (!defined('ABSPATH')) {
    echo 'baibai';
    exit();
}

use HeadlessChromium\BrowserFactory;

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

    register_rest_route('awai/v1', '/challenge', array(
        // By using this constant we ensure that when the WP_REST_Server changes our readable endpoints will work as intended.
        'methods'  => WP_REST_Server::CREATABLE,
        // Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
        'callback' => 'awai_monday_challenge',
    ));
}

add_action('rest_api_init', 'awai_register_routes', 99);

function awai_monday_challenge(WP_REST_Request $req)
{
    $res = json_decode($req->get_body());

    $logtext = json_encode($res, JSON_PRETTY_PRINT);
    $myfile = fopen(__DIR__."/post-log.json", "w") or die("Unable to open file!");
    fwrite($myfile, $logtext);
    fclose($myfile);

    $doc_url = $res->event->value->value;
    ob_start();
    echo "<pre>";
    var_dump($doc_url);
    echo "</pre>";
    $html = ob_get_clean();
    $html_file = fopen(__DIR__."/post-html.html", "w") or die("Unable to open file!");
    fwrite($html_file, $html);
    fclose($html_file);

    $browserFactory = new BrowserFactory();

    // starts headless Chrome
    $browser = $browserFactory->createBrowser();

    try {
        // creates a new page and navigate to an URL
        $page = $browser->createPage();
        $page->navigate($doc_url)->waitForNavigation(Page::DOM_CONTENT_LOADED, 10000);

        // get page title
        $pageTitle = $page->evaluate('document.title')->getReturnValue();
        $image = $page->evaluate("document.querySelector('.file-image')")->getReturnValue();

        $html_file2 = fopen(__DIR__."/post-html2.html", "w") or die("Unable to open file!");
        $html2 = $pageTitle + $image;
        fwrite($html_file2, $html);
        fclose($html_file2);
    } finally {
        // bye
        $browser->close();
    }

    $responds = new WP_REST_Response($response);
    $responds->set_status(400);
    return $responds;
}

function awai_create_agenda(WP_REST_Request $req)
{
    $json_data = $req->get_json_params();

    $res = new WP_REST_Response($response);
    $res->set_status(200);

    return $res;

    if (!$json_data) {
        $res = [
            'success' => false,
            'err' => 'no request'
        ]        ;
        return $res;
    }

    if ($json_data['challenge']) {
        return json_encode($json_data);
    }

    if (!$json_data['awai-token']) {
        $res = [
            'success' => false,
            'err' => 'no awai token',
            'request'=> $json_data,
        ];
        return $res;
    }
    if (!awai_verify_token($json_data['awai-token'])) {
        $res = [
            'success' => false,
            'err' => 'awai token invalid',
            'request'=> $json_data,
        ];
        return $res;
    }

    $title = !!$json_data['post-title'] ? $json_data['post-title'] : 'some title';
    $content = !!$json_data['post-content'] ? $json_data['post-content'] : 'deze content';
    $datum = !!$json_data['post-start-date'] ? $json_data['post-start-date'] : '15/11/2050';
    $tijd = !!$json_data['post-start-tijd'] ? $json_data['post-start-tijd'] : '20:00';
    $date_time = "$datum $tijd";


    $term_slugs = awai_get_agenda_term_slugs();

    try {
        $new_post = array(
            'post_title'    => wp_strip_all_tags($title),
            'post_content'  => $content,
            'post_status'   => 'publish',
            'post_type'     => 'agenda',
        );

        $new_post_id = wp_insert_post($new_post, true);
    } catch (\Throwable $th) {
        $res = [
            'success' => false,
            'err' => $th,
            'request'=> $_POST,
        ];
        return $res;
    }

    $date_update_res = update_field('field_61542c48ad4da', $date_time, $new_post_id);

    $post_plekken = null;
    foreach (['plek', 'type'] as $tax_name) {
        $post_key = "post-$tax_name";
        if ($_POST[$post_key]) {
            $tax_term_dirty_names = explode(',', $_POST[$post_key]);

            foreach ($tax_term_dirty_names as $dirty_name) {
                $slug = sanitize_title($dirty_name);
                wp_set_post_terms($new_post_id, $slug, $tax_name, true);
            }
        }
    }



    $res = [
        'success' => true,
        'created_post'=> $new_post_id,
        'post_data' => $_POST,
        'new_post'=> get_post($new_post_id),
        'salt' => NONCE_SALT,
    ];
    return $res;
}

function awai_verify_token($token)
{
    $salt = urldecode($token);
    return $salt === NONCE_SALT;
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

    $plek_terms_slugs = [];
    foreach ($plek_terms as $pt) {
        $plek_terms_slugs[] = $pt;
    }
    $type_terms_slugs = [];
    foreach ($type_terms as $tt) {
        $type_terms_slugs[] = $tt;
    }

    return [
        'type' => $type_terms_slugs,
        'plek' => $plek_terms_slugs,
    ];
}
