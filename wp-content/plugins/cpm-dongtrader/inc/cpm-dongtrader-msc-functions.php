<?php
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
            'totals' => $smallstreet_reward_10_c
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
                'xp_awarded'    => 10000000 // XP awarded to buyer for this order
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
                'xp_awarded'    => 10000000 // XP awarded to buyer for this order
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
            'name' => __('My Treasury', 'cpm-dongtrader'),
            'slug' => 'detente-treasury',
            'position' => 2,
        ],

        [
            'name' => __('Group', 'cpm-dongtrader'),
            'slug' => 'detente-group',
            'position' => 3,
        ],

        [
            'name' => __('Seller Income', 'cpm-dongtrader'),
            'slug' => 'detente-commission',
            'position' => 4,
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
}
add_action('wp_loaded', 'add_custom_tab_to_my_account');



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

