<?php
/**
 * @author Ryan Harris <info@ryanbuiltthat.com>
 * @package WPNDC
 * @category plugins
 * @copyright Copyright (c) 2015, Ryan Harris
 * Created by PhpStorm.
 * User: Ryan
 * Date: 7/16/2015
 * Time: 10:44 PM
 */


/**
 * Let's make sure this file can't be accessed directly
 * @link http://mikejolley.com/2013/08/keeping-your-shit-secure-whilst-developing-for-wordpress/
 */
if (!defined('ABSPATH')) exit;


/**
 * Class EPKADV
 * @version 1.2.0
 * @todo Create sub classes to handled the getting/setting
 */
class EPKADV
{
    /**
     * WordPress Database Object
     * @var object $wpdb
     * @see EPKADV::__constructor()
     * @link https://codex.wordpress.org/Class_Reference/wpdb
     */
    protected $_wpdb;

    /**
     * The URL action to define for triggering the cloning
     * @var string $_action
     * @see EPKADV::setAction()
     */
    protected $_action;

    /**
     * Defines the post type of the TARGET post: (post, page, {custom-post-name})
     * @var string $_new_post_type
     * @see EPKADV::setNewPostType()
     * @link https://codex.wordpress.org/Post_Types
     */
    protected $_new_post_type;

    /**
     * Defines the post type of the SOURCE post: (post, page, {custom-post-name})
     * @var string $_original_post_type
     * @see EPKADV::setOriginalPostType()
     * @link https://codex.wordpress.org/Post_Types
     */
    protected $_original_post_type;

    /**
     * Set the status of the TARGET post upon successful cloning
     * @var string $_new_post_status
     * @see EPKADV::setNewPostStatus()
     * @link https://codex.wordpress.org/Post_Status
     */
    protected $_new_post_status;

    /**
     * Set the ID of the TARGET post upon cloning
     * @var mixed $_new_post_id
     * @see EPKADV::setNewPostId()
     */
    protected $_new_post_id;

    /**
     * Set the ID of the SOURCE post
     * @var mixed $_original_id
     * @see EPKADV::setOriginalId
     */
    protected $_original_id;

    /**
     * Defines the status of the SOURCE post upon successful cloning
     * @var string $_original_status
     * @see EPKADV::getOriginalStatus()
     * @link https://codex.wordpress.org/Post_Status
     */
    protected $_original_status;

    /**
     * Define persistent post object
     * @var object $_post_object
     * @see EPKADV::setPostObject()
     * @link https://codex.wordpress.org/Function_Reference/get_post
     */
    protected $_post_object;


    /**
     * Constructor / Setter
     *
     * @param $wpdb
     * @source 103 127 Sets a few options, hooks into WordPress
     * @since 1.2.0 Refactored all code into a better class
     */
    public function __construct($wpdb){

        /**
         * Set up options
         */
        self::setAction('epk_copyto_project');
        self::setNewPostType('project');
        self::setNewPostStatus('draft');
        self::setOriginalStatus('draft');
        self::setOriginalPostType('epk');

        /**
         * Main function is run on admin_action from the querystring
         * @link https://codex.wordpress.org/Plugin_API/Action_Reference/admin_post_(action)
         */
        add_action('admin_action_'.self::getAction(), array($this, 'post_advance_to_new_type'));


        /**
         * Hook into `row_actions` and the edit page `metabox` to display the action button
         * @link https://developer.wordpress.org/reference/hooks/post_row_actions/
         */
        add_filter('page_row_actions', array($this, 'add_link_to_index'), 10, 2);
        add_filter('post_submitbox_misc_actions', array($this, 'post_edit_page_button' ));
    }

    /**
     * @return mixed
     */
    protected function getAction()
    {
        return $this->_action;
    }

    /**
     * @param mixed $action
     */
    protected function setAction($action)
    {
        $this->_action = $action;
    }

    /**
     * @return mixed
     */
    protected function getNewPostType()
    {
        return $this->_new_post_type;
    }

    /**
     * @param string $type
     */
    protected function setNewPostType($type)
    {
        $this->_type = $type;
    }

    /**
     * @return mixed
     */
    protected function getNewPostStatus()
    {
        return $this->_new_post_status;
    }

    /**
     * @param mixed $_new_post_status
     */
    protected function setNewPostStatus($_new_post_status)
    {
        $this->_new_post_type = $_new_post_status;
    }

    /**
     * @return string
     */
    protected function getOriginalStatus()
    {
        return $this->_original_status;
    }

    /**
     * @param string $original_status
     */
    protected function setOriginalStatus($original_status)
    {
        $this->_original_status = $original_status;
    }

    /**
     * @return mixed
     */
    protected function getOriginalId()
    {
        return $this->_original_id;
    }

    /**
     * @param mixed $original_id
     */
    protected function setOriginalId($original_id)
    {
        $this->_original_id = $original_id;
    }

    /**
     * @return mixed
     */
    protected function getOriginalPostType()
    {
        return $this->_original_post_type;
    }

    /**
     * @param mixed $original_post_type
     */
    protected function setOriginalPostType($original_post_type)
    {
        $this->_original_post_type = $original_post_type;
    }

    /**
     * @return mixed
     */
    protected function getNewPostId()
    {
        return $this->_new_post_id;
    }

    /**
     * @param mixed $new_post_id
     */
    protected function setNewPostId($new_post_id)
    {
        $this->_new_post_id = $new_post_id;
    }

    /**
     * @return mixed
     */
    protected function getPostObject()
    {
        return $this->_post_object;
    }

