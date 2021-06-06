<?php

/**
 * Plugin Name:     Quantum Open Graph
 * Plugin URI:      https://qbitone.de
 * Description:     Plugin to output Open Graph tags
 * Author:          Andreas Geyer
 * Author URI:      https://qbitone.de
 * Text Domain:     quantum-open-graph
 * Domain Path:     /languages
 * Version:         0.2.0
 *
 * @package         Quantum_Open_Graph
 */

if (!defined('ABSPATH')) exit;

define('QOP_PLUGIN_DIR', plugin_dir_path(__FILE__));


require 'vendor/autoload.php';


add_action('plugins_loaded', 'qop_load_textdomain', 10, 0);

/**
 * Fires once activated plugins have loaded.
 *
 */
function qop_load_textdomain(): void
{
    load_plugin_textdomain('quantum-open-graph', false, QOP_PLUGIN_DIR . 'languages/');
}


add_filter('language_attributes', 'qop_filter_language_attributes', 10, 2);
/**
 * Filters the language attributes for display in the 'html' tag.
 *
 * @param string $output  A space-separated list of language attributes.
 * @param string $doctype The type of HTML document (xhtml|html).
 * @return string A space-separated list of language attributes.
 */
function qop_filter_language_attributes(string $output, string $doctype): string
{
    // space in front of the string is important!
    $output .= ' prefix="og: https://ogp.me/ns#"';
    return $output;

    /**
     * Alternative possibilty to write the extension.
     * Might be better in handling further changes
     */
    // $attributes = [];
    // $attributes[] = $output;
    // $attributes[] = 'test';
    // $output = implode(' ', $attributes);
    // return $output;
}




add_action('wp_head', 'qop_output_og_tags', 500, 0);

/**
 * Prints scripts or data in the head tag on the front end.
 *
 */
function qop_output_og_tags(): void
{
    // last string concat for new line in source code
    $format = "\t" . '<meta property="og:%1$s" content="%2$s">' . "\n";

    global $wp;

    $values = array(
        "locale"        => "de_DE",
        "type"          => "website",
        "title"         => wp_get_document_title(),
        // get the full URL along with query parameters.
        "url"           => add_query_arg($wp->query_vars, home_url($wp->request)),
        "site_name"     => get_bloginfo('name'),
    );


    if (class_exists('ACF')) {
        // check if the ACF 'description' field has a value
        // TODO: field name should be optimized/prefixed to prevent naming collisons
        if (get_field('description')) {
            $values['description'] = get_field('description');
        }
    }


    $image = qop_og_image();

    $values = array_merge($values, $image);

    echo "\n";
    foreach ($values as $key => $value) {
        printf($format, $key, $value);
    }
}

function qop_og_image(): array
{
    $image = [];

    // TODO fix it for general archive pages
    $page_id = (is_home() && get_option('page_for_posts')) ? get_option('page_for_posts') : null;

    if (has_post_thumbnail($page_id)) {

        // get image url
        $url = get_the_post_thumbnail_url($page_id);
        // matches 'http:' at the beginning of the string (^) and case-insensitiv (i)
        $url_secure = preg_replace("/^http:/i", "https:", $url);
        $image['image'] = $url;
        $image['image:secure_url'] = $url_secure;

        // get image alt
        global $post;
        $image_id = $page_id ? $page_id : $post->ID;
        $thumbnail_id = get_post_thumbnail_id($image_id);
        $alt = get_post_meta($thumbnail_id, '_wp_attachment_image_alt', true);
        $image['image:alt'] = $alt;

        // get image type
        $case = exif_imagetype($url);
        switch ($case) {
            case IMAGETYPE_JPEG:
                $type = 'image/jpeg';
                break;
            case IMAGETYPE_PNG:
                $type = 'image/png';
                break;
            default:
                $type = 'image';
        }
        $image['image:type'] = $type;

        // get image height & width
        $image['image:height'] = '1200';
        $image['image:width'] = '630';
    }
    return $image;
}


$myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
    'https://github.com/QbitOne/quantum-open-graph/',
    __FILE__,
    'quantum-open-graph'
);

//Set the branch that contains the stable release.
$myUpdateChecker->setBranch('master');