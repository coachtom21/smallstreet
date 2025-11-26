<?php
/**
 * CPM Dongtrader MSC Functions
 * 
 * @package CPM_Dongtrader
 * @since 1.0.0
 */

//Cronjob filters


/**
 * Interval Settings filters For the cron job
 *
 * @param [array] $schedules
 * @return array
 */
function dongtrader_one_minutes_interval($schedules)
{

    $schedules['5_minutes'] = array(
        'interval' => 5 * 60,
        'display' => __('Every 5 minutes', 'cpm-dongtrader'),
    );

    return $schedules;
}

add_filter('cron_schedules', 'dongtrader_one_minutes_interval');

/**
 * If the cron job isn't scheduled, schedule it.
 */
add_action('wp', 'dongtrader_schedule_cron_job');
function dongtrader_schedule_cron_job()
{
    if (!wp_next_scheduled('dongtrader_cron_job_hook')) {

        wp_schedule_event(time(), '5_minutes', 'dongtrader_cron_job_hook');

    }
}

function delete_data(){
   
 
    $users = [293, 294, 295];
    foreach($users as $u){
         delete_user_meta( $u,  '_buyer_details' );
         delete_user_meta( $u,  '_commission_details' );
         delete_user_meta( $u,  '_treasury_details' );
         delete_user_meta( $u,  '_group_details' );
    }

}

/**
 * Add a custom hook to the cron job, and then run a function when that hook is called.
 */
 
 add_action('dongtrader_cron_job_hook', 'dongtrader_cron_job');
 
//  add_action('wp_head' , 'dongtrader_cron_job');
function dongtrader_cron_job()
{

    mega_rotate_leadership();
    glassfrog_api_get_persons_of_circles();
    mega_save_price_allocation_to_group_members();

}


/**
 *Communicates and syncs with glassfrog api
 */
function glassfrog_api_get_persons_of_circles()
{
    global $wpdb;

    // Initialize an empty array to store members
    $members = array(); 

    // Our Custom table name from the database.
    $mega_mlm_users = $wpdb->prefix . 'mega_mlm_customers';

    // get glassfrog id and user id from custom table manage_glassfrogs_api
    $results = $wpdb->get_results("SELECT user_id, glassfrog_person_id FROM $mega_mlm_users WHERE user_status = 0 LIMIT 5 ", ARRAY_A);
    
    // if no results, exit
    if (!$results){
        return ;
    } 
    
    // extract glassfrog id from the results in the above custom query
    $glassfrog_ids = wp_list_pluck($results, 'glassfrog_person_id');

    // extract user id from the results in the above custom query
    $user_ids = wp_list_pluck($results, 'user_id');

    // combine whole array into one
    $all_users = array_combine($glassfrog_ids, $user_ids);

    // looping inside our all users
    foreach ($all_users as $gfid => $uid) {

        // call the glassfrog api
        $api_call = glassfrog_api_request('people/' . $gfid . '/roles', '');
        
        // vdd($api_call);
        
        // check if api call is successful
        if ($api_call) {

            // get all people of the circle from api obj
            $all_people_in_circle = $api_call->linked->people;

            // coun t all numbers of people inside the circle 
            $count_people_in_circle = count($all_people_in_circle);
 
            // check if five members rule is accomplished in the circle
            if ($count_people_in_circle == 5) {

                // exact circle name in the api from api obj
                $peoples_circle_name = $api_call->roles[0]->name;

                // circle id 
                $circle_id = $api_call->roles[0]->id;

                // Initialize an empty array to store user IDs
                $user_ids_in_circle = array(); 

                // looping inside the circle
                foreach ($all_people_in_circle as $ap) {
                
                    // update to custom database
                    $update_query = $wpdb->prepare("UPDATE $mega_mlm_users SET user_status = %d , circle_id= %d  WHERE user_id = %d", 1, $circle_id, $uid);
                
                    $wpdb->query($update_query);

                    // get wp user id stored as external id from the api
                    $user_ids_in_circle[] = $ap->external_id;
                }

                if (!empty($user_ids_in_circle)) {

                    $members[$circle_id] = array(
                        'user_id'       => $user_ids_in_circle,
                        'circle_id'     => $circle_id,
                        'circle_name'   => $peoples_circle_name
                    );
                }

            }
        }
    }
    

    mega_insert_group_details_to_db($members);
}

function mega_insert_group_details_to_db($members){

    if(empty($members) && count($members) != 5) return;

    global $wpdb;

    $user_details = $wpdb->prefix . 'mega_mlm_customers';

    $group_details    = $wpdb->prefix . 'mega_mlm_groups';

    foreach ($members as $key=>$val) {

        // Get the current date and the date one month from now
        $created_date = date('Y-m-d H:i:s');

        $expires_date = date('Y-m-d H:i:s', strtotime('+1 month'));

        // Set the group leader as the first user in the array
        $group_leader = $val['user_id'][0];

        //check if group exists
        $check_group = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $group_details WHERE circle_id=%d",(int) $key)
        );

        // if group exists do not create it
        if(!is_null($check_group)) continue;
            
            // Insert the data into the group details table
            $wpdb->insert($group_details, array(
                    'circle_id'     => (int) $key,
                    'circle_name'   => $val['circle_name'],
                    'created_date'  => sanitize_text_field($created_date),
                    'group_leader'  => $group_leader,
                    'leader_since'  => $created_date,
                    'leadership_expires'  => $expires_date,
                    'distribution_status' => 0
            ));
        

        // get group id from query
        $group_details_id = (int) $wpdb->get_var("SELECT group_id FROM $group_details WHERE circle_id=$key");

        for($i=0; $i<=count($val['user_id'])-1; $i++){

            // query to update all user with their group id
            $update_user_group = $wpdb->prepare("UPDATE $user_details SET customer_group_id = %d  WHERE user_id = %d", $group_details_id, (int) $val['user_id'][$i]);   
            
            $wpdb->query($update_user_group);
        }
    }
}

/**
 * Rotate leaderships from database created from the glassfrog  
 *
 * @return void          
 */

function mega_rotate_leadership(){

    global $wpdb;

    $mega_mlm_groups = $wpdb->prefix . 'mega_mlm_groups';

    $mega_mlm_customers = $wpdb->prefix . 'mega_mlm_customers';

    $current_leaders = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM $mega_mlm_groups WHERE leadership_expires <= %s",
            current_time('mysql')
        )
    );

    if (!empty($current_leaders)) {

        foreach ($current_leaders as $current_leader) {

            // all members from a group
            $all_members = $wpdb->get_results($wpdb->prepare("SELECT user_id FROM $mega_mlm_customers WHERE customer_group_id = %d",  (int) $current_leader->group_id),ARRAY_A);

            if(sizeof($all_members) === 0) continue;
            // all users id from a group
            $group_array = array_column($all_members, "user_id");

            //get indexe position of user in the array 
            $current_leader_index = array_search($current_leader->group_leader, $group_array);

            //the total members in an  array
            $total_members = count($group_array);

            if ($current_leader_index !== false && $current_leader_index + 1 < $total_members) :

                //set new leader that needs to be updated in dbase
                $new_leader = $group_array[$current_leader_index + 1];

            else :
                
                //create new array if not fount
                $new_leader = $group_array[0];

            endif;

            // Sanitize the new leader and the updated date
            $new_leader = intval($new_leader);
            
             // Assuming the leader ID is an integer
            $updated_date = current_time('mysql');

            // Calculate the new expiry date (1 month from the updated date)
            $expiry_date = date('Y-m-d H:i:s', strtotime('+1 month', strtotime($updated_date)));

            //update to the database
            $result = $wpdb->update(
                $mega_mlm_groups,
                array(
                    'group_leader' => $new_leader,
                    'leader_since' => $updated_date, // Update the leader_since column with the new date
                    'leadership_expires' => $expiry_date // Update the leadership_expires column with the new expiry date
                ),
                array('group_id' => $current_leader->group_id),
                array(
                    '%d', // leader ID format
                    '%s', // leader_since format
                    '%s' // leadership_expires format
                ),
                array('%d') // id format
            );
        }
    }
}

function dongtrader_get_orders_by_user($user_id , $group_table_orders) {

    if(empty($user_id) && empty($group_table_orders)) return;
   
    // Create an instance of WC_Order_Query
    $order_query = new WC_Order_Query(array(
        'limit' => -1,
        'customer_id' => $user_id,
        'status' => 'wc-completed',
    ));
    
    // Get the orders
    $orders = $order_query->get_orders();
    
    // Extract order IDs into an array
    $order_ids = array();
    foreach ($orders as $order) {
        $order_ids[] = $order->get_id();
    }
    
    $difference = array_diff($order_ids , $group_table_orders);
    
    return $difference;
}


