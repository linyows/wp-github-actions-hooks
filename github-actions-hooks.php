<?php
/**
 * GitHub Actions Hooks
 *
 * @package           GitHubActionsHooks
 * @author            linyows
 * @copyright         2020 Tomohisa Oda
 * @license           GPL-2.0
 *
 * @wordpress-plugin
 * Plugin Name:       GitHub Actions Hooks
 * Plugin URI:        https://github.com/linyows/wp-github-actions-hooks
 * Description:       Wordpress hooks to GitHub Actions.
 * Version:           1.0.0
 * Author:            linyows
 * Author URI:        https://github.com/linyows
 * License:           GPL
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       github-actions-hooks
 */
defined('ABSPATH') or die('You do not have access to this file.');

class gitHubActionsHooks {
    public function __construct() {
        add_action('admin_menu', array($this, 'create_plugin_settings_page'));
        add_action('admin_init', array($this, 'setup_sections'));
        add_action('admin_init', array($this, 'setup_fields'));
        add_action('save_post', array($this, 'github_dispatch'), 10, 2);
        add_action('save_page', array($this, 'github_dispatch'), 10, 2);
        add_action('acf_save_post', array($this, 'github_dispatch'), 20);
    }

    public function add_settings_page() {
        ?>
        <div class="wrap">
            <h1>GitHub Actions Hooks</h1>
            <hr>
            <form method="POST" action="options.php">
                <?php
                settings_fields('publish_to_github_fields');
                do_settings_sections('publish_to_github_fields');
                submit_button();
                ?>
            </form>
            <footer>
                <h3>Documentation</h3>
                <p><a href="https://github.com/linyows/wp-github-actions-hooks/">Github repository</a></p>
            </footer>

        </div>
        <?php
    }

    public function github_dispatch($post_id, $post) {
        $status = $post->post_status;
        $webhook_url = get_option('webhook_address');

        if (($status === 'publish') && (!empty($webhook_url))) {
           wp_remote_post($webhook_url);
        }
    }

    public function create_plugin_settings_page() {
        $page_title = 'GitHub Actions Hooks';
        $menu_title = 'GitHub Actions Hooks';
        $capability = 'manage_options';
        $slug = 'hooks_to_github_fields';
        $callback = array($this, 'add_settings_page');
        add_options_page($page_title, $menu_title, $capability, $slug, $callback);
    }

    public function setup_sections() {
        add_settings_section('github_settings_section', 'Settings', array($this, 'section_callback'), 'hooks_to_github_fields');
    }

    public function section_callback($arguments){
        switch ($arguments['id']) {
            case 'github_settings_section':
                break;
        }
    }

    public function setup_fields() {
        $fields = array(
            array(
                'uid' => 'webhook_address',
                'label' => 'GitHub Build Hook URL',
                'section' => 'github_settings_section',
                'type' => 'text',
                'placeholder' => 'github.com',
                'default' => '',
            ),
        );

        foreach ($fields as $field) {
            add_settings_field($field['uid'], $field['label'], array($this, 'field_callback'), 'hooks_to_github_fields', $field['section'], $field);
            register_setting('hooks_to_github_fields', $field['uid']);
        }
    }

    public function field_callback($arguments) {
        $value = get_option($arguments['uid']);

        if (!$value) {
            $value = $arguments['default'];
        }

        switch ($arguments['type']) {
            case 'text':
            case 'password':
            case 'number':
                printf('<input name="%1$s" id="%1$s" type="%2$s" placeholder="%3$s" value="%4$s" />', $arguments['uid'], $arguments['type'], $arguments['placeholder'], $value);
                break;
            case 'textarea':
                printf('<textarea name="%1$s" id="%1$s" placeholder="%2$s" rows="5" cols="50">%3$s</textarea>', $arguments['uid'], $arguments['placeholder'], $value);
                break;
            case 'select':
            case 'multiselect':
                if (!empty($arguments['options']) && is_array($arguments['options'])) {
                    $attributes = '';
                    $options_markup = '';
                    foreach ($arguments['options'] as $key => $label) {
                        $options_markup .= sprintf('<option value="%s" %s>%s</option>', $key, selected($value[array_search($key, $value, true)], $key, false), $label);
                    }
                    if ($arguments['type'] === 'multiselect') {
                        $attributes = ' multiple="multiple" ';
                    }
                    printf('<select name="%1$s[]" id="%1$s" %2$s>%3$s</select>', $arguments['uid'], $attributes, $options_markup);
                }
                break;
            case 'radio':
            case 'checkbox':
                if (!empty($arguments['options']) && is_array($arguments['options'])) {
                    $options_markup = '';
                    $iterator = 0;
                    foreach ($arguments['options'] as $key => $label) {
                        $iterator++;
                        $options_markup .= sprintf('<label for="%1$s_%6$s"><input id="%1$s_%6$s" name="%1$s[]" type="%2$s" value="%3$s" %4$s /> %5$s</label><br/>', $arguments['uid'], $arguments['type'], $key, checked($value[array_search($key, $value, true)], $key, false), $label, $iterator);
                    }
                    printf('<fieldset>%s</fieldset>', $options_markup);
                }
                break;
        }
    }
}

new gitHubActionsHooks;
