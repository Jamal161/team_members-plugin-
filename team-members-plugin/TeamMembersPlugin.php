<?php
/*
Plugin Name: Team Members Plugin
Description: Custom plugin to manage team members.
Version: 1.0
Author: Md Jamal Uddin
*/

class TeamMembersPlugin {
    private $post_type_name;
    private $post_type_slug;

    public function __construct() {
        $this->post_type_name = get_option('team_members_post_type_name', 'Team Members');
        $this->post_type_slug = get_option('team_members_post_type_slug', 'team-member');

        add_action('init', array($this, 'register_post_type'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_team_member_details'));
        add_action('init', array($this, 'pagination_rewrite_rules'));
        add_filter('post_type_link', array($this, 'custom_post_type_permalink'), 10, 2);
        add_action('pre_get_posts', array($this, 'modify_archive_query'));

        // Settings page
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'register_settings'));

        // Shortcode
        add_shortcode('team_members', array($this, 'team_members_shortcode'));
    }

    public function register_post_type() {
        $labels = array(
            'name'               => $this->post_type_name,
            'singular_name'      => 'Team Member',
            'menu_name'          => $this->post_type_name,
            'add_new'            => 'Add New',
            'add_new_item'       => 'Add New Team Member',
            'edit_item'          => 'Edit Team Member',
            'new_item'           => 'New Team Member',
            'view_item'          => 'View Team Member',
            'search_items'       => 'Search Team Members',
            'not_found'          => 'No team members found',
            'not_found_in_trash' => 'No team members found in trash',
        );

        $args = array(
            'labels'             => $labels,
            'public'             => true,
            'has_archive'        => true,
            'menu_icon'          => 'dashicons-groups',
            'supports'           => array('title', 'editor', 'thumbnail', 'custom-fields'),
            'rewrite'            => array('slug' => $this->post_type_slug),
        );

        register_post_type('team_member', $args);
    }

    public function add_meta_boxes() {
        add_meta_box(
            'team_member_details',
            'Team Member Details',
            array($this, 'team_member_details_callback'),
            'team_member',
            'normal',
            'high'
        );
    }

    public function team_member_details_callback($post) {
        $position = get_post_meta($post->ID, '_team_member_position', true);
        $bio = get_post_meta($post->ID, '_team_member_bio', true);
        $picture = get_post_meta($post->ID, '_team_member_picture', true);

        ?>
        <label for="team_member_position">Position:</label>
        <input type="text" id="team_member_position" name="team_member_position" value="<?php echo esc_attr($position); ?>" style="width: 100%;"><br>

        <label for="team_member_bio">Bio:</label>
        <textarea id="team_member_bio" name="team_member_bio" style="width: 100%;"><?php echo esc_textarea($bio); ?></textarea><br>

        <label for="team_member_picture">Picture URL:</label>
        <input type="text" id="team_member_picture" name="team_member_picture" value="<?php echo esc_attr($picture); ?>" style="width: 100%;"><br>
        <?php
    }

    public function save_team_member_details($post_id) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

        if (isset($_POST['team_member_position'])) {
            update_post_meta($post_id, '_team_member_position', sanitize_text_field($_POST['team_member_position']));
        }

        if (isset($_POST['team_member_bio'])) {
            update_post_meta($post_id, '_team_member_bio', sanitize_text_field($_POST['team_member_bio']));
        }

