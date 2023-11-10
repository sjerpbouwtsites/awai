<?php


function awai_admin_message($etx, $status = 'success')
{
    ?>
    <div class="notice notice-<?php echo $status;?> is-dismissible">
        <p>WP Monday: <?php echo $etx; ?></p>
    </div>
    <?php
}
function awai_post_nonce_unverified()
{
    awai_admin_message('Nonce fout!', $status = 'error');
}
function awai_post_nonce_verified()
{
    awai_admin_message('Nonce OK!', $status = 'success');
}
