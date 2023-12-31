<?php

require __DIR__ . '/vendor/autoload.php';

if (!defined('ABSPATH')) {
    echo 'baibai';
    exit();
}

use HeadlessChromium\BrowserFactory;
use HeadlessChromium\Page;

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

    $doc_url = null;
    try {
        $doc_url = $res->event->value->value;
        if (!$doc_url) {
            return;
        }
    } catch (\Throwable $th) {
        return;
        ;
    }

    $browserFactory = new BrowserFactory();

    // starts headless Chrome
    $browser = $browserFactory->createBrowser();

    $page_title = 'nog niets';
    try {
        $page_title = $res->event->pulseName;
    } catch (\Throwable $th) {
        //throw $th;
    }

    try {
        // creates a new page and navigate to an URL
        $page = $browser->createPage();
        $page->navigate($doc_url)->waitForNavigation(Page::NETWORK_IDLE, 10000);

        // get page title

        $doc_op = "document.querySelector('.file-image')?.src || 'geen image'";
        $image = $page->evaluate($doc_op)->getReturnValue();

        $remove_image = "
        const firstBlock = document.querySelector('.blocks-list .block-container');
        firstBlock.parentNode.removeChild(firstBlock);
        ";
        $page->evaluate($remove_image);

        $body_html = $page->evaluate("document.querySelector('.blocks-list').innerHTML")->getReturnValue();
        $body_html=preg_replace('/class=".*?"/', '', $body_html);
        $body_html=preg_replace('/data-block-id=".*?"/', '', $body_html);
        $body_html=preg_replace('/contenteditable=".*?"/', '', $body_html);
        $body_html=preg_replace('/data-gramm=".*?"/', '', $body_html);
        $body_html=preg_replace('/tabindex=".*?"/', '', $body_html);
        $body_html=preg_replace('/style=".*?"/', '', $body_html);
        $body_html=preg_replace('/data-cy=".*?"/', '', $body_html);
        $body_html=preg_replace('/\s{2,50}/', ' ', $body_html);
        $body_html = preg_replace('/\<[\/]{0,1}div[^\>]*\>/i', '', $body_html);


        $args = array(
            'post_type' => 'post',// your post type,
            'orderby' => 'post_date',
            'post_status' => array('publish', 'pending', 'draft'),
            'order' => 'DESC',
            'cat' => 27
        );
        $newsletter_posts = get_posts($args);

        $post_id = 0;
        if (count($newsletter_posts) > 0) {
            foreach ($newsletter_posts as $np) {
                if ($np->post_title === $page_title) {
                    $post_id = $np->ID;
                    break;
                }
            }
        }

        $html_file2 = fopen(__DIR__."/post-html2.html", "w") or die("Unable to open file!");
        ob_start();
        echo "DIT IS M";
        var_dump($body_html);
        $html2 = ob_get_clean();

        fwrite($html_file2, $html2);
        fclose($html_file2);

        wp_insert_post([
            'ID'            => $post_id,
            'post_author'   => 5,
            'post_content'  => $body_html,
            'post_title'    => $page_title,
            'post_category' => [27],
            'post_status'   => 'publish'
        ], true);
    } finally {
        // bye
        $browser->close();
    }

    $responds = new WP_REST_Response(json_encode([
        'success' => true
    ]));
    $responds->set_status(200);
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