function mega_save_price_allocation_to_group_members(){

    global $wpdb;

    //get group details table name
    $mlm_groups  = $wpdb->prefix . 'mega_mlm_groups';
    
    //get user detail table name
    $mlm_customer = $wpdb->prefix . 'mega_mlm_customers';

    //get purchase table
    $mlm_purchase =  $wpdb->prefix . 'mega_mlm_purchases';
    
    //sql query to get list of groups and group leader where distribution is not done or just a update is required
    $group_prepared_query = $wpdb->prepare("SELECT group_id , group_leader , distribution_status FROM $mlm_groups WHERE distribution_status=%d OR distribution_status=%d",0,2);
    
    //get results from above sql query
    $group_results = $wpdb->get_results($group_prepared_query, ARRAY_A);

    // if group results is empty exit
    if(empty($group_results)) return;

    $groups_details = array_map(function($group_result) use($wpdb, $mlm_customer , $mlm_groups , $mlm_purchase) {
        
        // all members from a group
        $all_members = $wpdb->get_results($wpdb->prepare("SELECT user_id FROM $mlm_customer WHERE customer_group_id = %d",  (int) $group_result['group_id']),ARRAY_A);

        // all users id from a group prevously group_array
        $all_members_id = array_column($all_members, "user_id");

        //group id from query result
        $group_id = $group_result['group_id'];

        //get distribution status
        $d_status = $group_result['distribution_status'];

        //get all related members from the group
        $related = array_values(array_diff($all_members_id, array($group_result['group_leader'])));

        //get circle name from group_data_table
        $group_name =  $wpdb->get_var($wpdb->prepare("SELECT circle_name FROM $mlm_groups WHERE group_id = %d",  (int) $group_id));
        
        //intilized new array to make its format = [order_id1 => user_id1 , order_id2=>user_id1, order_id3=>user_id2]
        $orders_user = [];

        //intialized new array to make its format = [user_id1 => [order_id2 ,order_id3]]
        $users_order  = [];

        //upline members 
        $upline_members = [];
        
        // sponsor
        $upline_user =[];

        foreach($all_members_id as $user_id) {

            //get all order foreach group members from mega_mlm_purchase table
            $user_orders = $wpdb->get_results($wpdb->prepare("SELECT order_id FROM $mlm_purchase WHERE customer_id = %d AND allocation_status = %d", (int) $user_id, 0), ARRAY_A);

            //get all orders of each users
            $user_orders_id = array_column( $user_orders ,'order_id');

            if(!empty($user_orders_id) ) {

                // get affiliate id 
                $sponsor =  $wpdb->get_var($wpdb->prepare("SELECT upline_id FROM $mlm_customer WHERE user_id = %s",  $user_id));

                // get group id
                $sponsor_group_id = $wpdb->get_var($wpdb->prepare("SELECT customer_group_id FROM $mlm_customer WHERE upline_id = %d", (int) $sponsor));
            
                // get upline group id 
                $upline_group_members = $wpdb->get_results($wpdb->prepare("SELECT user_id FROM $mlm_customer WHERE customer_group_id = %d ", (int) $sponsor_group_id), ARRAY_A);

                $sponsor_group_members = array_column($upline_group_members,'user_id');

                $upline_user[$user_id] = $sponsor;

                $upline_members[$user_id] = $sponsor_group_members;

                // find the diference between orders stored on custom dbase and woocommerce
                $diff = dongtrader_get_orders_by_user($user_id , $user_orders_id);


                if(!empty($diff)){

                 
                    //update $users_order array format = [user_id1 => [order_id2 order_id3]]
                    $users_order[$user_id] = $user_orders_id;
                    
                    foreach($diff as $d){

                        $orders_user[$d] = $user_id;

                        $update = $wpdb->prepare("UPDATE $mlm_purchase SET allocation_status = %d WHERE order_id = %d", 1, (int) $d);
                        
                        $wpdb->query($update);
                    }


                }else{

                    // update $users_order array format = [user_id1 => [order_id2 order_id3]]
                    $users_order[$user_id] = $user_orders_id;

                    //again looping inside serilized orders
                    foreach($user_orders_id as $uo){

                        //update $orders_user in format of [order_id1 => user_id1 , order_id2=>user_id1, order_id3=>user_id2]
                        $orders_user[$uo] = $user_id;
                    }
                }


            }
            
        }

        return array(
            'parent_user_id' => $group_result['group_leader'],
            'related'        => $related,
            'all_members'    => $all_members_id,
            'group_id'       => $group_id,
            'group_name'     => $group_name,
            'user_orders'    => $users_order,
            'orders_user'    => $orders_user,
            'upline_members' => $upline_members,
            'upline_user'    => $upline_user
        );
  
    }, $group_results);

    //looping inside the created array format 
    foreach($groups_details as $gd){
        
        // saving all order details
        // mega_save_order_details($gd['user_orders']);

        // saving all commission details
        mega_save_commission_details($gd['orders_user'], true , (int) $gd['parent_user_id']);

        // save all treasury details
        // mega_save_treasury_details($gd['orders_user'] , $gd['all_members']); 

        // saving all group details
        mega_save_group_details($gd['orders_user'] , (int) $gd['parent_user_id'] , (int) $gd['group_id']);

        // update allocation status of each orders id to 1 wp_mega_mlm_purchases
        mega_update_allocation_status($gd['orders_user']);
        
        // update distribution status
        mega_update_group_distribution_status($gd['group_id']);
    }

}

function mega_save_order_details($user_orders){

   foreach($user_orders as $member=>$order){
   
        $buyer_meta =  get_user_meta($member , '_buyer_details', true);

        //assign empty array if $buyer_meta is empty 
        $buyer_metas = !empty($buyer_meta) ? $buyer_meta : [];

        //get buyer or customer name
        $buyer_name   = dongtrader_check_user($member , false);

        //looping inside all orders of this user
        foreach($order as $ao){
            
            //check if order id exists in database
            if(get_post_type($ao) != 'shop_order') continue;

            //get affiliate from order meta
            $sponsor    = dongtrader_get_order_meta($ao, 'mega_affid');
            
            //cashback amount from order meta distributeable to voter
            $rebate       = dongtrader_get_order_meta($ao, 'mega_cashback_v');
            
            //will next time after reconfirming it 
            $rebate_d       = dongtrader_get_order_meta($ao, 'mega_cashback_d');
            
            // Get membership 
            $membership_name = dongtrader_get_order_meta($ao,'_membership_name'); 
            
            //new array for new order which we will append to 
            $buyer_metas[] = [
                'order_id'      => $ao,
                'name'          => $buyer_name,
                'membership'    => $membership_name,
                'rebate'        => $rebate,
                'rebate_d'      => 0,
                'total'         => $rebate,
                'xp_awarded'    => 10000000, // XP awarded for this order
            ];
            
     
             update_user_meta($member,'_buyer_details',$buyer_metas);
             
           // new we need to update tresury details to sponsor 
           if($sponsor!=0 && dongtrader_check_user($sponsor,true)){
           
                // Get previously stored sponsors treasury    
                $sponsor_buyer_meta  = get_user_meta($sponsor, '_buyer_details', true);
                    
                //assign empty array if $treasury_meta is empty 
                $sponsor_buyer_metas = !empty($sponsor_buyer_meta) ? $sponsor_buyer_meta : [];
                
                //new array for new order which we will append to 
                $sponsor_buyer_metas[] = [
                    'order_id'      => $ao,
                    'name'          => $buyer_name,
                    'membership'    => $membership_name,
                    'rebate'        => 0,
                    'rebate_d'      => $rebate_d,
                    'total'         => $rebate_d,
                    'xp_awarded'    => 10000000, // XP awarded for sponsor
                ];
                update_user_meta($sponsor,'_buyer_details',$sponsor_buyer_metas);
           }

        }
        
   }
}

/**
 * Saving treasury related details to user meta function
 * @return void
 */
function mega_save_my_treasury($orders_members){

    if(empty($orders_members)) return;

    foreach($orders_members as $order=>$user){
            
        //check if order id exists in database
        if(get_post_type($order) != 'shop_order') continue;
         
        //get previous seller trading details saved in user meta
        $treasury_meta  = get_user_meta($user, '_treasury_details', true);
        
        //Get the sponsor id 
        $sponsor =  dongtrader_get_order_meta($order,'mega_affid'); 
        
        //assign empty array if $treasury_meta is empty 
        $treasury_metas = !empty($treasury_meta) ? $treasury_meta : [];
          
        //get title of the product
        $membership_name =  dongtrader_get_order_meta($order,'_membership_name');
                  
        //constant cost from pmpro custom fields
        $members_reward = dongtrader_get_order_meta($order, 'mega_members_r');
         
        //50% to seller
        $member_reward_50_i = $members_reward* 50/100;
         
        //40% to group
        $member_reward_40_g = $members_reward* 40/100;
         
        //10% as commission
        $smallstreet_reward_10_c = $members_reward * 10/100;
        
        // get actual buyer name 
        $buyer_name   = dongtrader_check_user($user , false);
        
        // treasury metas arrays
        $treasury_metas[] = [
            'order_id'      => $order,
            'name'          => $buyer_name,
            'membership'    => $membership_name,
            'seller_reward' => 0,
            'group_reward'  => 0,
            'smallstreet_reward' => $smallstreet_reward_10_c,
            'totals' => $smallstreet_reward_10_c,
            'xp_awarded'    => 10000000 // XP awarded for this order
        ];
        
        // updating treasury details to the current user 
        update_user_meta($user,'_treasury_details',$treasury_metas);
        
       // new we need to update tresury details to sponsor 
       if($sponsor!=0 && dongtrader_check_user($sponsor,true)){
       
            // Get previously stored sponsors treasury    
            $sponsor_treasury_meta  = get_user_meta($sponsor, '_treasury_details', true);
                
            //assign empty array if $treasury_meta is empty 
            $sponsor_treasury_metas = !empty($sponsor_treasury_meta) ? $sponsor_treasury_meta : [];
            
            $sponsor_treasury_metas[] = [
                'order_id'      => $order,
                'name'          => $buyer_name,
                'membership'    => $membership_name,
                'seller_reward' => $member_reward_50_i,
                'group_reward'  => 0,
                'smallstreet_reward' => $smallstreet_reward_10_c,
                'totals' => $smallstreet_reward_10_c+$member_reward_50_i,
                'xp_awarded'    => 100000000 // XP awarded to buyer for this order
            ];
            
            update_user_meta($sponsor,'_treasury_details',$sponsor_treasury_metas);
            
            
            $seller_income_meta     = get_user_meta($sponsor, '_income_details', true);
            
            $seller_income_metas    = !empty($seller_income_meta) ? $seller_income_meta : [];
            
            $seller_reward_c        = $smallstreet_reward_10_c*50/100;
            
            $smallstreet_reward_c   = $smallstreet_reward_10_c *40/100;
            
            $totals = $seller_reward_c+ $smallstreet_reward_c;
            
            $seller_income_metas[] = [
                'order_id'      => $order,
                'name'          => $buyer_name,
                'membership'    => $membership_name,
                'seller_reward_c_i_50' => $seller_reward_c,
                'group_reward_c_g_40'  => 0,
                'smallstreet_reward_c_10' => $smallstreet_reward_c,
                'totals' =>$totals,
                'xp_awarded'    => 100000000 // XP awarded to buyer for this order
            ];
            
            update_user_meta($sponsor,'_income_details',$seller_income_metas);
       }

    }
}


