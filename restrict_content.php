<?php
/**
 * @package Restrict WordPress Content
 * @version 1.0
 */
/*
Plugin Name: ScriptHere's Restrict WordPress Content.
Plugin URI: https://github.com/blogscripthere/restrict_content
Description:  It's a simple plugin to restrict content to users who are logged into WordPress and those who are not logged in
Author: Narendra Padala
Author URI: https://in.linkedin.com/in/narendrapadala
Text Domain: sh
Version: 1.0
Last Updated: 24/02/2018
*/


/**
 * Adding restrict content option meta box at the post or page edit screen hooks.
 */
add_action( 'add_meta_boxes' ,          'sh_register_logged_users_meta_box');
add_action( 'save_post'     ,           'sh_rc_save_callback' , 10, 2 );

/**
 * Register restrict content option meta box at the post or page edit screen
 */
function sh_register_logged_users_meta_box() {
    //set show / hide content meta box
    add_meta_box('logged-users-meta-box-id', __('Restrict Content', 'shrc'), 'sh_rc_display_callback', array('post', 'page'), 'side', 'high');
}
/**
 * Restrict content option meta box at the post or page edit screen callback
 */
function sh_rc_display_callback( $post) {
    //check
    $check =  (get_post_meta($post->ID,'logged_users_check'))? get_post_meta($post->ID,'logged_users_check',true):false;
    //set checked
    $checked = ($check)? 'checked':'';
    //add nonce field
    wp_nonce_field( 'logged_users_nonce_action', 'logged_users_nonce' );
    //add note
    echo "<p>The individual parts of the post content should have the following tag in it: <br/><i>[logged_users] your content [/logged_users]</i></p>";
    //check box
    echo '<p><input name="logged_users_check" value="1" type="checkbox" '.$checked.'> <span>Show logged in users</span></p>';
}
/**
 * Restrict content option meta box at the post or page edit screen option selection save callback
 */
function sh_rc_save_callback( $post_id, $post ) {
    // Add nonce for security and authentication.
    $users_nonce = isset($_POST['logged_users_nonce']) ? $_POST['logged_users_nonce'] : '';
    // Check if nonce is set.
    if (!isset($users_nonce)) {
        return;
    }
    // Check if nonce is valid.
    if (!wp_verify_nonce($users_nonce, 'logged_users_nonce_action')) {
        return;
    }
    //get
    $login_check = isset($_POST['logged_users_check']) ? $_POST['logged_users_check'] : false;
    //check
    if(!get_post_meta($post_id,'logged_users_check')){
        add_post_meta($post_id,'logged_users_check',$login_check);
    }else{
        update_post_meta($post_id,'logged_users_check',$login_check);
    }
}
/**
 * Based on restrict content option at the post or page edit screen option selection hide/show posts list callback
 */
function sh_hide_posts_list_callback(){
    //set args
    $args = array(
        'meta_key' => 'logged_users_check',
        'meta_value' => '1'
    );
    //query
    $query =  new WP_Query( $args );
    //get post ids
    $post_ids = wp_list_pluck( $query->posts, 'ID' );
    //return
    return (!empty($post_ids)) ? $post_ids : array();
}

/**
 * Based on restrict posts list hide posts list callback
 */
function sh_set_hide_posts_callback($query) {
    //check if user not logged in hide selected posts
    if(!is_user_logged_in()){
        //set
        $query->set('post__not_in', sh_hide_posts_list_callback());
    }
}

/**
 * Set hide posts list, if user not logged in
 */
add_action('pre_get_posts', 'sh_set_hide_posts_callback');


//logged in user details callback
function sh_logged_users_details_callback(){
    // current user
    if( function_exists( 'wp_get_current_user' ) ){
        $user = wp_get_current_user();
    }else{
        global $user_ID;
        // pick
        $user = get_userdata($user_ID);
    }
    //return
    return $user;
}

/**
 * Create short code "logged_users" to show content only for logged in users callback.
 */