        if (isset($_POST['team_member_picture'])) {
            update_post_meta($post_id, '_team_member_picture', esc_url_raw($_POST['team_member_picture']));
        }
    }

    public function pagination_rewrite_rules() {
        add_rewrite_rule(
            $this->post_type_slug . '/page/([0-9]+)/?$',
            'index.php?post_type=team_member&paged=$matches[1]',
            'top'
        );
        flush_rewrite_rules();
    }

    public function custom_post_type_permalink($permalink, $post) {
        if ($post->post_type == 'team_member') {
            return home_url("/$this->post_type_slug/{$post->post_name}/");
        }
        return $permalink;
    }

    public function modify_archive_query($query) {
        if (is_admin() || !$query->is_main_query()) {
            return;
        }

        if (is_post_type_archive('team_member') && $query->is_main_query()) {
            $query->set('posts_per_page', get_option('posts_per_page', 10));
            $query->set('paged', (get_query_var('paged')) ? get_query_var('paged') : 1);
        }
    }

    // Settings page functions
    public function add_settings_page() {
        add_menu_page(
            'Team Members Settings',
            'Team Members Settings',
            'manage_options',
            'team-members-settings',
            array($this, 'settings_page_content'),
            'dashicons-admin-generic',
            30
        );
    }

    public function settings_page_content() {
        ?>
        <div class="wrap">
            <h1>Team Members Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('team_members_settings_group'); ?>
                <?php do_settings_sections('team-members-settings'); ?>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function register_settings() {
        register_setting('team_members_settings_group', 'team_members_post_type_name');
        register_setting('team_members_settings_group', 'team_members_post_type_slug');

        add_settings_section(
            'team_members_general_settings',
            'General Settings',
            array($this, 'general_settings_callback'),
            'team-members-settings'
        );

        add_settings_field(
            'team_members_post_type_name',
            'Post Type Name',
            array($this, 'post_type_name_callback'),
            'team-members-settings',
            'team_members_general_settings'
        );

        add_settings_field(
            'team_members_post_type_slug',
            'Post Type Slug',
            array($this, 'post_type_slug_callback'),
            'team-members-settings',
            'team_members_general_settings'
        );
    }

    public function general_settings_callback() {
        echo '<p>Configure general settings for the Team Members plugin.</p>';
    }

    public function post_type_name_callback() {
        $post_type_name = get_option('team_members_post_type_name', 'Team Members');
        echo '<input type="text" name="team_members_post_type_name" value="' . esc_attr($post_type_name) . '" />';
    }

    public function post_type_slug_callback() {
        $post_type_slug = get_option('team_members_post_type_slug', 'team-member');
        echo '<input type="text" name="team_members_post_type_slug" value="' . esc_attr($post_type_slug) . '" />';
    }

    // Shortcode function
    public function team_members_shortcode($atts) {
        ob_start();

        $atts = shortcode_atts(
            array(
                'number'       => 3,
                'position'     => 'top',
                'show_button'  => true,
            ),
            $atts,
            'team_members'
        );

        $args = array(
            'post_type'      => 'team_member',
            'posts_per_page' => $atts['number'],
        );

        $team_members = new WP_Query($args);

        if ($team_members->have_posts()) :
            while ($team_members->have_posts()) : $team_members->the_post();
                ?>
                <div class="team-member">
                    <?php
                    $position = get_post_meta(get_the_ID(), '_team_member_position', true);
                    $bio = get_post_meta(get_the_ID(), '_team_member_bio', true);
                    $picture = get_post_meta(get_the_ID(), '_team_member_picture', true);

                    if ($atts['position'] == 'bottom') {
                        echo '<h2>' . get_the_title() . '</h2>';
                        echo '<p>' . esc_html($position) . '</p>';
                        echo '<img src="' . esc_url($picture) . '" alt="' . esc_attr(get_the_title()) . '">';
                    } else {
                        echo '<img src="' . esc_url($picture) . '" alt="' . esc_attr(get_the_title()) . '">';
                        echo '<h2>' . get_the_title() . '</h2>';
                        echo '<p>' . esc_html($position) . '</p>';
                    }
                    ?>
                    <div class="bio"><?php echo wpautop(esc_html($bio)); ?></div>
                    <?php if ($atts['show_button']) : ?>
                        <a href="<?php echo get_post_type_archive_link('team_member'); ?>" class="see-all-button">See All</a>
                    <?php endif; ?>
                </div>
            <?php
            endwhile;
            wp_reset_postdata();
        else :
            echo 'No team members found';
        endif;

        return ob_get_clean();
    }
}

$team_members_plugin = new TeamMembersPlugin();