/**
 * Save Commission to affilates and group leaders
 *
 * @param array $order_users
 * @param mixed $group_leader
 * @return void
 */
function mega_save_commission_details($order_users , $group = false , $group_leader = '' ){

    if(empty($order_users)) return;

    foreach($order_users as $o=>$u){
        
        // check if order id exists in database
        if(get_post_type($o) != 'shop_order') continue;
        
        $affiliate_commission  = dongtrader_get_order_meta($o,'mega_cashback_d');

        // commission to smallstreet **Needs to reconsider and discusss this again***
        $site_comm        = dongtrader_get_order_meta($o,'mega_comm_c_ds');

        // Get sponsor id 
        $sponsor_id       = (int) dongtrader_get_order_meta($o,'mega_affid');

        // commission amount that needs to be saved to group leader
        $group_comm       = dongtrader_get_order_meta($o,'mega_mr_c_dg');

        // commission amount to affiliate
        $individual_comm  = dongtrader_get_order_meta($o,'mega_mr_c_di');

        // group leader and sponsor check
        $check = $group_leader == $sponsor_id ? true : false;

        $membership_name = dongtrader_get_order_meta($o,'_membership_name'); 

        // data to update for sponsor
        if($sponsor_id !== 0 && dongtrader_check_user($sponsor_id,true) && !$check && $group == false ){

            // get sponsor commission data
            $sponsor_commission_meta = get_user_meta($sponsor_id, '_commission_details', true);
            
            // assign empty array if $commission_meta is empty
            $sponsor_commission_metas = !empty($sponsor_commission_meta) ? $sponsor_commission_meta : [];
            
            // calculate total commission for this row
            $total            =  $individual_comm  + $site_comm + $affiliate_commission;

            // new array to be stored in sponsor meta
            $sponsor_commission_metas[] =[
                'order_id'      => $o,
                'name'          => dongtrader_check_user($u, false),
                'product_title' => $membership_name,
                'affiliate_com' => $affiliate_commission,
                'individual_com'=> $individual_comm,
                'group_com'     => 0,
                'site_com'      => $site_comm,
                'total'         => $total,
                'xp_awarded'    => 10000000 // XP awarded to buyer for this order
            ];

            // Check if the sponsor exists in database and update to its meta
             update_user_meta($sponsor_id,'_commission_details',$sponsor_commission_metas);


        }

        // commission to update for group leader
        if(dongtrader_check_user($group_leader,true) && !$check && $group){

            // get sponsor commission data
            $leader_commission_meta = get_user_meta($group_leader, '_commission_details', true);

            // assign empty array if $commission_meta is empty
            $leader_commission_metas = !empty($leader_commission_meta) ? $leader_commission_meta : [];

            // calculate total commission for this row
            $total            =  $site_comm  + $group_comm;

            // new array to be stored in sponsor meta
            $leader_commission_metas[] =[
                'order_id'      => $o,
                'name'          => dongtrader_check_user($u, false),
                'product_title' => $membership_name,
                'affiliate_com' => 0,
                'individual_com'=> 0,
                'group_com'     => $group_comm,
                'site_com'      => $site_comm,
                'total'         => $total,
                'xp_awarded'    => 10000000 // XP awarded to buyer for this order
            ];

            // update new commission metas to group leader
            update_user_meta($group_leader,'_commission_details',$leader_commission_metas);
        }

        // if group leader and sponsor are same
        if($check && $group){
            // get sponsor commission data
            $leader_sp_commission_meta = get_user_meta($group_leader, '_commission_details', true);

            // assign empty array if $commission_meta is empty
            $leader_sp_commission_metas = !empty($leader_sp_commission_meta) ? $leader_sp_commission_meta : [];

            // calculate total commission for this row
            $total            =  $site_comm  + $group_comm + $affiliate_commission + $individual_comm;

            // new array to be stored in sponsor meta
            $leader_sp_commission_metas[] =[
                'order_id'      => $o,
                'name'          => dongtrader_check_user($u, false),
                'product_title' => $membership_name,
                'affiliate_com' => $affiliate_commission,
                'individual_com'=> $individual_comm,
                'group_com'     => $group_comm,
                'site_com'      => $site_comm,
                'total'         => $total,
                'xp_awarded'    => 10000000 // XP awarded to buyer for this order
            ];

             update_user_meta($group_leader,'_commission_details',$leader_sp_commission_metas);
        }

    }

}
/**
 * Saves Treasury Details
 *
 * @param [array] $orders_members
 * @param [array] $allmems
 * @return void
 */
function mega_save_treasury_details($orders_members , $allmems = [] ) {

    // exit if the array is empty
    if(empty($orders_members)) return;

    foreach($orders_members as $order=>$user){
        
         //check if order id exists in database
         if(get_post_type($order) != 'shop_order') continue;
         
         //get previous seller trading details saved in user meta
         $treasury_meta     = get_user_meta($user, '_treasury_details', true);

         //assign empty array if $treasury_meta is empty 
         $treasury_metas    = !empty($treasury_meta) ? $treasury_meta : [];
 
         //get title of the product
         $product_name      =  dongtrader_get_order_meta($order,'_membership_name');
 
         //get buyer or customer name
         $buyer_name        = dongtrader_check_user($user , false);

         //get order object to extract total
         $order_obj         =  wc_get_order($order);

         //Get order total
         $order_total       = $order_obj->get_total();
 
         //rebate amount from order meta distributeable to user whom has brought the product
         $rebate            = dongtrader_get_order_meta($order, 'mega_cashback_v');
 
         //rebate amount from order meta distributeable to user whom has brought the product
         $process           = dongtrader_get_order_meta($order, 'mega_cashback_d');
 
         //constant cost from pmpro custom fields
         $members_reward    = dongtrader_get_order_meta($order, 'mega_members_r');
         
         //  Affliate or sponsor 
         $sponsor           =  dongtrader_get_order_meta($order ,'mega_affid');
         
         //50% to seller
         $member_reward_50_i = $members_reward* 50/100;
         
         //40% to group
         $member_reward_40_g = $members_reward* 40/100;
         
         //10% as commission
         $member_reward_10_c = $members_reward * 10/100;
         
        //to update on sponsor meta
        if($sponsor!=0 && dongtrader_check_user($sponsor,true)){
            
            $treasury_metas[] = [
                'order_id'      => $order,
                'name'          => $buyer_name,
                'product_title' => $product_name,
                'm_r_50_i'      => $member_reward_50_i,
                'm_r_40_g'      => 0,
                'm_r_10_c'      => 0,
                'xp_awarded'    => 10000000 // XP awarded to buyer for this order
            ]; 
        }
        
        //  Amount remaining after distribution 
        //  $remaining_total_amt   = $order_total - $distributed_total_amt;
        // if($sponsor !== 0 && dongtrader_check_user($sponsor, true){
        
        // }
        //  $treasury_metas[] = [
        //      'order_id'      => $order,
        //      'name'          => $buyer_name,
        //      'product_title' => $product_name,
        //      'total_amt'     => $order_total,
        //      'distrb_amt'    => $distributed_total_amt,
        //      'rem_amt'       => $remaining_total_amt,
        //  ];

        //  update_user_meta($user,'_treasury_details',$treasury_metas);
        //  if(!empty($allmems)){
        //     foreach($allmems as $am) {
        //         update_user_meta($am,'_treasury_details', $treasury_metas);
        //      }
        //  }
    }
}
/**
 * Function to save group details
 *
 * @param [array] $order_members
 * @param [int] $group_leader
 * @param [int] $group_id
 * @return void
 */
