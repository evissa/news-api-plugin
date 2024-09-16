<?php
/*
Plugin Name: News API Plugin
Description: This plugin fetches and display news articles from the https://newsapi.org/ API.
Author: Evisa C.
*/

// Creates options page for API settings
function create_options_page() {
    add_menu_page(
        'News Fetcher Settings',
        'News Fetcher',
        'manage_options',
        'news-fetcher-settings',
        'render_options_page',
        'dashicons-admin-generic'
    );
}
add_action('admin_menu', 'create_options_page');

function render_options_page() {
    ?>
    <div class="wrap">
        <h2>News Fetcher Settings</h2>
        <form method="post" action="options.php">
            <?php
            settings_fields('settings_group');
            do_settings_sections('news-fetcher-settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

function register_settings() {
    register_setting('settings_group', 'api_endpoint');
    register_setting('settings_group', 'pagination_limit');

    add_settings_section(
        'main_settings',
        'Main Settings',
        null,
        'news-fetcher-settings'
    );

    add_settings_field(
        'api_endpoint',
        'API Endpoint',
        'api_endpoint_callback',
        'news-fetcher-settings',
        'main_settings'
    );

    add_settings_field(
        'pagination_limit',
        'Pagination Limit',
        'pagination_limit_callback',
        'news-fetcher-settings',
        'main_settings'
    );
}
add_action('admin_init', 'register_settings');

function api_endpoint_callback() {
    $api_endpoint = esc_attr(get_option('api_endpoint', 'http://api.mediastack.com/v1/news?access_key=09624a4db091e1a9b64d4caa908a57ac&keywords=technology&science=us'));
    echo '<input type="text" name="api_endpoint" value="' . $api_endpoint . '" size="50" />';
}

function pagination_limit_callback() {
    $pagination_limit = esc_attr(get_option('pagination_limit', '10'));
    echo '<input type="number" name="pagination_limit" value="' . $pagination_limit . '" min="1" />';
}

// Fetches articles from the API without storing them in the database
function fetch_articles() {
    $api_endpoint = get_option('api_endpoint', 'http://api.mediastack.com/v1/news?access_key=09624a4db091e1a9b64d4caa908a57ac&keywords=technology&science=us');
    $response = wp_remote_get($api_endpoint);
    $body = wp_remote_retrieve_body($response);
    
    $data = json_decode($body, true);

    if (!empty($data['data'])) { // Adjust according to API's returned structure (here 'data' assumed as response structure).
        return $data['data']; // Assuming 'data' contains the articles.
    } else {
        return false;
    }
}

// Shortcode to display articles without saving them in the database
function display_articles_shortcode($atts) {
    $atts = shortcode_atts(array(
        'posts_per_page' => get_option('pagination_limit', 10),
        'paged' => get_query_var('paged') ? get_query_var('paged') : 1
    ), $atts, 'display_news_articles');

    $articles = fetch_articles();

    if ($articles) {
        ob_start();
        
        echo '<div class="news-articles">';
        $articles = array_slice($articles, ($atts['paged'] - 1) * $atts['posts_per_page'], $atts['posts_per_page']);
        foreach ($articles as $article) {
            echo '<div class="news-article">';
            echo '<h2>' . esc_html($article['title']) . '</h2>';
            echo '<p>' . esc_html($article['description']) . '</p>';
            echo '<p><strong>Author:</strong> ' . esc_html($article['author']) . '</p>';
            echo '<p><strong>Source:</strong> ' . esc_html($article['source']) . '</p>';
            echo '<p><strong>Country:</strong> ' . esc_html($article['country']) . '</p>';
            echo '<p><strong>Published At:</strong> ' . esc_html($article['published_at']) . '</p>';
            echo '<a href="' . esc_url($article['url']) . '" target="_blank">Read more</a>';
            echo '</div>';
        }
        echo '</div>';

        // Paginate links (simple manual pagination as articles are not stored in DB)
        $total_pages = ceil(count(fetch_articles()) / $atts['posts_per_page']);
        echo paginate_links(array(
            'base' => str_replace(999999999, '%#%', esc_url(get_pagenum_link(999999999))),
            'format' => '?paged=%#%',
            'current' => max(1, $atts['paged']),
            'total' => $total_pages
        ));

        return ob_get_clean();
    } else {
        return 'No news articles found.';
    }
}
add_shortcode('display_news_articles', 'display_articles_shortcode');
?>
