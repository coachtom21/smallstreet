<div class="dong-pagination user-trading-list-paginate" style="float:right">
    <?php
        $current_page = isset($_GET['listpaged']) ? (int) $_GET['listpaged'] : 1;
        $num_items = count($args);
        $items_per_page =10;
        $num_pages = ceil($num_items / $items_per_page);
        echo paginate_links(array(
            'base' => add_query_arg('listpaged', '%#%'),
            'format' => 'list',
            'prev_text' => __('&laquo; Previous', 'cpm-dongtrader'),
            'next_text' => __('Next &raquo;', 'cpm-dongtrader'),
            'total' => $num_pages,
            'current' => $current_page,
        ));
    ?>
</div>