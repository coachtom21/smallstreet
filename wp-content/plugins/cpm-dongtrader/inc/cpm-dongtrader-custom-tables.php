<?php 

// All custom tables required for MLM operations 


/*Create database tables where we can save api response details*/
function dongtrader_create_dbtable()
{

    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    /* create table to store custom order */
    $order_table_name = $wpdb->prefix . 'dong_order_export_table';
    if ($wpdb->get_var("SHOW TABLES LIKE '{$order_table_name}'") != $order_table_name) :
        $export_sql = "CREATE TABLE $order_table_name (
            id INT NOT NULL AUTO_INCREMENT ,
            customer_email VARCHAR(255) NOT NULL ,
            customer_first_name VARCHAR(255) NOT NULL ,
            customer_last_name VARCHAR(255) NOT NULL ,
            customer_phone VARCHAR(255) NOT NULL ,
            customer_country VARCHAR(255) NOT NULL ,
            customer_state VARCHAR(255) NOT NULL ,
            customer_address VARCHAR(255) NOT NULL ,
            customer_postcode VARCHAR(255) NOT NULL ,
            customer_city VARCHAR(255) NOT NULL ,
            customer_membership VARCHAR(255) NOT NULL ,
            customer_sector VARCHAR(255) NOT NULL ,
            affilate_user_id INT NOT NULL ,
            created_at VARCHAR(255) NOT NULL,
            PRIMARY KEY (id)) $charset_collate;";
        dbDelta($export_sql);
    endif;
            // product_id INT NOT NULL ,
            // product_varition_id INT NOT NULL ,
    // // Create users glassfrog details table
    // $user_details_table = $wpdb->prefix . 'glassfrog_user_data';
    // if ($wpdb->get_var("SHOW TABLES LIKE '{$user_details_table}'") != $user_details_table):
    //     $glassfrog_user_sql = "CREATE TABLE $user_details_table (
    //         id INT NOT NULL AUTO_INCREMENT ,
    //         user_id INT ,
    //         gf_person_id VARCHAR(255) ,
    //         circle_id	int(11),
    //         gf_name VARCHAR(255) ,
    //         in_glassfrog VARCHAR(255) NOT NULL ,
    //         all_orders VARCHAR(255) NOT NULL ,
    //         group_id INT,
    //         upline_id INT,
    //         PRIMARY KEY (id),
    //         INDEX (user_id),
    //         FOREIGN KEY (upline_id) REFERENCES {$user_details_table}(user_id)
    //         ) $charset_collate;";
    //     dbDelta($glassfrog_user_sql);
    // endif;




    $release_group_profit = $wpdb->prefix . 'release_groups_profit';
    if ($wpdb->get_var("SHOW TABLES LIKE '{$release_group_profit}'") != $release_group_profit) {
        $release_group_profit_query = "CREATE TABLE {$release_group_profit} (
            id INT NOT NULL AUTO_INCREMENT,
            release_date DATETIME,
            release_amount INT NOT NULL,
            release_note VARCHAR(255) NOT NULL,
            group_id INT,
            PRIMARY KEY (id)
        ) $charset_collate;";
        dbDelta($release_group_profit_query);
    }

    // Adding new database structure for mlm

    // M.l.m users table
    $mega_mlm_users =  $wpdb->prefix . 'mega_mlm_customers';

    $group_details_table = $wpdb->prefix . 'mega_mlm_groups';

    if ($wpdb->get_var("SHOW TABLES LIKE '{$mega_mlm_users}'") != $mega_mlm_users) {

        $mega_mlm_users_query = "CREATE TABLE {$mega_mlm_users} (
            id INT NOT NULL AUTO_INCREMENT,
            user_id INT,
            upline_id INT,
            customer_group_id INT(11),
            glassfrog_person_id BIGINT,
            person_name VARCHAR(255),
            circle_id INT(11),
            created_date TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            user_status INT(11) NOT NULL,
            PRIMARY KEY (id),
            UNIQUE(user_id),
            INDEX(user_id),
            FOREIGN KEY (customer_group_id) REFERENCES {$group_details_table}(group_id)
        ) $charset_collate;";

        dbDelta($mega_mlm_users_query);
    }

    //mlm tree data

    if ($wpdb->get_var("SHOW TABLES LIKE '{$group_details_table}'") != $group_details_table) :
        $group_details_sql = "CREATE TABLE $group_details_table (
                group_id INT NOT NULL AUTO_INCREMENT,
                group_members VARCHAR(255) NOT NULL,
                circle_id INT NOT NULL,
                circle_name VARCHAR(255) NOT NULL,
                created_date DATETIME NOT NULL,
                group_leader INT NOT NULL,
                leader_since DATETIME NOT NULL,
                leadership_expires DATETIME NOT NULL,
                distribution_status TINYINT(2) NOT NULL,
                total_group_profit VARCHAR(255),
                PRIMARY KEY (group_id)
            ) $charset_collate;";
        dbDelta($group_details_sql);
    endif;

    //downline table
    // $mega_mlm_downline =  $wpdb->prefix . 'mega_mlm_downline';

    // if ($wpdb->get_var("SHOW TABLES LIKE '{$mega_mlm_downline}'") != $mega_mlm_downline) {

    //     $mega_mlm_downline_query = "CREATE TABLE {$mega_mlm_downline} (
    //         downline_id INT NOT NULL AUTO_INCREMENT,
    //         user_id INT,
    //         downline_user_id INT,
    //         user_level INT, 
    //         PRIMARY KEY (downline_id),
    //         INDEX (user_id),
    //         INDEX (downline_user_id),
    //         FOREIGN KEY (user_id) REFERENCES {$mega_mlm_users}(user_id),
    //         FOREIGN KEY (downline_user_id) REFERENCES {$mega_mlm_users}(user_id)
    //     ) $charset_collate;";

    //     dbDelta($mega_mlm_downline_query);
    // }

    $mega_mlm_sales = $wpdb->prefix . 'mega_mlm_purchases';
    if ($wpdb->get_var("SHOW TABLES LIKE '{$mega_mlm_sales}'") != $mega_mlm_sales) {
        $mega_mlm_sales_query = "CREATE TABLE {$mega_mlm_sales} (
            sale_id INT NOT NULL AUTO_INCREMENT,
            sponsor_id INT,
            customer_id INT,
            order_id INT,
            allocation_status TINYINT(2) NOT NULL,
            PRIMARY KEY (sale_id),
            FOREIGN KEY (customer_id) REFERENCES {$mega_mlm_users}(user_id),
            FOREIGN KEY (sponsor_id) REFERENCES {$mega_mlm_users}(user_id)
        ) $charset_collate;";

        dbDelta($mega_mlm_sales_query);
    }


    // $mlm_allocation_table = $wpdb->prefix . 'mega_mlm_allocation';

    // if ($wpdb->get_var("SHOW TABLES LIKE '{$mlm_allocation_table}'") != $mlm_allocation_table) {
    //     $mlm_allocation_table_query = "CREATE TABLE {$mlm_allocation_table} (
    //         id INT NOT NULL AUTO_INCREMENT,
    //         rebate varchar(255),
    //         process varchar(255),
    //         profit varchar(255),
    //         profit_individual varchar(255),
    //         profit_group varchar(255),
    //         profit_commission varchar(255),
    //         commission_individual varchar(255),
    //         commission_group varchar(255),
    //         treasury varchar(255),
    //         sponsor_id INT,
    //         group_id INT,
    //         status varchar(255)
    //         PRIMARY KEY (id),
    //     ) $charset_collate;";

    //     dbDelta($mlm_allocation_table_query);
    // }


    // $mlm_distribution =  $wpdb->prefix . 'mega_mlm_distribution';

    //     if ($wpdb->get_var("SHOW TABLES LIKE '{$mlm_distribution}'") != $mlm_distribution) {
    //     $mlm_distribution_query = "CREATE TABLE {$mlm_distribution} (
    //         id INT NOT NULL AUTO_INCREMENT,
    //         from_order INT ,
    //         from_product INT,
            
    //     ) $charset_collate;";

    //     dbDelta($mega_mlm_sales_query);
    // }

}
add_action('plugin_loaded', 'dongtrader_create_dbtable');