function sh_logged_users_callback($args, $content, $tag){
    //check
    if(!is_user_logged_in()) {
        //return
        return apply_filters( "msg_logged_users_filter","<p> You don't have access content, please login to access the content ...!");
    }
    //return
    return $content;
}
/**
 * Create short code "logged_users" to show content only for logged in users hook.
 * usage : [logged_users] logged users content [/logged_users]
 */
add_shortcode('logged_users','sh_logged_users_callback');



/**
 * Create short code "non_logged_users" to show content only for non logged in users callback.
 */
function sh_non_logged_users_callback($args, $content, $tag){
    //check
    if(is_user_logged_in()) {
        //return
        return apply_filters( "msg_non_logged_users_filter","<p> You don't have access content, only non logged users have access the content ...!");
    }
    //return
    return $content;
}
/**
 * Create short code "non_logged_users" to show content only for non logged in users hook.
 * usage : [non_logged_users] non logged users content [/non_logged_users]
 */
add_shortcode('non_logged_users','sh_non_logged_users_callback');


/**
 * Create short code "user_role_is" restrict content by role only for logged in users callback
 */
function sh_user_role_is_callback($args, $content, $tag){
    //check
    if(is_user_logged_in()) {
        //check
        $args = is_array($args) ? array_shift($args) : NULL;
        $arguments = str_replace('#', '', $args);
        $user_roles = explode('|', $arguments);
        //get details
        $roles = sh_logged_users_details_callback()->roles;
        //check
        if(array_intersect($user_roles,$roles)){
            //return
            return $content;
        }else{
            //return
            return apply_filters( "msg_user_role_is_login_filter","<p> You don't have access content for your role...!");
        }
    }else{
        //return
        return apply_filters( "msg_user_role_is_login_out_filter","<p> You don't have access content, please login to access the content ...!");
    }
}
/**
 * Create short code "user_role_is" restrict content by role only for logged in users hook.
 * usage : [user_role_is #author|administrator] your user role content [/user_role_is]
 */
add_shortcode('user_role_is','sh_user_role_is_callback');


/**
 * Create short code "user_id_is" restrict content by user id only for logged in users callback
 */
function sh_user_id_is_callback($args, $content, $tag){
    //check
    if(is_user_logged_in()) {
        //check
        $args = is_array($args) ? array_shift($args) : NULL;
        $arguments = str_replace('#', '', $args);
        $user_ids = explode('|', $arguments);
        //get details
        $user_id = sh_logged_users_details_callback()->ID;
        //check
        if(in_array($user_id,$user_ids)){
            //return
            return $content;
        }else{
            //return
            return apply_filters( "msg_user_id_is_login_filter","<p> You don't have access content, please contact admin to access the content ...!");
        }

    }else{
        //return
        return apply_filters( "msg_user_id_is_login_out_filter","<p> You don't have access content, please login to access the content ...!");
    }
}
/**
 * Create short code "user_id_is" restrict content by user id only for logged in users hook
 * usage : [user_id_is #123|456] your user role content [/user_id_is]
 */
add_shortcode('user_id_is','sh_user_id_is_callback');


/**
 * Create short code "user_login_is" restrict content by user login only for logged in users callback
 */

function sh_user_login_is_callback($args, $content, $tag){
    //check
    if(is_user_logged_in()) {
        //check
        $args = is_array($args) ? array_shift($args) : NULL;
        $arguments = str_replace('#', '', $args);
        $users = explode('|', $arguments);
        //get details
        $user = sh_logged_users_details_callback()->data->user_login;
        //check
        if(in_array($user,$users)){
            //return
            return $content;
        }else{
            //return
            return apply_filters( "msg_user_login_is_login_filter","<p> You don't have access content, please contact admin to access the content ...!");
        }

    }else{
        //return
        return apply_filters( "msg_user_login_is_login_out_filter","<p> You don't have access content, please login to access the content ...!");
    }
}

/**
 * Create short code "user_login_is" restrict content by user login only for logged in users hook
 * usage : [user_login_is #narendra|satvik|admin] your user role content [/user_login_is]
 */
add_shortcode('user_login_is','sh_user_login_is_callback');
