<div class="wrap">
    <h2><?php echo esc_html(get_admin_page_title()); ?></h2>

    <?php

if (isset($_GET['action']) && $_GET['action'] == 'view') {

        require_once(BMM_DIR_PATH . 'views/bmm-screen-layout-view.php');

    }else{
        require_once(BMM_DIR_PATH . 'views/bmm-screen-layout-list.php');
    }

    ?>

</div>