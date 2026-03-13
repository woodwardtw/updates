<?php
/**
 * Update bookmark related functions
 *
 * @package Understrap
 */

// -----------------------------------------------------------------------
// media_sideload_image() lives in admin-only includes that WordPress does
// not load during REST API requests.  The Press This plugin calls it without
// requiring those files, causing a fatal.  Load them here when the REST API
// initialises so they're available by the time the callback runs.
// -----------------------------------------------------------------------
add_action( 'rest_api_init', function() {
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';
} );

// -----------------------------------------------------------------------
// Strip well-known tracking/analytics parameters from a URL.
//
// Handles two common encoding issues:
//  1. HTML-entity separators: URLs extracted from HTML attributes often have
//     &amp; instead of & between parameters.  html_entity_decode() fixes this
//     before parse_str() sees the query string.
//  2. amp; key prefix: when &amp; wasn't decoded before parse_str(), each key
//     ends up with a literal "amp;" prefix (e.g. "amp;utm_medium").  After
//     html_entity_decode() this is also resolved, but we strip any residual
//     "amp;" prefix as a fallback.
//
// All utm_* parameters are removed by pattern; other tracker IDs are listed
// explicitly.  Filterable via 'update_tracking_params' for the explicit list.
// -----------------------------------------------------------------------
function update_strip_tracking_params( $url ) {

    // Explicit non-UTM tracking params to remove.
    $explicit_params = apply_filters( 'update_tracking_params', array(
        // Google Ads
        'gclid', 'gclsrc', 'dclid', 'gbraid', 'wbraid',
        'gad_source', 'gad_campaignid',
        // Facebook / Meta
        'fbclid',
        // Microsoft / Bing Ads
        'msclkid',
        // Mailchimp
        'mc_cid', 'mc_eid',
        // Twitter / X
        'twclid',
        // Instagram
        'igshid',
        // HubSpot
        '_hsenc', '_hsmi',
        // Marketo
        'mkt_tok',
        // Branch.io deep-link / click tracking (also covers %24deep_link, %243p)
        '$deep_link', '$3p', '_branch_match_id', '_branch_referrer',
        '_branch_match_id_from_pasteboard',
        // Generic referral tracking (used by Reddit email digest, newsletters, etc.)
        'ref_campaign', 'ref_source',
        // Generic session/correlation IDs
        'correlation_id',
    ) );

    $parsed = wp_parse_url( $url );
    if ( empty( $parsed['query'] ) ) {
        return $url;
    }

    // Decode HTML entities so &amp; separators become plain & before parse_str.
    $query = html_entity_decode( $parsed['query'], ENT_QUOTES | ENT_HTML5, 'UTF-8' );

    parse_str( $query, $raw_params );

    $clean_params = array();
    foreach ( $raw_params as $key => $value ) {
        // Strip residual "amp;" prefix (e.g. "amp;utm_medium" → "utm_medium").
        // This occurs when %3B-encoded semicolons from &amp; survive decoding.
        $key = preg_replace( '/^amp;/', '', $key );

        // Remove all utm_* parameters (covers utm_source, utm_medium,
        // utm_campaign, utm_ads_campaign_id, and any future variants).
        if ( preg_match( '/^utm_/i', $key ) ) {
            continue;
        }

        // Remove explicitly listed tracker IDs.
        if ( in_array( $key, $explicit_params, true ) ) {
            continue;
        }

        $clean_params[ $key ] = $value;
    }

    $clean_query = http_build_query( $clean_params );

    $clean_url = $parsed['scheme'] . '://' . $parsed['host'];
    if ( ! empty( $parsed['port'] ) ) {
        $clean_url .= ':' . $parsed['port'];
    }
    $clean_url .= ( $parsed['path'] ?? '' );
    if ( $clean_query !== '' ) {
        $clean_url .= '?' . $clean_query;
    }
    if ( ! empty( $parsed['fragment'] ) ) {
        $clean_url .= '#' . $parsed['fragment'];
    }

    return $clean_url;
}

