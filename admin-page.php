<?php

function awai_admin_page_func()
{
    $plek_terms = get_terms(array(
        'taxonomy'   => 'plek',
        'hide_empty' => false,
    ));
    $type_terms = get_terms(array(
        'taxonomy'   => 'type',
        'hide_empty' => false,
    ));

    $plek_terms_used = array_map(function ($plek_term) {
        return $plek_term->slug;
    }, $plek_terms);

    $type_terms_used = array_map(function ($type_term) {
        return $type_term->slug;
    }, $type_terms);

    if ($_POST && array_key_exists('nonce', $_POST)) {
        if (! wp_verify_nonce($_POST['nonce'], 'my-nonce')) {
            awai_admin_message('NOT VERIFIED NONCE', $status = 'error');
        } else {
            $new_post = array(
                'post_title'    => wp_strip_all_tags($_POST['post-title']),
                'post_content'  => $_POST['post-content'],
                'post_status'   => 'publish',
                'post_author'   => 1,
                //'post_category' => array( 8,39 )
                'post_type'     => 'agenda',
            );

            $new_post_id = wp_insert_post($new_post, true);

            $date_update_res = update_field('field_61542c48ad4da', "15/11/2038 00:00", $new_post_id);

            if ($_POST['post-plek']) {
                $post_plekken = explode(',', strtolower($_POST['post-plek']));
                foreach ($post_plekken as $pp) {
                    if (!array_key_exists($pp, $plek_terms_used)) {
                        wp_insert_term($pp, 'plek');
                    }
                }
                wp_set_post_terms($new_post_id, $post_plekken, 'plek');
            }

            if ($_POST['post-type']) {
                $post_typen = explode(',', strtolower($_POST['post-type']));
                foreach ($post_typen as $pt) {
                    if (!array_key_exists($pt, $type_terms_used)) {
                        wp_insert_term($pt, 'type');
                    }
                }
                wp_set_post_terms($new_post_id, $post_typen, 'type');
            }


            awai_admin_message("Made post $new_post_id", $status = 'success');
        }
    }


    $nonce = wp_create_nonce('my-nonce');

    echo "<div id='wpbody' role='main'>
    <div id='wpbody-content'>
  
      <div class='wrap'>

        <h1 class='wp-heading-inline'>
          Wp Monday integratie test admin pagina
        </h1>";


    echo "<p>Gebruik de volgende token in Monday: <textarea style='display: block; min-width: 1200px; font-size: 8px; height: 60px; padding: 20px; margin: 20px;'>".urlencode(NONCE_SALT)."</textarea></p>";


    echo "
            <form method='POST' action='https://sjerpvanwouden.nl/oyvey/wp-json/awai/v1/post'>
                <input type='hidden' value='lenN!70z!C?$}i$Nnu;<3+8cQv0$-do^.C^0pwi8t6:WqtdTHQ9/iY)36LS~MN&B' name='awai-token'><br>
                <table class='form-table' role='presentation'>
                    <tbody>
                    ".awai_form_input('text', 'post-title', 'Post Title')."<br><br>
                    ".awai_form_input('text', 'post-content', 'Post Content')."<br><br>
                    ".awai_form_input('text', 'post-plek', 'Plek')."<br><br>
                    ".awai_form_input('text', 'post-type', 'Type')."<br><br>
                    ".awai_form_input('date', 'post-start-date', 'Datum')."                    

                    </tbody>
                <table>
                <p class='submit'><input type='submit' name='submit' id='submit' class='button button-primary' value='verstuur'></p>
            </form>";


    echo "      </div>
    </div>
   </div>";
}

function awai_form_input($type, $name, $label_text)
{
    $rand = rand(0, 100);

    $input = '';
    if ($type === 'date') {
        $input = "<input name='$name' type='$type' id='$name' class='regular-text'>";
    } else {
        $input = "<input name='$name' type='$type' id='$name' value='$name-$rand' class='regular-text'>";
    }
    return "
    <tr>    
    <th scope='row'>
        <label for='$name'>$label_text</label>
    </th>
    <td>$input</td>
</tr>
    ";
}

function awai_admin_page_register()
{
    add_menu_page(
        'awai_admin_page_func',
        'WP monday',
        'manage_options',
        'awai_admin',
        'awai_admin_page_func',
        plugins_url('agitatie-wp-agenda-integration/awai-36-34.png'),
        99
    );
}

add_action('admin_menu', 'awai_admin_page_register');
