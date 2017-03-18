<?php
/*
Plugin Name: SEO HTML Generator
Plugin URI: https://github.com/rdp-in/seo-html-generator
Author: Ramandeep Singh
Description: Generates html code for Yoast SEO options. Usefull when migrating Wordpress SEO to Static HTMl sites.
Version: 1.0
License: GPLv2 or later
*/

/**
* Function checks whether the dependent plugins are active or not.
* If not, then the current plugin is deactivated and an error is displayed.
**/
add_action("init", "checkCompatibility");
function checkCompatibility()
{
    $dependent_plugins = array("wordpress-seo/wp-seo.php" => "Yoast SEO plugin is deactivated. Please update it to use this plugin's functionality.");
    $activated_plugins = apply_filters('active_plugins', get_option('active_plugins'));
    $active = true;
    foreach ($dependent_plugin as $plugin => $msg) {
        if (!in_array($plugin, $activated_plugins)) {
            add_action("admin_notices", function () use ($msg) {
                $class = 'notice notice-error';
                $message = $msg;
                printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
            });
            $active = false;
        }
    }

    // If the dependent plugin is deactive, then deactivate the plugin
    if (!$active) {
        deactivate_plugins(plugin_basename(__FILE__));
        return false;
    } else {
        // hook adds the column
        add_filter('manage_pages_columns', 'addThumbColumn');
        // Hook adds the column value
        add_action('manage_pages_custom_column', 'addThumbValue', 10, 2);
        // hook adds the custom javascripts
        add_action("admin_enqueue_scripts", "adminEnqueueScripts");
        // Register the ajax call
        add_action("wp_ajax_seoGenerateHtml", "seoGenerateHtml");
        add_action("wp_ajax_nopriv_seoGenerateHtml", "seoGenerateHtml");
    }
}

/**
* Function adds a column on the pages list table
* @param array columns
* @return array the columns with the custom column
**/
function addThumbColumn($columns)
{
    // Add custom column
    $columns["seo_actions"] = __("Actions");
    // return
    return $columns;
}

/**
* Function adds the custom column value
* @param string the current column slug
* @param int the page id
* @return void
**/
function addThumbValue($column_slug, $page_id)
{
    // Check if the column_slug is the custom one.
    if ($column_slug == "seo_actions") {
        echo "<button id='".$page_id."' class='button button-primary seo-btn'>Generate Seo Html</button>";
    }
}


/**
* Function enqueues the scripts on the admin page
**/
function adminEnqueueScripts()
{
    // Register the script
    wp_register_script("seo-js", plugin_dir_url(__FILE__)."assets/js/seo-html-generator.js");
    // Localize the script with the ajax url
    wp_localize_script("seo-js", "ajax_data", array("ajax_url" => admin_url("admin-ajax.php")));
    // Enqueue it
    wp_enqueue_script("seo-js", array("jquery"));
}


/**
* Function generates the html content and renders it
**/
function seoGenerateHtml()
{
    // validate the page id
    if (isset($_POST["pageId"]) && $_POST["pageId"] > 0) {
        // store it
        $page_id = filter_input(INPUT_POST, "pageId");
        // Get the page object
        $page = get_post($page_id);
        // Check if it is valid
        if (isset($page) && isset($page->ID)) {
            // Get the yoast description
            $description = get_post_meta($page->ID, "_yoast_wpseo_metadesc", true);
            // Get the page url
            $link = get_permalink($page->ID);
            // Get the locale i.e. labnguage set
            $locale = get_locale();
            // Get site name
            $sitename = get_bloginfo("name");
            // Get the type of page
            $type = seoGetType($page);
            // Get the yoast set title
            $title = get_post_meta($page->ID, "_yoast_wpseo_title", true);

            $meta_keywords = get_post_meta($page->ID, "_yoast_wpseo_metakeywords", true);

            if ($title == "" && strlen($title) == 0) {
                $title = $page->post_title." - ".$sitename;
            }


            // Create the seo html
            $html = "";
            if ($description != "" && strlen($description) > 0) {
                $html = "<meta name='description' content= '".$description."'/>\n";
                $html .= "<meta name='robots' content='noodp'/>\n";
            }
            if ($meta_keywords != "" && strlen($meta_keywords) > 0) {
                $html .= "<meta name='keywords' content='".$meta_keywords."'/>\n";
            }

            $html .= "<link rel='canonical' href='".$link."' />\n";
            $html .= "<meta property='og:locale' content='".$locale."' />\n";
            $html .= "<meta property='og:type' content='".$type."' />\n";

            $html .= "<meta property='og:title' content='".$title."' />\n";

            if ($description != "" && strlen($description) > 0) {
                $html .= "<meta property='og:description' content='".$description."' />\n";
            }

            $html .= "<meta property='og:url' content='".$link."' />\n";
            $html .= "<meta property='og:site_name' content='".$sitename."' />\n";
            $html .= "<meta name='twitter:card' content='summary' />\n";

            if ($description != "" && strlen($description) > 0) {
                $html .= "<meta name='twitter:description' content='".$description."'/>\n";
            }

            $html .= "<meta name='twitter:title' content='".$title."' />\n";

            if (get_option('page_on_front') == $page->ID) {
                if (class_exists("WPSEO_JSON_LD")) {
                    $wpseo_json_ld = new WPSEO_JSON_LD();
                    ob_start();
                    $wpseo_json_ld->website();
                    $html .= ob_get_clean();
                }
            }

            echo json_encode(array(
                "result" => "success",
                "data" => $html
                ));
            die();
        }
    }
    echo json_encode(array(
        "result" => "failure"
        ));
    die();
}


/**
* function returns the type of the page
* @param object page
* @return string
**/
function seoGetType($page)
{
    $type = WPSEO_Meta::get_value('og_type');
    if ($type === '') {
        $type = 'article';
    }

    if (get_option("page_on_front") == $page->ID || get_option("page_for_posts") == $page->ID) {
        $type = 'website';
    }
    return $type;
}