// -----------------------------------------------------------------------
// Change post type to 'update' on save, extract source URL into ACF field,
// and apply any taxonomy terms pre-stored by the JS before the save fired.
// -----------------------------------------------------------------------
add_filter( 'press_this_save_post', function( $data ) {

    $pattern = '/Source: <em><a href="([^"]+)"/';//regex to get source URL

    if ( preg_match( $pattern, $data['post_content'], $matches ) ) {
        $url = update_strip_tracking_params( $matches[1] );
        update_field( 'source_url', $url, $data['ID'] );
    }

    $new_cpt     = 'update';
    $post_object = get_post_type_object( $new_cpt );

    if (
        isset( $post_object->cap->create_posts )
        && current_user_can( $post_object->cap->create_posts )
    ) {
        $data['post_type'] = $new_cpt;
    }

    // Apply taxonomy terms that were stored in post meta by the JS
    // pre-save AJAX call (pt_store_taxonomy action below).
    $post_id = isset( $data['ID'] ) ? (int) $data['ID'] : 0;
    if ( $post_id ) {
        $stored = get_post_meta( $post_id, '_pt_pending_tax', true );
        if ( $stored ) {
            delete_post_meta( $post_id, '_pt_pending_tax' );
            add_action( 'save_post', function( $saved_id ) use ( $post_id, $stored ) {
                if ( $saved_id !== $post_id ) return;
                if ( ! empty( $stored['theme'] ) ) {
                    wp_set_object_terms( $saved_id, $stored['theme'], 'theme' );
                }
                if ( ! empty( $stored['discipline'] ) ) {
                    wp_set_object_terms( $saved_id, $stored['discipline'], 'discipline' );
                }
                if ( ! empty( $stored['software'] ) ) {
                    wp_set_object_terms( $saved_id, $stored['software'], 'software' );
                }
            } );
        }
    }

    return $data;

}, 999 );


// -----------------------------------------------------------------------
// AJAX handler: stores selected taxonomy term IDs in post meta so the
// press_this_save_post filter above can apply them during the REST save.
// Called from JS before the press-this/v1/save request fires.
// -----------------------------------------------------------------------
add_action( 'wp_ajax_pt_store_taxonomy', function() {
    check_ajax_referer( 'pt_store_taxonomy', 'nonce' );

    $post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
    if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
        wp_send_json_error( 'unauthorized' );
    }

    $theme_ids      = array_filter( array_map( 'absint', (array) ( $_POST['theme']      ?? array() ) ) );
    $discipline_ids = array_filter( array_map( 'absint', (array) ( $_POST['discipline'] ?? array() ) ) );
    $software_ids   = array_filter( array_map( 'absint', (array) ( $_POST['software']   ?? array() ) ) );
    update_post_meta( $post_id, '_pt_pending_tax', array(
        'theme'      => $theme_ids,
        'discipline' => $discipline_ids,
        'software'   => $software_ids,
    ) );

    wp_send_json_success();
} );


// -----------------------------------------------------------------------
// Overwrite the post permalink for 'update' posts with the bookmarked URL.
// -----------------------------------------------------------------------
function update_custom_bookmark_permalink( $post_link, $post ) {
    if ( 'update' === get_post_type( $post ) ) {
        $custom_url = get_field( 'source_url', $post->ID );
        if ( ! empty( $custom_url ) ) {
            return esc_url( $custom_url );
        }
    }
    return $post_link;
}
add_filter( 'post_type_link', 'update_custom_bookmark_permalink', 10, 2 );


