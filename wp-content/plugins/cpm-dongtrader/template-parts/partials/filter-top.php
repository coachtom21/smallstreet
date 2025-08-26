<?php 
$get_items = dongtrader_pagination_array($args, $items_per_page = 10 , $items_array=false);
extract($get_items);

?>
<div class="export-section">
    <form id="posts-filter" method="get" action="">
            <label for="filter"><?php _e("Show", 'cpm-dongtrader');?></label>
            <select id="filter" name="filter">
                <option value="all" <?php echo $all_selected; ?>>All</option>
                <option value="within-a-date-range" <?php echo $date_selected; ?>>Within a Date Range</option>
            </select>
            <span id="from" style="display: none;">From</span>
            <input id="start-month" name="start-month" type="date" size="2" value="<?php echo $startdate; ?>" style="display: none;">
            <span id="to" style="display: none;">To</span>
            <input id="end-month" name="end-month" type="date" size="2" value="<?php echo $enddate; ?>" style="display: none;">
            <span id="filterby" style="display: none;">filter by </span>
            <input id="submit" class="button" type="submit" value="Filter">
            <script>
                jQuery(document).ready(function() {
                    jQuery('#filter').change(function() {
                        pmpro_ShowMonthOrYear();
                    });
                });

                function pmpro_ShowMonthOrYear() {
                    var filter = jQuery('#filter').val();
                    if (filter == 'all') {
                        jQuery('#start-month').hide();
                        jQuery('#start-day').hide();
                        jQuery('#start-year').hide();
                        jQuery('#end-month').hide();
                        jQuery('#end-day').hide();
                        jQuery('#end-year').hide();
                        jQuery('#predefined-date').hide();
                        jQuery('#status').hide();
                        jQuery('#l').hide();
                        jQuery('#discount-code').hide();
                        jQuery('#from').hide();
                        jQuery('#to').hide();
                        jQuery('#submit').show();
                        jQuery('#filterby').hide();
                    } else if (filter == 'within-a-date-range') {
                        jQuery('#start-month').show();
                        jQuery('#start-day').show();
                        jQuery('#start-year').show();
                        jQuery('#end-month').show();
                        jQuery('#end-day').show();
                        jQuery('#end-year').show();
                        jQuery('#predefined-date').hide();
                        jQuery('#status').hide();
                        jQuery('#l').hide();
                        jQuery('#discount-code').hide();
                        jQuery('#submit').show();
                        jQuery('#from').show();
                        jQuery('#to').show();
                        jQuery('#filterby').hide();
                    }
                }

                pmpro_ShowMonthOrYear();
            </script>
        </form>
</div>