    /**
     * @param mixed $post_object
     */
    protected function setPostObject($post_object)
    {
        $this->_post_object = $post_object;
    }


    /**
     *  Main method that handles the post cloning
     * @since 1.2.0 Refactored all code into a better class
     */
    protected function post_advance_to_new_type(){
        if (!(isset($_GET['post']) || isset($_POST['post']) || (isset($_REQUEST['action']) && self::getAction() == $_REQUEST['action']))) {
            wp_die('No post to duplicate has been supplied!');
        }
        self::setOriginalId((isset($_GET['post']) ? $_GET['post'] : $_POST['post']));
        $post = get_post(self::getOriginalId());
        self::setPostObject($post);
        $current_user = wp_get_current_user();
        $new_post_author = $current_user->ID;
        if (isset($post) && $post != null) {
            $args = array(
                'comment_status' => $post->comment_status,
                'ping_status' => $post->ping_status,
                'post_author' => $new_post_author,
                'post_content' => $post->post_content,
                'post_excerpt' => $post->post_excerpt,
                'post_name' => $post->post_name,
                'post_parent' => $post->post_parent,
                'post_password' => $post->post_password,
                'post_status' => self::getNewPostStatus(),
                'post_title' => $post->post_title,
                'post_type' => self::getNewPostType(),
                'to_ping' => $post->to_ping,
                'menu_order' => $post->menu_order
            );
            //$new_post_id = wp_insert_post($args);
            self::setOriginalId(wp_insert_post($args));
            $taxonomies = get_object_taxonomies($post->post_type);
            foreach ($taxonomies as $taxonomy) {
                $post_terms = wp_get_object_terms(self::getOriginalId(), $taxonomy, array('fields' => 'slugs'));
                wp_set_object_terms(self::getNewPostId(), $post_terms, $taxonomy, false);
            }
            $post_meta_infos = $this->_wpdb->get_results("SELECT meta_key, meta_value FROM $this->_wpdb->postmeta WHERE post_id=".self::getOriginalId());
            if (count($post_meta_infos) != 0) {
                $sql_query = "INSERT INTO $this->_wpdb->postmeta (post_id, meta_key, meta_value) ";
                foreach ($post_meta_infos as $meta_info) {
                    if ($meta_info->meta_key == "epk_image") {
                        $meta_key = $meta_info->meta_key;
                        $meta_value = addslashes($meta_info->meta_value);
                        $sql_query_sel[] = "SELECT ".self::getNewPostId().", 'projects_image', '$meta_value'";
                    } else if ($meta_info->meta_key == "epk_gallery") {
                        $meta_key = $meta_info->meta_key;
                        $meta_value = addslashes($meta_info->meta_value);
                        $sql_query_sel[] = "SELECT ".self::getNewPostId().", 'projects_gallery', '$meta_value'";
                    }else{
                        $meta_key = $meta_info->meta_key;
                        $meta_value = addslashes($meta_info->meta_value);
                        $sql_query_sel[] = "SELECT ".self::getNewPostId().", '$meta_key', '$meta_value'";
                    }
                }
                $sql_query .= implode(" UNION ALL ", $sql_query_sel);
                $this->_wpdb->query($sql_query);
                }

            self::update_original_post();
            wp_redirect(admin_url('post.php?action=edit&post=' . self::getNewPostId()));
            exit;
        }else{
            wp_die('Post creation failed, could not find original post: ' . self::getOriginalId());
        }
    }

    /**
     * Adds an action button to the source post index page
     * @link https://developer.wordpress.org/reference/hooks/page_row_actions/
     *
     * Inherits parameters from WP action
     * @param $actions
     * @param $post
     */
    public function add_link_to_index($actions, $post){
        $button_link_class = "button button-primary button-small";
        $button_icon = "<" . "span class=\"dashicons dashicons-admin-page\"></span>";
        $button_title = "Clone to Projects";
        $button_label = $button_title;
        $button_link_styles = '';
        if (current_user_can('edit_posts')) {
            if ($post->post_type == self::getOriginalPostType()) {
                //$actions['duplicate'] = 'test';
                $actions['duplicate'] = '<a style="'.$button_link_styles.'" class="'.$button_link_class.'" href="admin.php?action='.self::getAction().'&amp;post=' . $post->ID . '" title="'.$button_title.'" rel="permalink">'.$button_label.'</a>';
            }
        }
        return $actions;
    }

    /**
     * Add an action button to the source post edit page.
     * Currently this defaults to display above Publish/Update button
     *
     */
    public function post_edit_page_button(){
        $post = get_post(self::getOriginalId());
        $button_link_class = "button button-primary button-small";
        $button_icon = "<" . "span class=\"dashicons dashicons-admin-page\"></span>";
        $button_title = "Clone to Projects";
        $button_label = $button_title;
        $button_link_styles = 'float:right;margin:2em 1em;';

        if (current_user_can('edit_posts')) {
            if ($post->post_type === "epk") {
                $button = '<a style="'.$button_link_styles.'" class="'.$button_link_class.'" href="admin.php?action='.self::getAction().'&amp;post=' . $post->ID . '" title="'.$button_title.'" rel="permalink">'.$button_label.'</a>';
            }
        }
        _e($button);
    }

    /**
     * Change (Unpublish) the status of the original post. Defaults to 'draft'
     */
    protected function update_original_post(){
        $current_post = get_post(self::getOriginalId(), 'ARRAY_A');;
        $current_post['post_status'] = self::getOriginalStatus();
        wp_update_post($current_post);
    }

    /**
     * Send the source post to the Trash
     */
    protected function trash_original_post(){
        wp_trash_post( self::getOriginalId() );
    }
}