<?php

function awai_register_plugin_page()
{
    add_action('admin_notices', 'awai_admin_register_success_message');
}
function awai_admin_register_success_message()
{
    awai_admin_message('Registratie succesvol.', $status = 'success');
}

register_activation_hook(__FILE__, 'awai_register_plugin_page');
