<?php

function awai_admin_nonce_page()
{
    echo "<div id='wpbody' role='main'>
    <div id='wpbody-content'>
  
      <div class='wrap'>

        <h1 class='wp-heading-inline'>
          Code zodat monday wordpress kan valideren
        </h1>";
    echo "<p>Gebruik de volgende token in Monday: <textarea style='display: block; min-width: 1200px; font-size: 10px; height: 60px; padding: 20px; margin: 20px;'>".urlencode(NONCE_SALT)."</textarea></p>";

    echo "</div></div>";
}

function awai_admin_debug_page()
{
    awai_admin_message('Het volgende is alleen voor test en development redenen hier. Niet gebruiken thx.', $status = 'warning');

    echo "<div id='wpbody' role='main'>
    <div id='wpbody-content'>
  
      <div class='wrap'>

        <h1 class='wp-heading-inline'>
          Wp Monday integratie test admin pagina
        </h1>";


    echo "<p class='submit'><input type='submit' name='submit-2' id='submit-json' class='button button-primary' value='verstuur json challenge'></p>";
    echo "<pre id='print-res' style='height: 500px; width: 800px; background-color: white; padding: 20px;'>
        </pre>";
    echo "<br><br><hr><br><br>";
    echo "
            <form method='POST' action='https://sjerpvanwouden.nl/oyvey/wp-json/awai/v1/post'>
                <input type='hidden' value='lenN%2170z%21C%3F%24%7Di%24Nnu%3B%3C3%2B8cQv0%24-do%5E.C%5E0pwi8t6%3AWqtdTHQ9%2FiY%2936LS%7EMN%26B' name='awai-token'><br>
                <table class='form-table' role='presentation'>
                    <tbody>
                    ".awai_form_input('text', 'post-title', 'Post Title')."<br><br>
                    ".awai_form_input('text', 'post-content', 'Post Content')."<br><br>
                    ".awai_form_input('text', 'post-plek', 'Plek')."<br><br>
                    ".awai_form_input('text', 'post-type', 'Type')."<br><br>
                    ".awai_form_input('date', 'post-start-date', 'Datum')."                    
                    ".awai_form_input('time', 'post-start-time', 'Tijd')."                    

                    </tbody>
                <table>
                <p class='submit'><input type='submit' name='submit' id='submit' class='button button-primary' value='verstuur'></p>
            </form>";




    echo "      </div>
    </div>
   </div>";

    echo "<script>
  
   const submitJsonBtn = document.querySelector('#submit-json');
   const printRes = document.getElementById('print-res');
   const data = JSON.stringify({challenge: 'harry'});
   console.log({sending: data})
   submitJsonBtn.onclick = async (e) => {
    let response = await fetch('https://sjerpvanwouden.nl/oyvey/wp-json/awai/v1/challenge', {
            method: 'Post',
            headers: {
                'Content-Type': 'application/json',
            },
            body: data,
            mode: 'same-origin'
    }).then(res =>{
        return res.text()
    })
    .then(text=>{
        printRes.innerHTML = text;
    })
    .catch(err => {
        console.log(err);
        //printRes.innerHTML = res
    })

    
};   
   
   </script>";
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
        'Awai code page',
        'WP-monday code',
        'manage_options',
        sanitize_title('Awai code page'),
        'awai_admin_nonce_page',
        plugins_url('awai/awai-36-34.png'),
        99
    );
    add_submenu_page('awai-code-page', 'WP monday debug', 'WP monday debug', 'manage_options', sanitize_title('Awai admin debug page'), 'awai_admin_debug_page');
}

add_action('admin_menu', 'awai_admin_page_register');