// -----------------------------------------------------------------------
// Press This sidebar: inject Themes and Disciplines panels.
//
// Strategy:
//  1. MutationObserver waits for React to render .press-this-editor__sidebar-content,
//     then appends two PanelBody-style sections (matching the Categories panel style)
//     with hierarchical checkboxes inside .components-panel.
//  2. window.fetch is monkey-patched to intercept press-this/v1/save:
//     a. BEFORE firing the save, an AJAX call stores the selected term IDs in a
//        transient.  The press_this_save_post filter above reads that transient
//        and includes the terms in the wp_update_post() call — all server-side,
//        so window.close() cannot race against any network request.
//     b. After the save response arrives, if redirect is set (= publish), we
//        call window.close() and return a modified response with redirect:false
//        so React doesn't also navigate the (already closing) window.
// -----------------------------------------------------------------------
add_action( 'admin_footer-press-this.php', function() {

    $theme_terms      = get_terms( array( 'taxonomy' => 'theme',      'hide_empty' => false ) );
    $discipline_terms = get_terms( array( 'taxonomy' => 'discipline', 'hide_empty' => false ) );
    $software_terms = get_terms( array( 'taxonomy' => 'software', 'hide_empty' => false ) );

    $theme_data = array();
    foreach ( (array) $theme_terms as $t ) {
        $theme_data[] = array( 'id' => (int) $t->term_id, 'name' => $t->name, 'parent' => (int) $t->parent );
    }
    $discipline_data = array();
    foreach ( (array) $discipline_terms as $t ) {
        $discipline_data[] = array( 'id' => (int) $t->term_id, 'name' => $t->name, 'parent' => (int) $t->parent );
    }
    $software_data = array();
    foreach ( (array) $software_terms as $t ) {
        $software_data[] = array( 'id' => (int) $t->term_id, 'name' => $t->name, 'parent' => (int) $t->parent );
    }

    $arrow_svg = '<svg class="components-panel__arrow" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path d="M17.5 11.6L12 16l-5.5-4.4.9-1.2L12 14.1l4.5-3.6 1 1.1z"/></svg>';
    ?>
    <script type="text/javascript">
    (function() {
        var themeTerms      = <?php echo wp_json_encode( $theme_data ); ?>;
        var disciplineTerms = <?php echo wp_json_encode( $discipline_data ); ?>;
        var softwareTerms   = <?php echo wp_json_encode( $software_data ); ?>;
        var arrowSVG        = <?php echo wp_json_encode( $arrow_svg ); ?>;
        var ajaxUrl         = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
        var taxNonce        = <?php echo wp_json_encode( wp_create_nonce( 'pt_store_taxonomy' ) ); ?>;

        // -----------------------------------------------------------------------
        // Build hierarchical checkboxes matching the Categories panel style.
        // -----------------------------------------------------------------------
        function buildCheckboxes( terms ) {
            var html = '';
            function renderChildren( parentId, depth ) {
                terms
                    .filter( function( t ) { return t.parent === parentId; } )
                    .forEach( function( t ) {
                        var indent = depth * 16;
                        html +=
                            '<label class="press-this-editor__category" style="padding-left:' + indent + 'px;">' +
                            '<input type="checkbox" value="' + t.id + '">' +
                            t.name +
                            '</label>';
                        renderChildren( t.id, depth + 1 );
                    } );
            }
            renderChildren( 0, 0 );
            return html;
        }

        function buildPanel( panelId, title, terms ) {
            return (
                '<div id="' + panelId + '" class="components-panel__body">' +
                    '<h2 class="components-panel__body-title">' +
                        '<button type="button" class="components-panel__body-toggle components-button" aria-expanded="false">' +
                            title + arrowSVG +
                        '</button>' +
                    '</h2>' +
                    '<div class="press-this-editor__categories">' +
                        buildCheckboxes( terms ) +
                    '</div>' +
                '</div>'
            );
        }

        // Toggle panel open/close (scoped to our injected panels via id prefix).
        document.addEventListener( 'click', function( e ) {
            var btn = e.target.closest && e.target.closest( '.components-panel__body-toggle' );
            if ( ! btn ) return;
            var panel = btn.closest( '.components-panel__body' );
            if ( ! panel || ! panel.id || panel.id.indexOf( 'pt-' ) !== 0 ) return;
            var open = panel.classList.toggle( 'is-opened' );
            btn.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
        } );

        // -----------------------------------------------------------------------
        // MutationObserver: inject panels once React renders the sidebar.
        // -----------------------------------------------------------------------
        var appEl = document.getElementById( 'press-this-app' );
        if ( ! appEl ) return;

        var observer = new MutationObserver( function() {
            var sidebarContent = document.querySelector( '.press-this-editor__sidebar-content' );
            if ( ! sidebarContent ) return;
            var componentsPanel = sidebarContent.querySelector( '.components-panel' );
            if ( ! componentsPanel ) return;
            if ( document.getElementById( 'pt-theme-panel' ) ) return; // already injected

            observer.disconnect();

            var wrapper = document.createElement( 'div' );
            wrapper.innerHTML =
                buildPanel( 'pt-theme-panel',      '<?php echo esc_js( __( 'Themes',      'understrap' ) ); ?>', themeTerms ) +
                buildPanel( 'pt-discipline-panel', '<?php echo esc_js( __( 'Disciplines', 'understrap' ) ); ?>', disciplineTerms ) +
                buildPanel( 'pt-software-panel',   '<?php echo esc_js( __( 'Software',    'understrap' ) ); ?>', softwareTerms );
            var refNode = componentsPanel.firstChild;
            while ( wrapper.firstChild ) {
                componentsPanel.insertBefore( wrapper.firstChild, refNode );
            }
        } );
        observer.observe( appEl, { childList: true, subtree: true } );

        // -----------------------------------------------------------------------
        // Helpers
        // -----------------------------------------------------------------------
        function getCheckedIds( panelId ) {
            var boxes = document.querySelectorAll( '#' + panelId + ' input[type="checkbox"]:checked' );
            return Array.prototype.map.call( boxes, function( b ) { return parseInt( b.value, 10 ); } );
        }

        // Store selected term IDs server-side via AJAX so the press_this_save_post
        // PHP filter can apply them during the save. Returns a Promise.
        function storeTaxonomyTerms( postId, themeIds, disciplineIds, softwareIds ) {
            var formData = new FormData();
            formData.append( 'action',  'pt_store_taxonomy' );
            formData.append( 'nonce',   taxNonce );
            formData.append( 'post_id', postId );
            themeIds.forEach(      function( id ) { formData.append( 'theme[]',      id ); } );
            disciplineIds.forEach( function( id ) { formData.append( 'discipline[]', id ); } );
            softwareIds.forEach(   function( id ) { formData.append( 'software[]',   id ); } );
            return origFetch( ajaxUrl, { method: 'POST', body: formData } )
                .catch( function() {} ); // never block the save on AJAX failure
        }

        // -----------------------------------------------------------------------
        // Monkey-patch fetch to intercept press-this/v1/save.
        //
        // Flow for Publish:
        //   1. Collect checked term IDs.
        //   2. POST them to the AJAX handler (stored in a transient server-side).
        //   3. Fire the original press-this/v1/save — the PHP filter reads the
        //      transient and saves the terms inside wp_update_post(). Done.
        //   4. Read the save response; if redirect is set, call window.close()
        //      and return a redirect-free response so React doesn't also navigate.
        //
        // Flow for Save Draft: same steps 1-3, skip step 4 (no redirect).
        // -----------------------------------------------------------------------
        var origFetch = window.fetch;
        window.fetch = function( url, options ) {

            if ( typeof url === 'string' && url.indexOf( 'press-this/v1/save' ) !== -1 ) {

                var themeIds      = getCheckedIds( 'pt-theme-panel' );
                var disciplineIds = getCheckedIds( 'pt-discipline-panel' );
                var softwareIds   = getCheckedIds( 'pt-software-panel' );
                var ptData        = window.pressThisData || {};
                var postId        = ptData.postId;

                // Store terms server-side first, then fire the press-this save.
                var preSave = ( ( themeIds.length || disciplineIds.length || softwareIds.length ) && postId )
                    ? storeTaxonomyTerms( postId, themeIds, disciplineIds, softwareIds )
                    : Promise.resolve();

                return preSave
                    .then( function() {
                        return origFetch( url, options );
                    } )
                    .then( function( response ) {
                        if ( ! response.ok ) return response;

                        return response.clone().json().then(
                            function( data ) {
                                if ( data && data.redirect ) {
                                    // Published — close the popup.
                                    // Return redirect-free response so React doesn't navigate.
                                    window.close();
                                    return new Response(
                                        JSON.stringify( Object.assign( {}, data, { redirect: false } ) ),
                                        { status: 200, headers: { 'Content-Type': 'application/json' } }
                                    );
                                }
                                return response; // draft save — pass through unchanged
                            },
                            function() { return response; } // JSON parse error — pass through
                        );
                    } );
            }

            return origFetch.apply( this, arguments );
        };
    })();
    </script>
    <style>
    /* Hide panel content until is-opened; rotate arrow when open. */
    #pt-theme-panel .press-this-editor__categories,
    #pt-discipline-panel .press-this-editor__categories,
    #pt-software-panel .press-this-editor__categories {
        display: none;
    }
    #pt-theme-panel.is-opened .press-this-editor__categories,
    #pt-discipline-panel.is-opened .press-this-editor__categories,
    #pt-software-panel.is-opened .press-this-editor__categories {
        display: block;
    }
    #pt-theme-panel.is-opened .components-panel__arrow,
    #pt-discipline-panel.is-opened .components-panel__arrow,
    #pt-software-panel.is-opened .components-panel__arrow {
        transform: translateY(-50%) rotate(180deg);
    }
    </style>
    <?php
} );
