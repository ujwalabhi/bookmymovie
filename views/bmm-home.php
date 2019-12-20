<?php
/**
 * Represents the view for the administration dashboard.
 *
 * @package   BookMyMovie
 * @author    Ujwal Abhishek
 * @license   GPL-2.0+
 * @copyright 2019 Ujwal Abhishek
 */
?>
<div>

    <h2><?php echo esc_html(get_admin_page_title()); ?></h2>



        <p>
            Screen is the static schema of the seating arrangement. This data has been created static in the DB for
            representation managed in the scheme, places and the events table.
        </p>

        <p>
            Db schema and sample data is created on plugin activation. The DB schema and dtata is deleted on plugin
            deactivation.
        </p>

        <p>
            To use the plugin please embed the short code in the pages to render the seat booking layout and carts
        </p>



</div>

<div >
    <hr>
    <h2>Short Code</h2>
    <img src="<?php echo BMM_DIR_URL; ?>/img/instruction.png" style="width: 100%"/>
    <hr>
    <h2>Screen</h2>
    <img src="<?php echo BMM_DIR_URL; ?>/img/screen.png"/>
    <hr>
    <h2>View Bookings</h2>
    <img src="<?php echo BMM_DIR_URL; ?>/img/bookings.png"/>

</div>





