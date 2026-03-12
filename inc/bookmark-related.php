<?php
/**
 * Update bookmark related functions
 *
 * @package Understrap
 */

add_filter( 'press_this_save_post', function( $data )
{
  
  $pattern = '/Source: <em><a href="([^"]+)"/';//regex to get source URL

   // Using preg_match to extract the URL
   if (preg_match($pattern, $data['post_content'], $matches)) {
       $url = $matches[1]; // The URL will be captured in the first capturing group
       update_field( 'source_url', $url, $data['ID'] );//write source URL to custom field
   } else {
       //write_log("No URL found. Pattern: " . $pattern . " Content: " . $data['post_content']);
   }

    //---------------------------------------------------------------
    //
    $new_cpt    = 'update';              // new post type   
    //---------------------------------------------------------------

    $post_object = get_post_type_object( $new_cpt );

    // Change the post type if current user can
    if( 
           isset( $post_object->cap->create_posts ) 
        && current_user_can( $post_object->cap->create_posts ) 
    ) 
        $data['post_type']  = $new_cpt;


    return $data;

}, 999 );


//overwrite normal post link with the URL that was bookmarked

function update_custom_bookmark_permalink($post_link, $post) {
    // Check if the post type is 'bookmark'
    if ('update' === get_post_type($post)) {
        // Retrieve the ACF field 'source_url'
        $custom_url = get_field('source_url', $post->ID);

        // If the custom field is not empty, replace the post link with the custom URL
        if (!empty($custom_url)) {
            return esc_url($custom_url);
        }
    }

    // If the conditions are not met, return the original post link
    return $post_link;
}
add_filter('post_type_link', 'update_custom_bookmark_permalink', 10, 2);


// Display taxonomy controls in the Press This v2 (React/Gutenberg) sidebar.
// The 'theme' and 'discipline' taxonomies are registered in inc/custom-data.php.
// Strategy:
//  1. MutationObserver waits for the React sidebar (.press-this-editor__sidebar-content) to render.
//  2. We inject <select> elements for Theme and Discipline into the sidebar.
//  3. We monkey-patch window.fetch to intercept the press-this/v1/save REST call and,
//     after a successful save, make a follow-up REST call to /wp/v2/updates/<postId>
//     to assign the selected taxonomy terms (both CPT and taxonomies have show_in_rest=true).
add_action( 'admin_footer-press-this.php', function() {
    $theme_terms      = get_terms( array( 'taxonomy' => 'theme',      'hide_empty' => false ) );
    $discipline_terms = get_terms( array( 'taxonomy' => 'discipline', 'hide_empty' => false ) );

    // Build option HTML strings in PHP to avoid inline JS string-building loops.
    $theme_options = '';
    foreach ( (array) $theme_terms as $term ) {
        $theme_options .= '<option value="' . esc_attr( $term->term_id ) . '">' . esc_html( $term->name ) . '</option>';
    }
    $discipline_options = '';
    foreach ( (array) $discipline_terms as $term ) {
        $discipline_options .= '<option value="' . esc_attr( $term->term_id ) . '">' . esc_html( $term->name ) . '</option>';
    }
    ?>
    <script type="text/javascript">
    (function() {
        // -----------------------------------------------------------------------
        // 1. Build the HTML for the taxonomy selects (options injected from PHP).
        // -----------------------------------------------------------------------
        var themeOptionsHTML      = <?php echo wp_json_encode( $theme_options ); ?>;
        var disciplineOptionsHTML = <?php echo wp_json_encode( $discipline_options ); ?>;

        var panelHTML =
            '<div id="pt-custom-taxonomies" style="padding:0 16px 16px;border-top:1px solid #ddd;margin-top:8px;">' +
                '<p style="margin:12px 0 4px;font-weight:600;font-size:13px;"><?php echo esc_js( __( 'Theme', 'understrap' ) ); ?></p>' +
                '<select id="pt-theme-select" style="width:100%;margin-bottom:12px;">' +
                    '<option value="">&mdash; <?php echo esc_js( __( 'Select', 'understrap' ) ); ?> &mdash;</option>' +
                    themeOptionsHTML +
                '</select>' +
                '<p style="margin:0 0 4px;font-weight:600;font-size:13px;"><?php echo esc_js( __( 'Discipline', 'understrap' ) ); ?></p>' +
                '<select id="pt-discipline-select" style="width:100%;">' +
                    '<option value="">&mdash; <?php echo esc_js( __( 'Select', 'understrap' ) ); ?> &mdash;</option>' +
                    disciplineOptionsHTML +
                '</select>' +
            '</div>';

        // -----------------------------------------------------------------------
        // 2. MutationObserver: inject selects once React renders the sidebar.
        // -----------------------------------------------------------------------
        var appEl = document.getElementById('press-this-app');
        if (!appEl) return;

        var observer = new MutationObserver(function() {
            var sidebar = document.querySelector('.press-this-editor__sidebar-content');
            if (sidebar && !document.getElementById('pt-custom-taxonomies')) {
                var node = document.createElement('div');
                node.innerHTML = panelHTML;
                sidebar.appendChild(node.firstElementChild);
            }
        });
        observer.observe(appEl, { childList: true, subtree: true });

        // -----------------------------------------------------------------------
        // 3. Monkey-patch fetch: after a successful press-this/v1/save, assign
        //    the selected taxonomy terms via the standard WP REST API.
        // -----------------------------------------------------------------------
        var origFetch = window.fetch;
        window.fetch = function(url, options) {
            var fetchPromise = origFetch.apply(this, arguments);

            if (typeof url === 'string' && url.indexOf('press-this/v1/save') !== -1) {
                fetchPromise.then(function(response) {
                    if (!response.ok) return;

                    var ptData    = window.pressThisData || {};
                    var postId    = ptData.postId;
                    var nonce     = ptData.restNonce;
                    var restUrl   = ptData.restUrl; // e.g. https://site/wp-json/press-this/v1/

                    if (!postId || !nonce || !restUrl) return;

                    var themeEl      = document.getElementById('pt-theme-select');
                    var disciplineEl = document.getElementById('pt-discipline-select');
                    var themeId      = themeEl      ? parseInt(themeEl.value,      10) : 0;
                    var disciplineId = disciplineEl ? parseInt(disciplineEl.value, 10) : 0;

                    if (!themeId && !disciplineId) return;

                    // Derive WP REST root from the press-this REST URL.
                    var wpRoot = restUrl.replace(/press-this\/v1\/?$/, '');
                    var taxData = {};
                    if (themeId)      taxData['theme']      = [themeId];
                    if (disciplineId) taxData['discipline'] = [disciplineId];

                    origFetch(wpRoot + 'wp/v2/updates/' + postId, {
                        method:  'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-WP-Nonce':   nonce
                        },
                        body: JSON.stringify(taxData)
                    });
                });
            }

            return fetchPromise;
        };
    })();
    </script>
    <?php
} );