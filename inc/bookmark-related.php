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
//  1. MutationObserver waits for React to render .press-this-editor__sidebar-content,
//     then appends two PanelBody-style sections (matching the Categories panel style)
//     with hierarchical checkboxes inside .components-panel.
//  2. window.fetch is monkey-patched: after a successful press-this/v1/save, a follow-up
//     REST call to /wp/v2/updates/<postId> assigns the checked taxonomy terms.
add_action( 'admin_footer-press-this.php', function() {
    // Pass full term data (id, name, parent) as JSON so JS can build the hierarchy.
    $theme_terms      = get_terms( array( 'taxonomy' => 'theme',      'hide_empty' => false ) );
    $discipline_terms = get_terms( array( 'taxonomy' => 'discipline', 'hide_empty' => false ) );

    $theme_data = array();
    foreach ( (array) $theme_terms as $t ) {
        $theme_data[] = array( 'id' => (int) $t->term_id, 'name' => $t->name, 'parent' => (int) $t->parent );
    }
    $discipline_data = array();
    foreach ( (array) $discipline_terms as $t ) {
        $discipline_data[] = array( 'id' => (int) $t->term_id, 'name' => $t->name, 'parent' => (int) $t->parent );
    }

    // SVG chevron used by WordPress PanelBody toggle arrow.
    $arrow_svg = '<svg class="components-panel__arrow" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path d="M17.5 11.6L12 16l-5.5-4.4.9-1.2L12 14.1l4.5-3.6 1 1.1z"/></svg>';
    ?>
    <script type="text/javascript">
    (function() {
        var themeTerms      = <?php echo wp_json_encode( $theme_data ); ?>;
        var disciplineTerms = <?php echo wp_json_encode( $discipline_data ); ?>;
        var arrowSVG        = <?php echo wp_json_encode( $arrow_svg ); ?>;

        // -----------------------------------------------------------------------
        // Build a hierarchical list of checkboxes matching the Categories panel.
        // Returns an HTML string of <label class="press-this-editor__category">…
        // -----------------------------------------------------------------------
        function buildCheckboxes(terms, containerId) {
            // Sort top-level first, then children, preserving name order within each level.
            var html = '';

            function renderChildren(parentId, depth) {
                terms
                    .filter(function(t) { return t.parent === parentId; })
                    .forEach(function(t) {
                        var indent = depth * 16; // 16px per level, matching WP admin
                        html +=
                            '<label class="press-this-editor__category" style="padding-left:' + indent + 'px;">' +
                            '<input type="checkbox" data-container="' + containerId + '" value="' + t.id + '">' +
                            t.name +
                            '</label>';
                        renderChildren(t.id, depth + 1);
                    });
            }
            renderChildren(0, 0);
            return html;
        }

        // -----------------------------------------------------------------------
        // Build a full PanelBody-style section matching the existing sidebar panels.
        // -----------------------------------------------------------------------
        function buildPanel(panelId, title, terms) {
            var checkboxes = buildCheckboxes(terms, panelId);
            return (
                '<div id="' + panelId + '" class="components-panel__body">' +
                    '<h2 class="components-panel__body-title">' +
                        '<button type="button" class="components-panel__body-toggle components-button" aria-expanded="false">' +
                            title + arrowSVG +
                        '</button>' +
                    '</h2>' +
                    '<div class="press-this-editor__categories">' +
                        checkboxes +
                    '</div>' +
                '</div>'
            );
        }

        // -----------------------------------------------------------------------
        // Toggle open/close when the panel button is clicked.
        // The content div is hidden via CSS when is-opened is absent.
        // -----------------------------------------------------------------------
        document.addEventListener('click', function(e) {
            var btn = e.target.closest && e.target.closest('.components-panel__body-toggle');
            if (!btn) return;
            var panel = btn.closest('.components-panel__body');
            if (!panel || (!panel.id || panel.id.indexOf('pt-') !== 0)) return;
            var open = panel.classList.toggle('is-opened');
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        });

        // -----------------------------------------------------------------------
        // MutationObserver: inject panels once React renders the sidebar.
        // Target is .components-panel inside .press-this-editor__sidebar-content.
        // -----------------------------------------------------------------------
        var appEl = document.getElementById('press-this-app');
        if (!appEl) return;

        var observer = new MutationObserver(function() {
            var sidebarContent = document.querySelector('.press-this-editor__sidebar-content');
            if (!sidebarContent) return;
            var panel = sidebarContent.querySelector('.components-panel');
            if (!panel) return;
            if (document.getElementById('pt-theme-panel')) return; // already injected

            observer.disconnect();

            var wrapper = document.createElement('div');
            wrapper.innerHTML =
                buildPanel('pt-theme-panel',      '<?php echo esc_js( __( 'Themes', 'understrap' ) ); ?>', themeTerms) +
                buildPanel('pt-discipline-panel', '<?php echo esc_js( __( 'Disciplines', 'understrap' ) ); ?>', disciplineTerms);

            while (wrapper.firstChild) {
                panel.appendChild(wrapper.firstChild);
            }
        });
        observer.observe(appEl, { childList: true, subtree: true });

        // -----------------------------------------------------------------------
        // Monkey-patch fetch: after a successful press-this/v1/save, assign the
        // checked taxonomy terms via the standard WP REST API (/wp/v2/updates/).
        // -----------------------------------------------------------------------
        var origFetch = window.fetch;
        window.fetch = function(url, options) {
            var fetchPromise = origFetch.apply(this, arguments);

            if (typeof url === 'string' && url.indexOf('press-this/v1/save') !== -1) {
                // Clone the response so the original .json() chain is not consumed.
                fetchPromise.then(function(response) {
                    if (!response.ok) return;

                    var ptData  = window.pressThisData || {};
                    var postId  = ptData.postId;
                    var nonce   = ptData.restNonce;
                    var restUrl = ptData.restUrl; // e.g. https://site/wp-json/press-this/v1/
                    if (!postId || !nonce || !restUrl) return;

                    function getCheckedIds(panelId) {
                        var boxes = document.querySelectorAll(
                            '#' + panelId + ' input[type="checkbox"]:checked'
                        );
                        return Array.prototype.map.call(boxes, function(b) {
                            return parseInt(b.value, 10);
                        });
                    }

                    var themeIds      = getCheckedIds('pt-theme-panel');
                    var disciplineIds = getCheckedIds('pt-discipline-panel');
                    if (!themeIds.length && !disciplineIds.length) return;

                    var wpRoot  = restUrl.replace(/press-this\/v1\/?$/, '');
                    var taxData = {};
                    if (themeIds.length)      taxData['theme']      = themeIds;
                    if (disciplineIds.length) taxData['discipline'] = disciplineIds;

                    origFetch(wpRoot + 'wp/v2/updates/' + postId, {
                        method:  'POST',
                        headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce },
                        body:    JSON.stringify(taxData)
                    });
                });
            }

            return fetchPromise;
        };
    })();
    </script>
    <style>
    /* Hide panel content until is-opened; the arrow rotates when open. */
    #pt-theme-panel .press-this-editor__categories,
    #pt-discipline-panel .press-this-editor__categories {
        display: none;
    }
    #pt-theme-panel.is-opened .press-this-editor__categories,
    #pt-discipline-panel.is-opened .press-this-editor__categories {
        display: block;
    }
    #pt-theme-panel.is-opened .components-panel__arrow,
    #pt-discipline-panel.is-opened .components-panel__arrow {
        transform: translateY(-50%) rotate(180deg);
    }
    </style>
    <?php
} );