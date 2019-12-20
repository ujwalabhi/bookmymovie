<?php

/*
 * Select Schemes
 */

$schemes = $this->get_schemes();

?>
<div class="wrap">
<h2><?php echo esc_html(get_admin_page_title()); ?></h2>

<!--    Scheme List-->
<form method="get" action="" id="posts-filter">

    <table cellspacing="0" class="wp-list-table widefat fixed posts">

        <thead>
        <tr>

            <th style="" class="manage-column column-title sortable desc" id="title" scope="col">
                <a href="">
                    <span>Name</span><span class="sorting-indicator"></span>
                </a>
            </th>
            <th style="" class="manage-column column-event" scope="col">
                Event
            </th>


            <th style="" class="manage-column column-shortcode" id="tags" scope="col">
                Shortcode
            </th>

        </tr>
        </thead>

        <tfoot>
        <tr>

            <th style="" class="manage-column column-title sortable desc" scope="col">
                <a href="">
                    <span>Name</span><span class="sorting-indicator"></span>
                </a>
            </th>
            <th style="" class="manage-column column-event" scope="col">
                Event
            </th>


            <th style="" class="manage-column column-shortcode" scope="col">
                Shortcode
            </th>

        </tr>
        </tfoot>

        <tbody id="the-list">

        <?php if ($schemes && is_array($schemes)): ?>
            <?php foreach ($schemes as $scheme) : ?>

                <tr valign="top" class="post-1 type-post status-publish format-standard hentry category-uncategorized alternate iedit author-self" id="post-1">

                    <td class="post-title page-title column-title">
                        <strong>
                            <a title="View Screen Layout" href="<?php echo $this->page_url; ?>&scheme=<?php echo $scheme->scheme_id; ?>&event=<?php echo $scheme->event_id; ?>&action=view" class="row-title">
                                <?php echo $scheme->name; ?>
                            </a>
                        </strong>


                    </td>
                    <td class="event column-event">
                        <?php if ($scheme->event) : ?>
                            <?php echo $scheme->event; ?><br>
                            (<?php echo $scheme->start; ?>)
                        <?php else : ?>
                            -
                        <?php endif; ?>
                    </td>


                    <td class="tags column-shortcodes">

                        <?php echo '[book_my_movie_event id="' . $scheme->event_id . '"]'; ?>
                    </td>

                </tr>


            <?php endforeach; ?>

        <?php else : ?>

            <tr valign="top" class="post-1 type-post status-publish format-standard hentry category-uncategorized alternate iedit author-self" id="post-1">
                <td colspan="4">
                   There are no schemes yet.
                </td>
            </tr>

        <?php endif; ?>


        </tbody>

    </table>

</form>
<!--    End Scheme List-->
<p>
    <b>Note:</b> This data is static and populated from the db. Add, Edit and Delete functionality not provided.
</p>
</div>