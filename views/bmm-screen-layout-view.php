<div id="scheme-container">
    <?php
    echo $this->display_scheme_front($_GET['scheme'], $_GET['event']);
    $evendata = $this->get_event_by_id($_GET['event']);
    $orders = $this->get_orders($_GET['scheme'], $_GET['event']);
    ?>
</div>
<div id="shopping-cart-container">
<h2>
   Movie Name : <?php echo $evendata->name; ?>
</h2>
<h2>
    Date : <?php echo date('d/m/Y',strtotime($evendata->start)); ?>
</h2>
<h2>
    Show Timings : <?php echo date('h:i a',strtotime($evendata->start)); ?> - <?php echo date('h:i a',strtotime($evendata->end)); ?>
</h2>
</div>
<p class="clear">
    <a title="Back" href="admin.php?page=bookmymovie-screenlayout" class="button button-primary">
        Back
    </a>
</p>

<div>
    <!--    Order List-->
    <form method="get" action="" id="posts-filter">

        <table cellspacing="0" class="wp-list-table widefat fixed posts">

            <thead>
            <tr>

                <th style="" class="" scope="col">
                    Code
                </th>
                <th style="" class="" scope="col">
                    Movie Name
                </th>
                <th style="" class="" scope="col">
                   Screen Name
                </th>
                <th style="" class="" scope="col">
                    Booked Seats <span title="NAME" style="display: inline-block; vertical-align: middle;" class="ui-icon ui-icon-info places-info-tooltip"></span>
                </th>
                <th style="" class="" scope="col">
                    First Name
                </th>
                <th style="" class="" scope="col">
                  Last Name
                </th>
                <th style="" class="" scope="col">
                  Email
                </th>
                <th style="" class="" scope="col">
                    Phone
                </th>
                <th style="" class="" scope="col">
                    Date
                </th>
                <th style="" class="" scope="col">
                    Total price
                </th>
                <th style="" class="" scope="col">
                    Status
                </th>

            </tr>
            </thead>

            <tfoot>
            <tr>

                <th style="" class="" scope="col">
                 Code
                </th>
                <th style="" class="" scope="col">
                    Movie Name
                </th>
                <th style="" class="" scope="col">
                 Screen Name
                </th>
                <th style="" class="" scope="col">
                    Booked Seats <span title="NAME" style="display: inline-block; vertical-align: middle;" class="ui-icon ui-icon-info places-info-tooltip"></span>
                </th>
                <th style="" class="" scope="col">
                    First Name
                </th>
                <th style="" class="" scope="col">
                   Last Name
                </th>
                <th style="" class="" scope="col">
                   Email
                </th>
                <th style="" class="" scope="col">
                    Phone
                </th>
                <th style="" class="" scope="col">
                    Date
                </th>
                <th style="" class="" scope="col">
                   Total price
                </th>
                <th style="" class="" scope="col">
                   Status
                </th>

            </tr>
            </tfoot>

            <tbody id="the-list">

            <?php if ($orders && is_array($orders)): ?>
                <?php foreach ($orders as $order) : ?>

                    <?php
                    $places = unserialize($order->places);
                    ?>

                    <tr valign="top" class="post-1 type-post status-publish format-standard hentry category-uncategorized alternate iedit author-self" id="post-1">

                        <td class="post-title page-title column-title">
                            <strong><?php echo $order->code; ?></strong>

                            <div class="row-actions">
                                <span class="view"><a rel="permalink" title="View this item" href="<?php echo $this->page_url; ?>&order=<?php echo $order->order_id; ?>&action=view">View</a>  </span> |
                                <span class="trash submitdelete"><a href="<?php echo $this->page_url; ?>&order=<?php echo $order->order_id; ?>&action=delete" title="Delete this item">Delete</a></span>
                            </div>
                        </td>
                        <td class="author column-author">
                            <?php echo $order->event_name; ?>
                        </td>
                        <td class="author column-author">
                            <?php echo $this->get_scheme_by_id($order->scheme_id)->name; ?>
                        </td>
                        <td class="author column-author">
                            <?php if ($places && is_array($places)): ?>
                                <?php foreach ($places as $place_id => $place) : ?>
                                    -&nbsp;<?php echo $place['seat_id'] ? $place['seat_id'] : "Unnamed"; ?><br/>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </td>
                        <td class="author column-author">
                            <?php echo $order->first_name; ?>
                        </td>
                        <td class="categories column-categories">
                            <?php echo $order->last_name; ?>
                        </td>
                        <td class="tags column-tags">
                            <?php echo $order->email; ?>
                        </td>
                        <td class="tags column-tags">
                            <?php echo $order->phone; ?>
                        </td>
                        <td class="tags column-tags">
                            <?php echo $order->date; ?>
                        </td>
                        <td class="tags column-tags">
                            <?php echo '&#8377;'. $order->total_price; ?>
                        </td>
                        <td class="tags column-tags">
                            <?php echo $this->order_statuses[$order->status_id]; ?>
                        </td>

                    </tr>


                <?php endforeach; ?>

            <?php else : ?>

                <tr valign="top" class="post-1 type-post status-publish format-standard hentry category-uncategorized alternate iedit author-self" id="post-1">
                    <td colspan="10">
                        There are no orders yet.
                    </td>
                </tr>

            <?php endif; ?>


            </tbody>

        </table>

    </form>
    <!--    End Order List-->
</div>
