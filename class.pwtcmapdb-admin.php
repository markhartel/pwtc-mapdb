<?php

class PwtcMapdb_Admin {
    private static $initiated = false;

    public static function init() {
	if ( ! self::$initiated ) {
		self::init_hooks();
	}
    }
    
    // Initializes plugin WordPress hooks.
	private static function init_hooks() {
        self::$initiated = true;
        add_action('admin_action_pwtc_copy_post_as_draft', array('PwtcMapdb_Admin', 'copy_post_as_draft'));
        add_filter('post_row_actions', array('PwtcMapdb_Admin', 'copy_post_link'), 10, 2);
        add_filter('page_row_actions', array('PwtcMapdb_Admin', 'copy_post_link'), 10, 2);
        add_action('post_submitbox_misc_actions', array('PwtcMapdb_Admin', 'copy_page_custom_button'));
    }

    public function copy_page_custom_button() {
        global $post;
        $post_status = 'draft';
        $html = '<div id="major-publishing-actions">';
        $html .= '<div id="export-action">';
        $html .= '<a href="admin.php?action=pwtc_copy_post_as_draft&amp;post='.$post->ID.'&amp;nonce='.wp_create_nonce( 'pwtc-copy-page-'.$post->ID ).'" title="Copy this as '.$post_status.'" rel="permalink">Copy This</a>';
        $html .= '</div>';
        $html .= '</div>';
        echo $html;
    }

    public static function copy_post_link($actions, $post) {
        $post_status = 'draft';
        if (current_user_can('edit_posts') or current_user_can('edit_rides')) {
            $actions['copy'] = '<a href="admin.php?action=pwtc_copy_post_as_draft&amp;post='.$post->ID.'&amp;nonce='.wp_create_nonce( 'pwtc-copy-page-'.$post->ID ).'" title="Copy this as '.$post_status.'" rel="permalink">Copy This</a>';
        }
        return $actions;
    }

    public static function copy_post_as_draft() {
       $nonce = $_REQUEST['nonce'];
       $post_id = (isset($_GET['post']) ? intval($_GET['post']) : intval($_POST['post']));

       if (wp_verify_nonce( $nonce, 'pwtc-copy-page-'.$post_id) && (current_user_can('edit_posts') or current_user_can('edit_rides'))) {
            global $wpdb;
            $suffix = ' -- Copy';
            $post_status = 'draft';
            $redirectit = 'to_page';
            if (!(isset($_GET['post']) || isset($_POST['post']) || (isset($_REQUEST['action']) && 'pwtc_copy_post_as_draft' == $_REQUEST['action']))) {
                wp_die('No post to copy has been supplied!');
            }
            $returnpage = '';            
            $post = get_post($post_id);
            /*
            * if you don't want current user to be the new post author,
            * then change next couple of lines to this: $new_post_author = $post->post_author;
            */
            $current_user = wp_get_current_user();
            $new_post_author = $current_user->ID;
            if (isset($post) && $post != null) {
                $args = array(
                    'comment_status' => $post->comment_status,
                    'ping_status' => $post->ping_status,
                    'post_author' => $new_post_author,
                    'post_content' => $post->post_content,
                    'post_excerpt' => $post->post_excerpt,
                    //'post_name' => $post->post_name,
                    'post_parent' => $post->post_parent,
                    'post_password' => $post->post_password,
                    'post_status' => $post_status,
                    'post_title' => $post->post_title.$suffix,
                    'post_type' => $post->post_type,
                    'to_ping' => $post->to_ping,
                    'menu_order' => $post->menu_order,
                );
                $new_post_id = wp_insert_post($args);

                $taxonomies = get_object_taxonomies($post->post_type);
                if (!empty($taxonomies) && is_array($taxonomies)):
                    foreach ($taxonomies as $taxonomy) {
                        $post_terms = wp_get_object_terms($post_id, $taxonomy, array('fields' => 'slugs'));
                        wp_set_object_terms($new_post_id, $post_terms, $taxonomy, false);
                    }
                endif;

                $post_meta_infos = $wpdb->get_results("SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE post_id=$post_id");
                if (count($post_meta_infos)!=0) {
                    $sql_query = "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) ";
                    foreach ($post_meta_infos as $meta_info) {
                        $meta_key = sanitize_text_field($meta_info->meta_key);
                        $meta_value = addslashes($meta_info->meta_value);
                        $sql_query_sel[]= "SELECT $new_post_id, '$meta_key', '$meta_value'";
                    }
                    $sql_query.= implode(" UNION ALL ", $sql_query_sel);
                    $wpdb->query($sql_query);
                } 

                if ($post->post_type != 'post'):
                    $returnpage = '?post_type='.$post->post_type;
                endif;
                if (!empty($redirectit) && $redirectit == 'to_list'):
                    wp_redirect(admin_url('edit.php'.$returnpage)); elseif (!empty($redirectit) && $redirectit == 'to_page'):
                    wp_redirect(admin_url('post.php?action=edit&post='.$new_post_id)); else:
                    wp_redirect(admin_url('edit.php'.$returnpage));
                endif;
                exit;
            } else {
                wp_die('Error! Post creation failed, could not find original post: '.$post_id);
            }
        } else {
            wp_die('Security check issue, Please try again.');
        }
    }
}