function mega_save_group_details($order_members , $group_leader,$group_id){


    if(empty($order_members)) return;
    // Make $wpdb object available in the current context
    global $wpdb;

    $mega_mlm_customers = $wpdb->prefix . 'mega_mlm_customers';
    $mega_mlm_groups    = $wpdb->prefix . 'mega_mlm_groups';
    
    foreach($order_members as $o => $u) {

        //check if order id exists in database
        if(get_post_type($o) != 'shop_order') continue;

        // Get sponsor id 
        $sponsor_id             = (int) dongtrader_get_order_meta($o,'mega_affid');

        $orderobj               = new WC_Order($o);

        $formatted_order_date   = wc_format_datetime($orderobj->get_date_created(), 'Y-m-d');

        $order_code             = $orderobj->get_order_key();

        $check                  = $sponsor_id == $group_leader ? true : false;

        $group_profit_amount    = dongtrader_get_order_meta($o, 'mega_mr_dg');

        $individual_profit_amount = dongtrader_get_order_meta($o, 'mega_mr_di');

        
        $group_name_query = "SELECT g.circle_name FROM {$mega_mlm_customers} AS c
        JOIN {$mega_mlm_groups} AS g ON c.customer_group_id = g.group_id
        WHERE c.user_id = %d";

        $circle_name = $wpdb->get_var($wpdb->prepare($group_name_query, (int) $u));

        // Save data to sponsor
        if($sponsor_id !== 0 && dongtrader_check_user($sponsor_id, true) && !$check ){

            // get previous group details saved in user meta
            $sponsor_group_meta  = get_user_meta($group_leader, '_group_details', true);

            // assign empty array if meta is empty 
            $sponsor_group_metas = !empty($sponsor_group_meta) ? $sponsor_group_meta : [];

            $sponsor_group_metas[] = [
                'order_id'              => $o,
                'order_code'            => $order_code,
                'order_date'            => $formatted_order_date,
                'gf_name'               => $circle_name,
                'profit_amount'         => $individual_profit_amount,
                
            ];

            update_user_meta($sponsor_id,'_group_details',$sponsor_group_metas);

        }
        // send data to group leader
        if(dongtrader_check_user($group_leader , true) && !$check){

            // get previous group details saved in user meta
            $gl_group_meta  = get_user_meta($group_leader, '_group_details', true);

            // assign empty array if meta is empty 
            $gl_group_metas = !empty($gl_group_meta) ? $gl_group_meta : [];

            $gl_group_metas[] = [
                'order_id'              => $o,
                'order_code'            => $order_code,
                'order_date'            => $formatted_order_date,
                'gf_name'               => $circle_name,
                'profit_amount'         => $group_profit_amount,
                
            ];

            update_user_meta($group_leader,'_group_details',$gl_group_metas);
        }

        // if the sponsor and group leader are same
        if($check){

            // get previous group details saved in user meta
            $gl_and_sp_group_meta  = get_user_meta($group_leader, '_group_details', true);

            // assign empty array if meta is empty 
            $gl_and_sp_group_metas = !empty($gl_and_sp_group_meta) ? $gl_and_sp_group_meta : [];

            
            $gl_and_sp_group_metas[] = [
                'order_id'              => $o,
                'order_code'            => $order_code,
                'order_date'            => $formatted_order_date,
                'gf_name'               => $circle_name,
                'profit_amount'         => $group_profit_amount + $individual_profit_amount,
            ];

            update_user_meta($group_leader,'_group_details',$gl_and_sp_group_metas);
        }
    }
}

function mega_update_allocation_status($orders_user){

      if(empty($orders_user)) return;

      global $wpdb; 

      $mega_mlm_sales = $wpdb->prefix . 'mega_mlm_purchases';

      foreach($orders_user as $o=> $u){

        if(get_post_type($o) != 'shop_order') continue;

        $update = $wpdb->prepare("UPDATE $mega_mlm_sales SET allocation_status = %d WHERE order_id = %d", 1, (int) $o);

        $wpdb->query($update);

      }

}

/**
 * Updates Group Status to 1 when there are not any purchases of allocation_status=0 
 * @return void
 */

 function mega_update_group_distribution_status($group_id){


    global $wpdb;
    $mega_mlm_sales = $wpdb->prefix . 'mega_mlm_purchases';
    $mega_mlm_users = $wpdb->prefix . 'mega_mlm_customers';
    $group_details_table = $wpdb->prefix .'mega_mlm_groups';
    $unallocated_orders = $wpdb->prepare("SELECT p.* FROM {$mega_mlm_sales} AS p
    INNER JOIN {$mega_mlm_users} AS c ON p.customer_id = c.user_id
    INNER JOIN {$group_details_table} AS g ON c.customer_group_id = g.group_id
    WHERE p.allocation_status = %d",0);

    $results = $wpdb->get_results($unallocated_orders,ARRAY_A);

    if(empty($results)){

        $update = $wpdb->prepare("UPDATE $group_details_table SET distribution_status = %d WHERE group_id = %d", 1, (int) $group_id);

        $wpdb->query($update);
    }


 }

function dongtrader_check_user($uid , $check_only = true ){

    global $wpdb;
   
    $table_name =  $wpdb->prefix .'users';

    $user_id_int = intval($uid);

    if($check_only ) :

        $check_user = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE ID = %d AND id !=0 ", $user_id_int));

        $val = intval($check_user) == 1 ? true : false;

    else :

        $user_name = $wpdb->get_var("SELECT display_name FROM $table_name WHERE id = $uid");
        
        $val = $user_name;

    endif;


    return $val;

}

/**
 * Retrieves meta values from order . If meta is empty returns 0 otherwise returns value
 *
 * @param [string] $orderid id of the order
 * @param [string] $key order's meta key
 * @return [meta_value]
 */
function dongtrader_get_order_meta($orderid, $key)
{
    //get order objects
    $om = get_post_meta($orderid, $key , true);

    //get value of meta
    return !empty($om) ? $om : 0;

}

/**
 * Adding tabs and tables for woocommerce my-account page 
 *
 * @return void
 */
function add_custom_tab_to_my_account()
{

    $all_my_account_tabs = [

        [
            'name' => __('My Orders', 'cpm-dongtrader'),
            'slug' => 'detente-orders',
            'position' => 1,

        ],

        [
            'name' => __('Wallet', 'cpm-dongtrader'),
            'slug' => 'detente-wallet',
            'position' => 2,
        ],

        [
            'name' => __('XP Transfers', 'cpm-dongtrader'),
            'slug' => 'xp-transfers',
            'position' => 3,
        ],

        [
            'name' => __('Redemption', 'cpm-dongtrader'),
            'slug' => 'redemption',
            'position' => 4,
        ],

        [
            'name' => __('My Treasury', 'cpm-dongtrader'),
            'slug' => 'detente-treasury',
            'position' => 5,
        ],

        [
            'name' => __('Group', 'cpm-dongtrader'),
            'slug' => 'detente-group',
            'position' => 6,
        ],

        [
            'name' => __('Seller Income', 'cpm-dongtrader'),
            'slug' => 'detente-commission',
            'position' => 7,
        ],

        [
            'name' => __('POC Pooling', 'cpm-dongtrader'),
            'slug' => 'poc-pooling',
            'position' => 8,
        ],
    ];

    usort($all_my_account_tabs, function($a, $b) {

        return $a['position'] - $b['position'];

    });

    $dongtraders_setting_data = get_option('dongtraders_api_settings_fields');

    $currency_rate_check      = !empty($dongtraders_setting_data['dong_enable_currency']) ? $dongtraders_setting_data['dong_enable_currency'] :false;

    $actual_vnd_rate          = $currency_rate_check == 'on' ?  $dongtraders_setting_data['vnd_rate'] : 1;

    $currency_symbol          = $currency_rate_check == 'on' ?  '₫' :  get_woocommerce_currency_symbol();

    $vnd_rate_array           = ['currency_enabled'=> $currency_rate_check, 'vnd_rate'=>$actual_vnd_rate , 'symbol'=>$currency_symbol];

    foreach ($all_my_account_tabs as $k => $v):


        add_rewrite_endpoint($v['slug'], EP_ROOT | EP_PAGES);

        add_filter('query_vars', function ($vars) use ( $v ) {

            $vars[] = $v['slug'];

            return $vars;
        });

        add_filter('woocommerce_account_menu_items', function ($menu_links) use ( $v ) {

            $menu_links = array_slice($menu_links, 0, $v['position'], true)
            + array($v['slug'] => $v['name'])
            + array_slice($menu_links, $v['position'], NULL, true);
    
        return $menu_links;
            
        });

        add_action('woocommerce_account_' . $v['slug'] . '_endpoint', function () use ( $v , $vnd_rate_array ) {

            $tem_path = CPM_DONGTRADER_PLUGIN_DIR.'template-parts'.DIRECTORY_SEPARATOR.'content-'.$v['slug'].'.php';
            
            if (file_exists($tem_path)) {
                load_template($tem_path,true, $vnd_rate_array);
            }
        });

    endforeach;
    
    // Add XP Transfers modal script to footer
    add_action('wp_footer', 'dongtrader_xp_transfers_script');
    
    // Flush rewrite rules if endpoints were just added or changed
    $endpoints_flushed = get_option('dongtrader_endpoints_flushed', false);
    $current_endpoints = array_column($all_my_account_tabs, 'slug');
    $saved_endpoints = get_option('dongtrader_saved_endpoints', array());
    
    // Check if endpoints have changed or if this is first time
    if (!$endpoints_flushed || $current_endpoints !== $saved_endpoints) {
        flush_rewrite_rules(false);
        update_option('dongtrader_endpoints_flushed', true);
        update_option('dongtrader_saved_endpoints', $current_endpoints);
    }
    
    // Temporary: Force flush on next page load (remove after first successful load)
    // Uncomment the line below if rewrite rules still don't work, then comment it again after page loads once
    // delete_option('dongtrader_endpoints_flushed');
}
add_action('wp_loaded', 'add_custom_tab_to_my_account');

/**
 * AJAX handler to search for verified users by email, username, or FonePay ID
 */
add_action('wp_ajax_search_xp_receiver', 'dongtrader_search_xp_receiver');

/**
 * AJAX handler to send XP transfer
 */
add_action('wp_ajax_send_xp_transfer', 'dongtrader_send_xp_transfer');

function dongtrader_search_xp_receiver() {
    // Security check
    check_ajax_referer('search_receiver', 'nonce');
    
    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => __('You must be logged in to search for users.', 'cpm-dongtrader')));
        return;
    }
    
    $query = isset($_POST['query']) ? sanitize_text_field($_POST['query']) : '';
    
    if (strlen($query) < 2) {
        wp_send_json_success(array());
        return;
    }
    
    $current_user_id = get_current_user_id();
    $results = array();
    
    global $wpdb;
    
    // Search by email, username, or FonePay ID
    $search_query = '%' . $wpdb->esc_like($query) . '%';
    
    // Get users matching email, username, or display name
    $users = $wpdb->get_results($wpdb->prepare("
        SELECT DISTINCT u.ID, u.user_email, u.user_login, u.display_name
        FROM {$wpdb->users} u
        WHERE (
            u.user_email LIKE %s 
            OR u.user_login LIKE %s 
            OR u.display_name LIKE %s
        )
        AND u.ID != %d
        AND u.user_email != ''
        AND u.user_email IS NOT NULL
        ORDER BY u.display_name ASC
        LIMIT 20
    ", $search_query, $search_query, $search_query, $current_user_id));
    
    // Also search by FonePay ID
    $fonepay_users = $wpdb->get_results($wpdb->prepare("
        SELECT DISTINCT u.ID, u.user_email, u.user_login, u.display_name
        FROM {$wpdb->users} u
        INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
        WHERE um.meta_key = 'mega-paypal'
        AND um.meta_value LIKE %s
        AND u.ID != %d
        AND u.user_email != ''
        AND u.user_email IS NOT NULL
        ORDER BY u.display_name ASC
        LIMIT 20
    ", $search_query, $current_user_id));
    
    // Combine results
    $all_users = array();
    $seen_ids = array();
    
    // Add regular user search results
    foreach ($users as $user) {
        if (!in_array($user->ID, $seen_ids)) {
            $all_users[] = $user;
            $seen_ids[] = $user->ID;
        }
    }
    
    // Add FonePay search results
    foreach ($fonepay_users as $user) {
        if (!in_array($user->ID, $seen_ids)) {
            $all_users[] = $user;
            $seen_ids[] = $user->ID;
        }
    }
    
    // Build results array with all user data
    foreach ($all_users as $user) {
        // Get FonePay ID if exists
        $fonepay_id = get_user_meta($user->ID, 'mega-paypal', true);
        
        // Build display name
        $display_name = !empty($user->display_name) ? $user->display_name : $user->user_login;
        
        $results[] = array(
            'id' => $user->ID,
            'name' => $display_name,
            'email' => $user->user_email,
            'username' => $user->user_login,
            'fonepay_id' => $fonepay_id ? $fonepay_id : ''
        );
        
        // Limit to 10 results
        if (count($results) >= 10) {
            break;
        }
    }
    
    wp_send_json_success($results);
}

/**
 * AJAX handler to send XP transfer
 */
function dongtrader_send_xp_transfer() {
    // Security check
    check_ajax_referer('send_xp_transfer', 'nonce');
    
    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => __('You must be logged in to send XP.', 'cpm-dongtrader')));
        return;
    }
    
    $sender_id = get_current_user_id();
    $receiver_id = isset($_POST['receiver_id']) ? intval($_POST['receiver_id']) : 0;
    // Keep the raw xp_amount as a sanitized string to preserve precision
    $xp_amount_raw = isset($_POST['xp_amount']) ? sanitize_text_field((string)$_POST['xp_amount']) : '0';
    // Normalized numeric string (strip any unexpected chars but keep decimal/exponent)
    $xp_amount_str = preg_replace('/[^0-9+\-\.eE]/', '', $xp_amount_raw);
    $memo = isset($_POST['memo']) ? sanitize_text_field($_POST['memo']) : '';
    
    // Validation
    if (!$receiver_id || $receiver_id <= 0) {
        wp_send_json_error(array('message' => __('Please select a receiver.', 'cpm-dongtrader')));
        return;
    }
    
    if ($receiver_id == $sender_id) {
        wp_send_json_error(array('message' => __('You cannot send XP to yourself.', 'cpm-dongtrader')));
        return;
    }
    
    // Validate xp amount string -> must be numeric and > 0
    if ($xp_amount_str === '' || !is_numeric($xp_amount_str) || floatval($xp_amount_str) <= 0) {
        wp_send_json_error(array('message' => __('Please enter a valid XP amount.', 'cpm-dongtrader')));
        return;
    }

    // Check minimum transfer (0.000001 XP = 1 YAM) using string-aware comparison
    $min_transfer_str = '0.000001';
    if (extension_loaded('bcmath')) {
        if (bccomp($xp_amount_str, $min_transfer_str, 18) === -1) {
            wp_send_json_error(array('message' => sprintf(__('Minimum transfer: %s XP', 'cpm-dongtrader'), number_format((float)$min_transfer_str, 6))));
            return;
        }
    } else {
        if (floatval($xp_amount_str) < (float)$min_transfer_str) {
            wp_send_json_error(array('message' => sprintf(__('Minimum transfer: %s XP', 'cpm-dongtrader'), number_format((float)$min_transfer_str, 6))));
            return;
        }
    }
    
    // Get sender's balance
    $sender_scan_raw = get_user_meta($sender_id, 'seller_scan', true);
    $buyer_scan_raw = get_user_meta($sender_id, 'buyer_scan', true);
    $personal_scan_raw = get_user_meta($sender_id, 'personal_scan', true);
    
    $seller_scan_data = maybe_unserialize($sender_scan_raw);
    $buyer_scan_data = maybe_unserialize($buyer_scan_raw);
    $personal_scan_data = maybe_unserialize($personal_scan_raw);
    
    if (!is_array($seller_scan_data)) $seller_scan_data = array();
    if (!is_array($buyer_scan_data)) $buyer_scan_data = array();
    if (!is_array($personal_scan_data)) $personal_scan_data = array();
    
    // Sum base XP from scans using string-based arithmetic to preserve precision
    $sender_total_xp_str = '0';
    foreach ($seller_scan_data as $entry) {
        if (is_array($entry) && !empty($entry)) {
            // Skip XP transfer entries - these are already accounted for in transactions table
            if (isset($entry['source']) && $entry['source'] === 'xp_transfer') {
                continue;
            }
            $xp_str = isset($entry['xp_units']) ? (string)$entry['xp_units'] : '0';
            $xp_str = preg_replace('/[^0-9+\-\.eE]/', '', $xp_str);
            $sender_total_xp_str = dongtrader_bc_add($sender_total_xp_str, $xp_str, 18);
        }
    }
    foreach ($buyer_scan_data as $entry) {
        if (is_array($entry) && !empty($entry)) {
            // Skip XP transfer entries - these are already accounted for in transactions table
            if (isset($entry['source']) && $entry['source'] === 'xp_transfer') {
                continue;
            }
            $xp_str = isset($entry['xp_units']) ? (string)$entry['xp_units'] : '0';
            $xp_str = preg_replace('/[^0-9+\-\.eE]/', '', $xp_str);
            $sender_total_xp_str = dongtrader_bc_add($sender_total_xp_str, $xp_str, 18);
        }
    }
    foreach ($personal_scan_data as $entry) {
        if (is_array($entry) && !empty($entry)) {
            // Skip XP transfer entries - these are already accounted for in transactions table
            if (isset($entry['source']) && $entry['source'] === 'xp_transfer') {
                continue;
            }
            $xp_str = isset($entry['xp_units']) ? (string)$entry['xp_units'] : '0';
            $xp_str = preg_replace('/[^0-9+\-\.eE]/', '', $xp_str);
            $sender_total_xp_str = dongtrader_bc_add($sender_total_xp_str, $xp_str, 18);
        }
    }
    
    // Get transactions to calculate available XP
    global $wpdb;
    $table_name_trans = $wpdb->prefix . 'xp_transactions';
    $sender_transactions = $wpdb->get_results($wpdb->prepare("
        SELECT xp_amount, sender_id, receiver_id
        FROM {$table_name_trans}
        WHERE sender_id = %d OR receiver_id = %d
    ", $sender_id, $sender_id), ARRAY_A);
    
    // Sum transactions using string arithmetic
    $sender_xp_sent_str = '0';
    $sender_xp_received_str = '0';
    if (is_array($sender_transactions)) {
        foreach ($sender_transactions as $trans) {
            $xp_amt_str = isset($trans['xp_amount']) ? (string)$trans['xp_amount'] : '0';
            $xp_amt_str = preg_replace('/[^0-9+\-\.eE]/', '', $xp_amt_str);
            if (intval($trans['sender_id']) === $sender_id) {
                $sender_xp_sent_str = dongtrader_bc_add($sender_xp_sent_str, $xp_amt_str, 18);
            } else {
                $sender_xp_received_str = dongtrader_bc_add($sender_xp_received_str, $xp_amt_str, 18);
            }
        }
    }

    // Calculate available XP: Total XP - Sent + Received (string-based)
    $sender_available_xp_str = dongtrader_bc_sub(dongtrader_bc_add($sender_total_xp_str, $sender_xp_received_str, 18), $sender_xp_sent_str, 18);
    // If negative, set to zero
    if (extension_loaded('bcmath')) {
        if (bccomp($sender_available_xp_str, '0', 18) === -1) {
            $sender_available_xp_str = '0';
        }
    } else {
        if ((float)$sender_available_xp_str < 0.0) {
            $sender_available_xp_str = '0';
        }
    }
    
    // Check balance sufficiency using string-aware comparison
    if (extension_loaded('bcmath')) {
        if (bccomp($xp_amount_str, $sender_available_xp_str, 18) === 1) {
            wp_send_json_error(array('message' => __('Insufficient balance.', 'cpm-dongtrader')));
            return;
        }
    } else {
        if ((float)$xp_amount_str > (float)$sender_available_xp_str) {
            wp_send_json_error(array('message' => __('Insufficient balance.', 'cpm-dongtrader')));
            return;
        }
    }

    // Check maximum transfer (50% of available balance)
    $max_transfer_str = dongtrader_bc_mul($sender_available_xp_str, '0.5', 18);
    if (extension_loaded('bcmath')) {
        if (bccomp($xp_amount_str, $max_transfer_str, 18) === 1) {
            wp_send_json_error(array('message' => sprintf(__('Maximum transfer: %s XP (50%% of balance)', 'cpm-dongtrader'), number_format((float)$max_transfer_str, 6))));
            return;
        }
    } else {
        if ((float)$xp_amount_str > (float)$max_transfer_str) {
            wp_send_json_error(array('message' => sprintf(__('Maximum transfer: %s XP (50%% of balance)', 'cpm-dongtrader'), number_format((float)$max_transfer_str, 6))));
            return;
        }
    }
    
    // Verify receiver exists and is active
    $receiver = get_userdata($receiver_id);
    if (!$receiver || !$receiver->user_email) {
        wp_send_json_error(array('message' => __('Receiver not found or inactive.', 'cpm-dongtrader')));
        return;
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'xp_transactions';

    // Calculate YAM equivalent using string-aware arithmetic (existing logic: multiply by 1,000,000)
    $yam_equivalent_str = dongtrader_bc_mul($xp_amount_str, '1000000', 0);

    // Insert transaction into database using string values for decimal fields
    $result = $wpdb->insert(
        $table_name,
        array(
            'sender_id' => $sender_id,
            'receiver_id' => $receiver_id,
            'xp_amount' => $xp_amount_str,
            'yam_equivalent' => $yam_equivalent_str,
            'memo' => $memo,
            'transaction_date' => current_time('mysql')
        ),
        array('%d', '%d', '%s', '%s', '%s', '%s')
    );
    
    if ($result === false) {
        wp_send_json_error(array('message' => __('Failed to record transaction. Please try again.', 'cpm-dongtrader')));
        return;
    }
    
    $transaction_id = $wpdb->insert_id;
    
    // Note: XP transfers are only stored in wp_xp_transactions table
    // We don't store transfers in personal_scan usermeta to avoid double-counting
    // Balance is calculated dynamically: Base XP from scans - Sent + Received (from transactions table)
    
    // Compute new balance (string-based)
    $new_balance_str = dongtrader_bc_sub($sender_available_xp_str, $xp_amount_str, 18);
    if (extension_loaded('bcmath')) {
        if (bccomp($new_balance_str, '0', 18) === -1) {
            $new_balance_str = '0';
        }
    } else {
        if ((float)$new_balance_str < 0.0) {
            $new_balance_str = '0';
        }
    }

    // Send success response with string new balance
    wp_send_json_success(array(
        'message' => __('XP transferred successfully!', 'cpm-dongtrader'),
        'transaction_id' => $transaction_id,
        'new_balance' => $new_balance_str
    ));
}



/**
 * This function is used manage pagination for tables in woocommerce my-account page
 *
 */
function dongtrader_pagination_array($details, $items_per_page = 10 , $items_array=false){

    $current_page = isset($_GET['listpaged']) ? (int) $_GET['listpaged'] : 1;

    // calculate start and end indices for items on current page
    $start_index = ($current_page - 1) * $items_per_page;


    if (isset($_REQUEST['filter'])) {
                
        //get filter data from url parameters
        $get_filter = sanitize_text_field($_REQUEST['filter']);

        if ($get_filter == "all") {

            $all_selected = "selected";
            $date_selected = "";

        } elseif ($get_filter == "within-a-date-range") {

            //get start date
            $start = sanitize_text_field($_REQUEST['start-month']);

            //get end date
            $enddate = sanitize_text_field($_REQUEST['end-month']);
            $date_selected = "selected";
            $all_selected = "";

            if (strtotime($start) > strtotime($enddate)) {
                $temp_date = $start;
                $start = $enddate;
                $enddate = $temp_date;
            }

            $start_date_obj = strtotime($start);
            $end_date_obj = strtotime($enddate);

            if ($start_date_obj && $end_date_obj) {

                $results = array_filter($details, function ($item) use ($start_date_obj, $end_date_obj) {
                    $order = new WC_Order($item['order_id']);
                    $item_date = strtotime($order->get_date_created()->date('Y-m-d'));

                    return ($item_date >= $start_date_obj && $item_date <= $end_date_obj);
                });

                $details = $results;
                $start_index = 0;

            }
    }
    } else {
        $start          = "";
        $enddate        = "";
        $date_selected  = "";
        $all_selected   = "";

    }

    $paginated_array = array_slice($details, $start_index, $items_per_page);

    $params_arr = [
        'startdate'     => $start,
        'enddate'       => $enddate,
        'date_selected' => $date_selected,
        'all_selected'  => $all_selected
    ];

    return $items_array ? $paginated_array : $params_arr ;

}

function get_user_orders($status)
{
	$user_id = get_current_user_id();

	if (!$user_id) {
		return [];
	}

	$args = [
		'customer_id' => $user_id,
		'status'      => $status,
		'limit'       => -1,
	];

	return wc_get_orders($args);
}

function isLastDayOfMonth() {

    // Get today's date
    $today = new DateTime();
    
    // Get tomorrow's date
    $tomorrow = clone $today;
    $tomorrow->modify('+1 day');
    
    // Check if tomorrow is the first day of the next month
    return $tomorrow->format('j') === '1';
}

/**
 * Add XP Transfers modal JavaScript to footer
 */
function dongtrader_xp_transfers_script() {
    // Only load on account pages and xp-transfers endpoint
    if (!is_account_page() || !is_user_logged_in()) {
        return;
    }
    
    // Check if we're on the xp-transfers page
    global $wp;
    if (!isset($wp->query_vars['xp-transfers'])) {
        return;
    }
    
    // Get total XP for calculations (same logic as template)
    $user_id = get_current_user_id();
    $seller_scan_raw = get_user_meta($user_id, 'seller_scan', true);
    $buyer_scan_raw = get_user_meta($user_id, 'buyer_scan', true);
    $personal_scan_raw = get_user_meta($user_id, 'personal_scan', true);
    
    $seller_scan_data = maybe_unserialize($seller_scan_raw);
    $buyer_scan_data = maybe_unserialize($buyer_scan_raw);
    $personal_scan_data = maybe_unserialize($personal_scan_raw);
    
    if (!is_array($seller_scan_data)) $seller_scan_data = array();
    if (!is_array($buyer_scan_data)) $buyer_scan_data = array();
    if (!is_array($personal_scan_data)) $personal_scan_data = array();
    
    $user_treasury_entries = array();
    foreach ($seller_scan_data as $entry) {
        if (is_array($entry) && !empty($entry)) {
            $user_treasury_entries[] = $entry;
        }
    }
    foreach ($buyer_scan_data as $entry) {
        if (is_array($entry) && !empty($entry)) {
            $user_treasury_entries[] = $entry;
        }
    }
    foreach ($personal_scan_data as $entry) {
        if (is_array($entry) && !empty($entry)) {
            $user_treasury_entries[] = $entry;
        }
    }
    
    $total_xp = 0;
    foreach ($user_treasury_entries as $entry) {
        // Skip if entry is not an array
        if (!is_array($entry) || empty($entry)) {
            continue;
        }
        
        // Skip XP transfer entries - these are already accounted for in transactions table
        // XP transfers are stored in personal_scan with source='xp_transfer'
        if (isset($entry['source']) && $entry['source'] === 'xp_transfer') {
            continue;
        }
        
        $xp = isset($entry['xp_units']) ? floatval($entry['xp_units']) : 0;
        if ($xp > 0) {
            $total_xp += $xp;
        }
    }
    
    // Get transactions to calculate available XP
    global $wpdb;
    $table_name = $wpdb->prefix . 'xp_transactions';
    $user_transactions = array();
    
    // Check if table exists before querying
    $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name));
    if ($table_exists) {
        $user_transactions = $wpdb->get_results($wpdb->prepare("
            SELECT xp_amount, sender_id, receiver_id
            FROM {$table_name}
            WHERE sender_id = %d OR receiver_id = %d
        ", $user_id, $user_id), ARRAY_A);
        
        if (!is_array($user_transactions)) {
            $user_transactions = array();
        }
    }
    
    $total_xp_sent_js = 0;
    $total_xp_received_js = 0;
    if (is_array($user_transactions)) {
        foreach ($user_transactions as $trans) {
            // Safety checks for transaction data
            if (!isset($trans['xp_amount']) || !isset($trans['sender_id']) || !isset($trans['receiver_id'])) {
                continue;
            }
            $xp_amt = isset($trans['xp_amount']) ? floatval($trans['xp_amount']) : 0;
            $trans_sender_id = isset($trans['sender_id']) ? intval($trans['sender_id']) : 0;
            $trans_receiver_id = isset($trans['receiver_id']) ? intval($trans['receiver_id']) : 0;
            
            if ($trans_sender_id === $user_id) {
                $total_xp_sent_js += $xp_amt;
            } elseif ($trans_receiver_id === $user_id) {
                $total_xp_received_js += $xp_amt;
            }
        }
    }
    
    // Calculate available XP: Total XP - Sent + Received
    // Ensure all values are numeric
    $total_xp = is_numeric($total_xp) ? floatval($total_xp) : 0;
    $total_xp_sent_js = is_numeric($total_xp_sent_js) ? floatval($total_xp_sent_js) : 0;
    $total_xp_received_js = is_numeric($total_xp_received_js) ? floatval($total_xp_received_js) : 0;
    
    $available_xp_js = $total_xp - $total_xp_sent_js + $total_xp_received_js;
    if ($available_xp_js < 0 || !is_numeric($available_xp_js)) {
        $available_xp_js = 0;
    }
    
    // Calculate max transfer (50% of available balance)
    $max_transfer = is_numeric($available_xp_js) ? ($available_xp_js * 0.5) : 0;
    if ($max_transfer < 0 || !is_numeric($max_transfer)) {
        $max_transfer = 0;
    }
    
    ?>
    <script type="text/javascript">
    console.log('=== XP TRANSFERS SCRIPT LOADING ===');
    
    jQuery(document).ready(function($) {
        console.log('📄 jQuery document ready fired');
        
        // Validation constants from documentation
        var minTransfer = 0.000001; // 1 YAM equivalent (minimum transfer)
        var maxTransfer = <?php echo number_format($max_transfer, 6, '.', ''); ?>; // 50% of available balance
        var currentBalance = <?php echo number_format($available_xp_js, 6, '.', ''); ?>; // Available XP (Total - Sent + Received)
        
        // Helper function to format numbers in scientific notation (e.g., "1.03 × 10²³")
        function formatScientificNotation(num) {
            if (num === 0 || num === null || isNaN(num)) {
                return '0';
            }
            var scientific = num.toExponential(2);
            var parts = scientific.split('e');
            var mantissa = parseFloat(parts[0]).toString();
            // Remove trailing zeros and decimal point if needed
            mantissa = mantissa.replace(/\.?0+$/, '');
            var exponent = parts[1] ? parts[1].replace('+', '') : '0';
            return mantissa + ' × 10<sup>' + exponent + '</sup>';
        }
        
        // Tab switching functionality
        function switchTab(tabName) {
            console.log('🔄 Switching to tab:', tabName);
            
            // Remove active class from all tabs and buttons
            $('.xp-tab-button').removeClass('active');
            $('.tab-content').removeClass('active');
            
            // Hide all tab contents explicitly
            $('.tab-content').css({
                'display': 'none',
                'visibility': 'hidden',
                'opacity': '0'
            });
            
            // Add active class to selected tab button
            var $tabButton = $('.xp-tab-button[data-tab="' + tabName + '"]');
            $tabButton.addClass('active');
            
            // Show selected tab content
            var $tabContent = $('#' + tabName + '-tab');
            $tabContent.addClass('active');
            $tabContent.css({
                'display': 'block',
                'visibility': 'visible',
                'opacity': '1'
            });
            
            console.log('✅ Tab switched to:', tabName);
            console.log('- Tab button found:', $tabButton.length > 0);
            console.log('- Tab button active:', $tabButton.hasClass('active'));
            console.log('- Tab content found:', $tabContent.length > 0);
            console.log('- Tab content active:', $tabContent.hasClass('active'));
            console.log('- Transactions tab visible:', $('#transactions-tab').is(':visible'));
            console.log('- Send XP tab visible:', $('#send-xp-tab').is(':visible'));
        }
        
        // Ensure Transactions tab is active on page load
        console.log('🔵 Initializing tabs...');
        console.log('- Transactions tab active:', $('#transactions-tab').hasClass('active'));
        console.log('- Send XP tab active:', $('#send-xp-tab').hasClass('active'));
        
        // Tab button click handlers
        $(document).on('click', '.xp-tab-button', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var tabName = $(this).data('tab');
            console.log('🟢 Tab button clicked:', tabName);
            if (tabName) {
                switchTab(tabName);
            } else {
                console.error('❌ No tab name found!');
            }
        });
        
        // Send XP button/tab handler (for backward compatibility)
        $(document).on('click', '#open-send-xp-tab', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('🟢 Send XP tab button clicked!');
            switchTab('send-xp');
        });
        
        // Receiver search functionality
        let searchTimeout = null;
        let selectedReceiver = null;
        
        $(document).on('input', '#receiver_search', function() {
            var query = $(this).val().trim();
            var $results = $('#receiver_results');
            
            // Clear previous timeout
            clearTimeout(searchTimeout);
            
            // Hide results if query is too short
            if (query.length < 2) {
                $results.hide().html('');
                return;
            }
            
            // Show loading state
            $results.html('<div class="receiver-result-item" style="text-align: center; padding: 10px; color: #64748b;"><?php echo esc_js(__('Searching...', 'cpm-dongtrader')); ?></div>').show();
            
            // Debounce search
            searchTimeout = setTimeout(function() {
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'search_xp_receiver',
                        query: query,
                        nonce: '<?php echo wp_create_nonce('search_receiver'); ?>'
                    },
                    success: function(response) {
                        if (response.success && response.data && response.data.length > 0) {
                            var html = '';
                            response.data.forEach(function(user) {
                                var displayText = user.name;
                                if (user.fonepay_id) {
                                    displayText += ' <span style="color: #64748b; font-size: 0.85em;">(' + user.fonepay_id + ')</span>';
                                }
                                html += '<div class="receiver-result-item" data-user-id="' + user.id + '" data-name="' + user.name + '" data-email="' + user.email + '">';
                                html += '<div class="name">' + displayText + '</div>';
                                html += '<div class="email">' + user.email + '</div>';
                                html += '</div>';
                            });
                            $results.html(html).show();
                        } else {
                            $results.html('<div class="receiver-result-item" style="text-align: center; padding: 10px; color: #64748b;"><?php echo esc_js(__('No users found', 'cpm-dongtrader')); ?></div>').show();
                        }
                    },
                    error: function() {
                        $results.html('<div class="receiver-result-item" style="text-align: center; padding: 10px; color: #dc2626;"><?php echo esc_js(__('Search error. Please try again.', 'cpm-dongtrader')); ?></div>').show();
                    }
                });
            }, 300);
        });
        
        // Select receiver from results
        $(document).on('click', '.receiver-result-item', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            var userId = $(this).data('user-id');
            var userName = $(this).data('name');
            var userEmail = $(this).data('email');
            
            if (!userId) return;
            
            selectedReceiver = {
                id: userId,
                name: userName,
                email: userEmail
            };
            
            // Update hidden field and display
            $('#receiver_id').val(userId);
            $('#receiver_name').text(userName);
            $('#receiver_email').text(userEmail);
            
            // Show selected receiver card (which includes the cross button)
            $('#selected_receiver').show();
            
            // Hide dropdown immediately
            $('#receiver_results').hide().html('');
            
            // Clear search input and show selected user name
            $('#receiver_search').val(userName).blur();
            
            // Hide any error messages
            $('#receiver_error').hide();
            $('#receiver_search').removeClass('error');
            
            // Validate form after receiver selection
            validateForm();
        });
        
        // Hide dropdown when search input loses focus (but only if no user selected)
        $(document).on('blur', '#receiver_search', function() {
            // Small delay to allow click on result item to register first
            setTimeout(function() {
                if (!selectedReceiver || !selectedReceiver.id) {
                    $('#receiver_results').hide();
                }
            }, 200);
        });
        
        // Close results when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.receiver-search').length && !$(e.target).closest('#selected_receiver').length) {
                $('#receiver_results').hide();
            }
        });
        
        // Form interactions
        $(document).on('input', '#memo', function() {
            var length = $(this).val().length;
            $('#char_count').text(length);
        });
        
        // Real-time XP amount validation and conversion display
        $(document).on('input', '#xp_amount', function() {
            var amount = parseFloat($(this).val()) || 0;
            var amountValue = $(this).val().trim();
            
            // Show conversion if amount is valid - NEW CONVERSION
            if (amount > 0 && !isNaN(amount) && amount >= minTransfer && amount <= maxTransfer && amount <= currentBalance) {
                // NEW: Calculate USD directly from XP (USD = XP / 10^23)
                var xpPerDollar = 100000000000000000000000; // 10^23
                var usd = amount / xpPerDollar;
                // NEW CONVERSION: 1 USD = 21,000 YAM = 10^23 XP
                var yamPerUSD = 21000; // 1 USD = 21,000 YAM
                var yam = usd * yamPerUSD; // Convert USD to YAM
                $('#yam_equiv').text(yam.toExponential(2));
                $('#usd_value').text(usd.toFixed(2));
                $('#conversion_display').show();
            } else {
                $('#conversion_display').hide();
            }
            
            // Validate form (this will handle error messages and button state)
            validateForm();
        });
        
        // Clear receiver function
        window.clearReceiver = function() {
            selectedReceiver = null;
            // Hide the selected receiver card (which includes the cross button)
            $('#selected_receiver').hide();
            // Clear search input
            $('#receiver_search').val('').focus();
            // Clear hidden field
            $('#receiver_id').val('');
            // Hide dropdown
            $('#receiver_results').hide().html('');
            // Validate form (this will handle error messages and button state)
            validateForm();
        };
        
        // Validation function
        function validateTransfer(receiverId, amount) {
            var errors = [];
            
            // 1. Receiver validation - Required field
            if (!receiverId || receiverId === '' || receiverId === null || receiverId === undefined) {
                errors.push({
                    field: 'receiver',
                    message: '<?php echo esc_js(__('Please select a receiver', 'cpm-dongtrader')); ?>'
                });
            }
            
            // 2. Amount validation - Required field and must be a valid number
            if (!amount || amount === '' || isNaN(amount) || amount <= 0) {
                errors.push({
                    field: 'amount',
                    message: '<?php echo esc_js(__('Please enter a valid XP amount', 'cpm-dongtrader')); ?>'
                });
                return errors; // Return early if amount is invalid
            }
            
            // 3. Minimum transfer check (0.000001 XP = 1 YAM equivalent)
            if (amount < minTransfer) {
                errors.push({
                    field: 'amount',
                    message: '<?php echo esc_js(sprintf(__('Minimum transfer: %s XP (1 YAM equivalent)', 'cpm-dongtrader'), number_format(0.000001, 6))); ?>'
                });
            }
            
            // 4. Maximum transfer check (50% of sender's balance)
            if (amount > maxTransfer) {
                errors.push({
                    field: 'amount',
                    message: '<?php echo esc_js(sprintf(__('Maximum transfer: %s XP (50%% of balance)', 'cpm-dongtrader'), number_format($max_transfer, 6))); ?>'
                });
            }
            
            // 5. Balance sufficiency check
            if (amount > currentBalance) {
                errors.push({
                    field: 'amount',
                    message: '<?php echo esc_js(__('Insufficient balance', 'cpm-dongtrader')); ?>'
                });
            }
            
            // 6. Check if sender has any balance
            if (currentBalance <= 0) {
                errors.push({
                    field: 'amount',
                    message: '<?php echo esc_js(__('You have no XP balance to transfer', 'cpm-dongtrader')); ?>'
                });
            }
            
            return errors;
        }
        
        // Real-time validation function
        function validateForm() {
            var receiverId = $('#receiver_id').val();
            var amount = parseFloat($('#xp_amount').val()) || 0;
            var isValid = true;
            
            // Clear previous errors
            $('#receiver_error').hide().text('');
            $('#amount_error').hide().text('');
            
            // Remove error classes from inputs
            $('#receiver_search').removeClass('error');
            $('#xp_amount').removeClass('error');
            
            // Validate receiver
            if (!receiverId || receiverId === '' || receiverId === null || receiverId === undefined) {
                $('#receiver_error').text('<?php echo esc_js(__('Please select a receiver', 'cpm-dongtrader')); ?>').show();
                $('#receiver_search').addClass('error');
                isValid = false;
            }
            
            // Validate amount
            var amountValue = $('#xp_amount').val().trim();
            if (!amountValue || amountValue === '') {
                $('#amount_error').text('<?php echo esc_js(__('XP amount is required', 'cpm-dongtrader')); ?>').show();
                $('#xp_amount').addClass('error');
                isValid = false;
            } else if (isNaN(amount) || amount <= 0) {
                $('#amount_error').text('<?php echo esc_js(__('Please enter a valid XP amount', 'cpm-dongtrader')); ?>').show();
                $('#xp_amount').addClass('error');
                isValid = false;
            } else if (amount < minTransfer) {
                $('#amount_error').text('<?php echo esc_js(sprintf(__('Minimum transfer: %s XP', 'cpm-dongtrader'), number_format(0.000001, 6))); ?>').show();
                $('#xp_amount').addClass('error');
                isValid = false;
            } else if (amount > maxTransfer) {
                $('#amount_error').text('<?php echo esc_js(sprintf(__('Maximum transfer: %s XP (50%% of balance)', 'cpm-dongtrader'), number_format($max_transfer, 6))); ?>').show();
                $('#xp_amount').addClass('error');
                isValid = false;
            } else if (amount > currentBalance) {
                $('#amount_error').text('<?php echo esc_js(__('Insufficient balance', 'cpm-dongtrader')); ?>').show();
                $('#xp_amount').addClass('error');
                isValid = false;
            }
            
            // Enable/disable submit button
            if (isValid && receiverId && amount > 0 && amount >= minTransfer && amount <= maxTransfer && amount <= currentBalance) {
                $('#submit_btn').prop('disabled', false);
            } else {
                $('#submit_btn').prop('disabled', true);
            }
            
            return isValid;
        }
        
        // Real-time validation on input change
        $(document).on('input blur', '#xp_amount', function() {
            validateForm();
        });
        
        // Real-time validation on receiver selection
        $(document).on('change', '#receiver_id', function() {
            validateForm();
        });
        
        // Validate on receiver search blur
        $(document).on('blur', '#receiver_search', function() {
            setTimeout(function() {
                validateForm();
            }, 200); // Delay to allow dropdown click to register
        });
        
        // Submit button - Show summary instead of submitting
        $(document).on('click', '#submit_btn', function(e) {
            e.preventDefault();
            
            // Validate form
            if (!validateForm()) {
                // Scroll to first error
                var firstError = $('.error-text:visible').first();
                if (firstError.length) {
                    $('html, body').animate({
                        scrollTop: firstError.offset().top - 100
                    }, 300);
                }
                return;
            }
            
            // Get form values
            var receiverId = $('#receiver_id').val();
            var receiverName = $('#receiver_name').text();
            var receiverEmail = $('#receiver_email').text();
            var amount = parseFloat($('#xp_amount').val()) || 0;
            var memo = $('#memo').val();
            
            // Final validation
            var validationErrors = validateTransfer(receiverId, amount);
            
            if (validationErrors.length > 0) {
                // Show errors in form
                validationErrors.forEach(function(error) {
                    if (error.field === 'receiver') {
                        $('#receiver_error').text(error.message).show();
                        $('#receiver_search').addClass('error');
                    } else if (error.field === 'amount') {
                        $('#amount_error').text(error.message).show();
                        $('#xp_amount').addClass('error');
                    }
                });
                
                // Scroll to first error
                var firstError = $('.error-text:visible').first();
                if (firstError.length) {
                    $('html, body').animate({
                        scrollTop: firstError.offset().top - 100
                    }, 300);
                }
                
                return;
            }
            
            // Hide any error messages
            $('#receiver_error').hide();
            $('#amount_error').hide();
            $('#receiver_search').removeClass('error');
            $('#xp_amount').removeClass('error');
            
            // Calculate values
            var yam = amount * 1000000; // 1 XP = 1,000,000 YAM
            var newBalance = currentBalance - amount;
            
            // Update summary
            $('#summary_receiver').text(receiverName + ' (' + receiverEmail + ')');
            $('#summary_amount').html(formatScientificNotation(amount));
            $('#summary_yam').text(yam.toLocaleString('en-US', {maximumFractionDigits: 2}));
            $('#summary_new_balance').html(formatScientificNotation(newBalance));
            
            // Hide form and show summary
            $('#send-xp-form').hide();
            $('#transfer_summary').show();
            
            // Scroll to summary
            $('html, body').animate({
                scrollTop: $('#transfer_summary').offset().top - 100
            }, 300);
        });
        
        // Confirm button - Actually submit the form
        $(document).on('click', '#confirm_btn', function(e) {
            e.preventDefault();
            
            // Get form values
            var receiverId = $('#receiver_id').val();
            var amount = parseFloat($('#xp_amount').val()) || 0;
            var memo = $('#memo').val() || '';
            var nonce = $('#xp_transfer_nonce').val();
            
            // Disable button and show loading state
            var $confirmBtn = $(this);
            var originalText = $confirmBtn.text();
            $confirmBtn.prop('disabled', true).text('<?php echo esc_js(__('Sending...', 'cpm-dongtrader')); ?>');
            
            // Submit via AJAX
            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'send_xp_transfer',
                    receiver_id: receiverId,
                    xp_amount: amount,
                    memo: memo,
                    nonce: nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        alert(response.data.message || '<?php echo esc_js(__('XP transferred successfully!', 'cpm-dongtrader')); ?>');
                        
                        // Reload page to show updated balance and transaction history
                        window.location.reload();
                    } else {
                        // Show error message
                        alert(response.data.message || '<?php echo esc_js(__('Transfer failed. Please try again.', 'cpm-dongtrader')); ?>');
                        $confirmBtn.prop('disabled', false).text(originalText);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Transfer error:', error);
                    alert('<?php echo esc_js(__('An error occurred. Please try again.', 'cpm-dongtrader')); ?>');
                    $confirmBtn.prop('disabled', false).text(originalText);
                }
            });
        });
        
        // Cancel confirm button - Go back to form
        $(document).on('click', '#cancel_confirm_btn', function(e) {
            e.preventDefault();
            $('#transfer_summary').hide();
            $('#send-xp-form').show();
        });
        
        // Transaction filter functionality
        $(document).on('click', '.transaction-filter-btn', function() {
            var filter = $(this).data('filter');
            
            // Remove active class from all buttons
            $('.transaction-filter-btn').removeClass('active');
            $('.transaction-filter-btn').css({
                'background': '#f9fafb',
                'color': '#6b7280',
                'border-color': '#e5e7eb'
            });
            
            // Add active class to clicked button
            $(this).addClass('active');
            $(this).css({
                'background': 'linear-gradient(135deg, #059669 0%, #047857 100%)',
                'color': '#ffffff',
                'border-color': '#047857'
            });
            
            // Filter table rows
            if (filter === 'all') {
                $('.transaction-row').removeClass('hidden').show();
            } else if (filter === 'sent') {
                $('.transaction-row').each(function() {
                    if ($(this).data('transaction-type') === 'sent') {
                        $(this).removeClass('hidden').show();
                    } else {
                        $(this).addClass('hidden').hide();
                    }
                });
            } else if (filter === 'received') {
                $('.transaction-row').each(function() {
                    if ($(this).data('transaction-type') === 'received') {
                        $(this).removeClass('hidden').show();
                    } else {
                        $(this).addClass('hidden').hide();
                    }
                });
            }
        });
        
        console.log('✅ XP Transfers tabs initialized');
        console.log('💡 Click on tabs to switch between Transactions and Send XP');
    });
    </script>
    <?php
}

