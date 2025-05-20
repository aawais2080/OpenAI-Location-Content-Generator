<?php
/**
 * Plugin Name: OpenAI Location Generator
 * Description: Select country, state, city and generate OpenAI content based on the prompt.
 * Version: 1.2
 * Author: Awais Arshad
 */

if (!defined('ABSPATH')) exit;

// Enqueue scripts and styles
add_action('admin_enqueue_scripts', function($hook) {
    if ($hook !== 'settings_page_openai_location_generator') {
        return;
    }
    wp_enqueue_style('openai-location-style', plugin_dir_url(__FILE__) . 'assets/css/style.css');
    wp_enqueue_script('openai-location-script', plugin_dir_url(__FILE__) . 'assets/js/script.js', ['jquery'], false, true);

    wp_localize_script('openai-location-script', 'openaiLocationAjax', [
        'ajax_url' => admin_url('admin-ajax.php'),
    ]);
});

// Frontend
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_style('openai-location-style', plugin_dir_url(__FILE__) . 'assets/css/style.css');
    wp_enqueue_script('openai-location-script', plugin_dir_url(__FILE__) . 'assets/js/script.js', ['jquery'], false, true);

    wp_localize_script('openai-location-script', 'openaiLocationAjax', [
        'ajax_url' => admin_url('admin-ajax.php'),
    ]);
});


// Register Industry Custom Post Type
add_action('init', function() {
    $labels = array(
        'name'                => __( 'Industries'),
        'singular_name'       => __( 'Industry'),
        'menu_name'           => __( 'Industries'),
        'parent_item_colon'   => __( 'Parent Industry'),
        'all_items'           => __( 'All Industries'),
        'view_item'           => __( 'View Industry'),
        'add_new_item'        => __( 'Add New Industry'),
        'add_new'             => __( 'Add New'),
        'edit_item'           => __( 'Edit Industry'),
        'update_item'         => __( 'Update Industry'),
        'search_items'        => __( 'Search Industry'),
        'not_found'           => __( 'Not Found'),
        'not_found_in_trash'  => __( 'Not found in Trash'),
    );
    
    register_post_type('industry', [
        'labels' => $labels,
        'supports' => ['title', 'editor', 'thumbnail'],
        'menu_icon' => 'dashicons-building',
        'hierarchical'        => false,
        'public'              => true,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'show_in_nav_menus'   => true,
        'show_in_admin_bar'   => true,
        'menu_position'       => 5,
        'can_export'          => true,
        'has_archive'         => true,
        'exclude_from_search' => false,
        'publicly_queryable'  => true,
        'capability_type'     => 'post',
        'show_in_rest' => true,
    ]);
});

add_action( 'init', 'create_locations_hierarchical_taxonomy', 0 );
function create_locations_hierarchical_taxonomy() {
  $labels = array(
    'name' => _x( 'Locations', 'taxonomy general name' ),
    'singular_name' => _x( 'Location', 'taxonomy singular name' ),
    'search_items' =>  __( 'Search Locations' ),
    'all_items' => __( 'All Locations' ),
    'parent_item' => __( 'Parent Location' ),
    'parent_item_colon' => __( 'Parent Location:' ),
    'edit_item' => __( 'Edit Location' ), 
    'update_item' => __( 'Update Location' ),
    'add_new_item' => __( 'Add New Location' ),
    'new_item_name' => __( 'New Location Name' ),
    'menu_name' => __( 'Locations' ),
  );    
  
// Now register the taxonomy
  register_taxonomy('locations',array('industry'), array(
    'hierarchical' => true,
    'labels' => $labels,
    'show_ui' => true,
    'show_in_rest' => true,
    'show_admin_column' => true,
    'query_var' => true,
    'rewrite' => array( 'slug' => 'location' ),
  ));
}

add_action( 'init', 'create_services_hierarchical_taxonomy', 0 );
function create_services_hierarchical_taxonomy() {
  $labels = array(
    'name' => _x( 'Services', 'taxonomy general name' ),
    'singular_name' => _x( 'Service', 'taxonomy singular name' ),
    'search_items' =>  __( 'Search Services' ),
    'all_items' => __( 'All Services' ),
    'parent_item' => __( 'Parent Service' ),
    'parent_item_colon' => __( 'Parent Service:' ),
    'edit_item' => __( 'Edit Service' ), 
    'update_item' => __( 'Update Service' ),
    'add_new_item' => __( 'Add New Service' ),
    'new_item_name' => __( 'New Service Name' ),
    'menu_name' => __( 'Services' ),
  );    
  
// Now register the taxonomy
  register_taxonomy('services',array('industry'), array(
    'hierarchical' => true,
    'labels' => $labels,
    'show_ui' => true,
    'show_in_rest' => true,
    'show_admin_column' => true,
    'query_var' => true,
    'rewrite' => array( 'slug' => 'service' ),
  ));
}

/*Custom Form in Industry single page start*/
function custom_contact_form_shortcode() {
    $city = $state = $country = '';

    // Example: get term from current post (if assigned)
    if ( is_singular('industry') ) {
        $terms = get_the_terms( get_the_ID(), 'locations' );
        if ( $terms && ! is_wp_error( $terms ) ) {
            $term = $terms[0]; // Get the first assigned term
            if ( $term ) {
                if ( $term->parent ) {
                    $parent1 = get_term( $term->parent, 'locations' );
                    if ( $parent1 && $parent1->parent ) {
                        $parent2 = get_term( $parent1->parent, 'locations' );
                        if ( $parent2 ) {
                            $city = $term->name;
                            $state = $parent1->name;
                            $country = $parent2->name;
                        }
                    } else {
                        $state = $term->name;
                        $country = $parent1->name;
                    }
                } else {
                    $country = $term->name;
                }
            }
        }
    
        ob_start(); ?>
        <div class="contact-form">
            <div class="form-heading">
                <h2>Contact Us About <?php the_title(); ?></h2>
            </div>
            <form id="custom-contact-form" method="post" action="">
                <p>
                    <label for="first_name">First Name *</label><br>
                    <input type="text" id="first_name" name="first_name" required>
                </p>
                <p>
                    <label for="last_name">Last Name *</label><br>
                    <input type="text" id="last_name" name="last_name" required>
                </p>
                <p>
                    <label for="email">Email *</label><br>
                    <input type="email" id="email" name="email" required>
                </p>
                <p>
                    <label for="phone">Phone Number (Optional)</label><br>
                    <input type="tel" id="phone" name="phone">
                </p>
                <p>
                    <label for="state">State *</label><br>
                    <input id="state" type="text" name="state" value="<?php echo esc_attr($state); ?>" required>
                </p>
                <p>
                    <label for="country">Country *</label><br>
                    <input id="country" type="text" name="country" value="<?php echo esc_attr($country); ?>" required>
                </p>
                <p>
                    <label for="message">Message *</label><br>
                    <textarea id="message" name="message" rows="5" required></textarea>
                </p>
                <p>
                    <button type="submit">Submit</button>
                </p>
            </form>
        </div>
        
        <?php
        return ob_get_clean();
    }
}
add_shortcode('custom_contact_form', 'custom_contact_form_shortcode');
/*Custom Form in Industry single page end*/


/*Industry post type loop Start*/
function inject_related_industries_after_content() {
    if (is_singular('industry')) {
        global $post;
        $location_terms = wp_get_post_terms($post->ID, 'locations', ['fields' => 'ids']);
        $service_terms = wp_get_post_terms($post->ID, 'services', ['fields' => 'ids']);
    
        $state_term_id = null;
        foreach ($location_terms as $term_id) {
            
            $term = get_term($term_id, 'locations');
            if ($term->parent != 0) {
                $parent_term = get_term($term->parent, 'locations');
                if ($parent_term && $parent_term->parent != 0) {
                    $state_term_id = $term->parent;
                } else {
                    $state_term_id = $term->term_id;
                }
            } else {
                continue;
            }
        }
    
        $args = [
            'post_type' => 'industry',
            'posts_per_page' => -1,
            'post__not_in' => [$post->ID],
            'tax_query' => [
                'relation' => 'AND',
                [
                    'taxonomy' => 'locations',
                    'field' => 'term_id',
                    'terms' => $state_term_id,
                    'include_children' => true,
                ],
                [
                    'taxonomy' => 'services',
                    'field' => 'term_id',
                    'terms' => $service_terms,
                    'operator' => 'AND',
                ],
            ],
        ];
    
        $related_query = new WP_Query($args);
    
        if (!$related_query->have_posts()) {
            return null;
        }
        
        $state_term = get_term_by( 'id', $state_term_id, 'locations' ); 
        $state_name = $state_term->name;
        
        $service_term = get_term_by( 'id', $service_terms[0], 'services' ); 
        $service_name = $service_term->name;
    
        $related_output = '<div class="related-industries">';
        $related_output .= '<h2>Related '.$service_name.' in '.$state_name.'</h2>';
        $related_output .= '<ul>';
        while ($related_query->have_posts()) {
            $related_query->the_post();
            $related_output .= '<li><a href="' . get_permalink() . '">' . get_the_title() . '</a></li>';
        }
        $related_output .= '</ul></div>';
        wp_reset_postdata();
    
        // return $content . $related_output . do_shortcode('[custom_contact_form]');
        return $related_output;
    }
}
add_shortcode('fetch_related_industry', 'inject_related_industries_after_content');
// add_filter('the_content', 'inject_related_industries_after_content');
/*Industry post type loop End*/



// Create Admin Menu
add_action('admin_menu', function() {
    add_options_page(
        'OpenAI Location Generator',         
        'OpenAI Location Generator',         
        'manage_options',                    
        'openai_location_generator',         
        'openai_location_generator_page',    
    );
});


add_action('admin_menu', 'openAI_register_settings_page');
function openAI_register_settings_page() {
    add_options_page(
        'OpenAI Settings',
        'OpenAI',
        'manage_options',
        'openAI-settings',
        'openAI_render_settings_page'
    );
}


/*********************************************************
 * Created New Setting for API and Services Start
 *********************************************************/
 
function openAI_render_settings_page() {
    ?>
    <div class="wrap">
        <h1>OpenAI Settings</h1>

        <form method="post" action="options.php">
            <?php
            settings_fields('openAI_settings_group');
            do_settings_sections('openAI-settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

add_action('admin_init', 'openAI_register_settings');

function openAI_register_settings() {
    register_setting('openAI_settings_group', 'openAI_api_key');
    register_setting('openAI_settings_group', 'openAI_services');

    // Section
    add_settings_section(
        'openAI_main_section',
        'API Configuration',
        null,
        'openAI-settings'
    );

    // API Key Field
    add_settings_field(
        'openAI_api_key',
        'API Key',
        'openAI_api_key_field_html',
        'openAI-settings',
        'openAI_main_section'
    );

    // Services Field
    add_settings_field(
        'openAI_services',
        'Services',
        'openAI_services_field_html',
        'openAI-settings',
        'openAI_main_section'
    );
}

function openAI_api_key_field_html() {
    $value = get_option('openAI_api_key');
    echo '<input type="text" name="openAI_api_key" value="' . esc_attr($value) . '" class="regular-text">';
}

function openAI_services_field_html() {
    $services = get_option('openAI_services', []);
    ?>
    <div id="openAI-services-wrapper">
        <?php foreach ((array) $services as $index => $service): ?>
            <div class="openAI-service">
                <input type="text" name="openAI_services[]" value="<?php echo esc_attr($service); ?>" />
                <button type="button" class="button remove-service">Remove</button>
            </div>
        <?php endforeach; ?>
    </div>
    <button type="button" class="button" id="add-service">Add Service</button>

    <script>
        document.getElementById('add-service').addEventListener('click', function () {
            let container = document.createElement('div');
            container.className = 'openAI-service';
            container.innerHTML = '<input type="text" name="openAI_services[]" value="" /> <button type="button" class="button remove-service">Remove</button>';
            document.getElementById('openAI-services-wrapper').appendChild(container);
        });

        document.addEventListener('click', function (e) {
            if (e.target.classList.contains('remove-service')) {
                e.target.parentElement.remove();
            }
        });
    </script>
    <?php
}

/*********************************************************
 * Created New Setting for API and Services End
 *********************************************************/


function openai_location_generator_page() { ?>
    <div class="wrap">
        <h1>OpenAI Location Generator</h1>

        <div id="openai-generator-form">
            <label for="country">Select Country:</label><br>
            <select id="country" name="country[]" multiple></select>

            <br><br>

            <label for="state">Select State:</label><br>
            <select id="state" name="state[]" multiple></select>

            <br><br>

            <label for="city">Select City:</label><br>
            <select id="city" name="city[]" multiple></select>

            <br><br>
            
            <label for="service">Select Service:</label><br>
            <select id="service" name="service">
                <option value="">Select Service</option> 
                <?php
                $services = get_option('openAI_services', []);
                foreach ($services as $service) {
                    $val = esc_attr(str_replace(' ', '-', strtolower($service)));
                    echo '<option value="' . $val . '">' . esc_html($service) . '</option>';
                }
                ?>
            </select>

            <br><br>

            <button id="generate-response" class="button button-primary">Generate</button>

            <div id="openai-response" style="margin-top:20px;">
                <h3>Output</h3>
                <textarea rows="10" cols="60" readonly></textarea>
                <br><br>
                <button id="create-posts" class="button button-primary" disabled="disabled">Create Post</button>
            </div>
        </div>
    </div>
<?php
}


// Add WYSIWYG to Add Form
add_action('services_add_form_fields', 'add_service_description_wysiwyg');
function add_service_description_wysiwyg() {
    ?>
    <div class="form-field term-description-wrap">
        <label for="service_description">Service Description</label>
        <?php
        wp_editor('', 'service_description', [
            'textarea_name' => 'service_description',
            'media_buttons' => true,
            'textarea_rows' => 5,
        ]);
        ?>
    </div>
    <?php
}

// Add WYSIWYG to Edit Form
add_action('services_edit_form_fields', 'edit_service_description_wysiwyg');
function edit_service_description_wysiwyg($term) {
    $description = get_term_meta($term->term_id, 'service_description', true);
    ?>
    <tr class="form-field">
        <th scope="row"><label for="service_description">Service Description</label></th>
        <td>
            <?php
            wp_editor($description, 'service_description', [
                'textarea_name' => 'service_description',
                'media_buttons' => true,
                'textarea_rows' => 10,
            ]);
            ?>
        </td>
    </tr>
    <?php
}



/********************************************************
 * Hook function With Generate content wirh OpenAI
********************************************************/
add_action('wp_ajax_generate_openai_response', 'handle_generate_openai_response');
add_action('wp_ajax_nopriv_generate_openai_response', 'handle_generate_openai_response');

function handle_generate_openai_response() {
    $countries = !empty($_POST['countries']) ? array_map('sanitize_text_field', (array)$_POST['countries']) : [];
    $states = !empty($_POST['states']) ? array_map('sanitize_text_field', (array)$_POST['states']) : [];
    $cities = !empty($_POST['cities']) ? array_map('sanitize_text_field', (array)$_POST['cities']) : [];
    $service = sanitize_text_field($_POST['service']);
    
    $api_key  = get_option('openAI_api_key');
    $endpoint = 'https://api.openai.com/v1/chat/completions';
    $results  = [];

    if (!empty($cities)) {
        foreach ($cities as $city) {
            $country = !empty($countries) ? $countries[0] : '';
            $state   = !empty($states)    ? $states[0]    : '';
    
            $prompt = <<<EOT
Generate SEO-optimized content for the topic: "{$service} services in {$city}, {$country}".
Use the following format with 'h2' for the "Introduction, Why Choose Us for..., Get Started" heading, 'h3' for "Benefits of..., Our Process" headings, and 'p' for paragraphs.

<h2>Introduction</h2>
<h2>Why Choose Us for {$service} in {$city}?</h2>
<h3>Benefits of {$service}</h3>
<h3>Our Process</h3>
<h2>Get Started</h2>
Each section should be 2-4 sentences. Keep tone friendly and professional. Avoid overusing city names. Keep content locally relevant.
EOT;
            $results[] = generate_openai_content($api_key, $endpoint, $prompt, "City: $city");
        }
    } elseif (!empty($states)) {
        foreach ($states as $state) {
            $country = !empty($countries) ? $countries[0] : '';

            $prompt = <<<EOT
Generate SEO-optimized content for the topic: "{$service} services in {$state}, {$country}".
Use the following format with 'h2' for the "Introduction, Why Choose Us for..., Get Started" heading, 'h3' for "Benefits of..., Our Process" headings, and 'p' for paragraphs.

<h2>Introduction</h2>
<h2>Why Choose Us for {$service} in {$state}?</h2>
<h3>Benefits of {$service}</h3>
<h3>Our Process</h3>
<h2>Get Started</h2>
Each section should be 2-4 sentences. Keep tone friendly and professional. Avoid overusing state names. Keep content locally relevant.
EOT;
            $results[] = generate_openai_content($api_key, $endpoint, $prompt, "State: $state");
        }
    } elseif (!empty($countries) && empty($states) && empty($cities)) {
        
        foreach ($countries as $country) {
            $prompt = <<<EOT
Generate SEO-optimized content for the topic: "{$service} services in {$country}".
Use the following format with 'h2' for the "Introduction, Why Choose Us for..., Get Started" heading, 'h3' for "Benefits of..., Our Process" headings, and 'p' for paragraphs.

<h2>Introduction</h2>
<h2>Why Choose Us for {$service} in {$state}?</h2>
<h3>Benefits of {$service}</h3>
<h3>Our Process</h3>
<h2>Get Started</h2>
Each section should be 2-4 sentences. Keep tone friendly and professional. Avoid overusing state names. Keep content locally relevant.
EOT;
            $results[] = generate_openai_content($api_key, $endpoint, $prompt, "Country: $country");
        }
        
    } elseif (!empty($service) && empty($countries) && empty($states) && empty($cities)) {
        $service_name = str_replace('-', ' ',ucwords($service));
        $prompt = <<<EOT
Generate SEO-optimized content for the topic: "{$service}".
Use the following format with 'h2' for the "Introduction, Why Choose Us for..., Get Started" heading, 'h3' for "Benefits of..., Our Process" headings, and 'p' for paragraphs.

<h2>Introduction</h2>
<h2>Why Choose Us for {$service}?</h2>
<h3>Benefits of {$service}</h3>
<h3>Our Process</h3>
<h2>Get Started</h2>
Each section should be 2-4 sentences. Keep tone friendly and professional. Avoid overusing state names. Keep content locally relevant.
EOT;
            $results[] = generate_openai_content($api_key, $endpoint, $prompt, "Service: $service_name");
    } else {
        wp_send_json_error('Please select at least one city or state.');
    }
    
    wp_send_json_success($results);
}

function generate_openai_content($api_key, $endpoint, $prompt, $label) {
    $body = [
        'model' => 'gpt-4o',
        'messages' => [
            ['role' => 'system', 'content' => 'You are a helpful assistant.'],
            ['role' => 'user', 'content' => $prompt]
        ],
        'temperature' => 0.7,
    ];
    
    $response = wp_remote_post($endpoint, [
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type'  => 'application/json',
        ],
        'body'    => wp_json_encode($body),
        'timeout' => 60,
    ]);
    
    if (is_wp_error($response)) {
        return "[$label, content: Request failed]";
    }
    
    $response_body = json_decode(wp_remote_retrieve_body($response), true);

    if (isset($response_body['choices'][0]['message']['content'])) {
        $content  = trim($response_body['choices'][0]['message']['content']);
        $cleaned  = preg_replace(['/^`+|`+$/', '/\\\n/'], '', $content);
        $cleaned  = str_replace(['```html', '```'], '', $cleaned);
        return "[$label, content: $cleaned]";
    }

    return "[$label, content: No response generated]";

}


/*******************************************
 * Hook function With Create Post Button
*******************************************/
add_action('wp_ajax_generate_location_posts', 'generate_location_posts_callback');
function generate_location_posts_callback() {
    $locations = $_POST['locations'] ?? [];
    $city_content = $_POST['city_content'] ?? [];
    $service = sanitize_text_field($_POST['service'] ?? '');
    $service_name = str_replace('-', ' ', ucwords($service));
    $created_posts = [];
    $debug_output = [];
    
    if ( !empty($locations) && !empty($service) ) {
        $service_term = term_exists($service_name, 'services') ?: wp_insert_term($service_name, 'services', [
            'slug' => sanitize_title($service_name),
        ]);
        $service_term_id = is_array($service_term) ? $service_term['term_id'] : $service_term;
    

        foreach ($locations as $group) {
            $country = sanitize_text_field($group['country'] ?? '');
            $country_name = sanitize_text_field($group['name'] ?? '');
            $country_term = term_exists($country, 'locations') ?: wp_insert_term($country, 'locations', [
                'slug' => sanitize_title($country),
            ]);
            $country_term_id = is_array($country_term) ? $country_term['term_id'] : $country_term;
            $states = isset($group['states']) && is_array($group['states']) 
                ? array_filter($group['states'], function($item) {
                    return is_array($item) && isset($item['name']) && $item['name'] !== '' && $item['name'] !== '0';
                }) 
                : [];
            
            if( !empty($states) ) {
                foreach ($states as $state) {
                    $state_name = sanitize_text_field($state['name'] ?? '');
                    $cities = isset($state['cities']) && is_array($state['cities']) ? $state['cities'] : [];
                    $state_term = term_exists($state_name, 'locations', $country_term_id) ?: wp_insert_term($state_name, 'locations', [
                        'parent' => $country_term_id,
                        'slug' => sanitize_title($state_name),
                    ]);
                    $state_term_id = is_array($state_term) ? $state_term['term_id'] : $state_term;
        
                    if (!empty($cities)) {
                        foreach ($cities as $city) {
                            $city = sanitize_text_field($city);
                            $city_term = term_exists($city, 'locations', $state_term_id) ?: wp_insert_term($city, 'locations', [
                                'parent' => $state_term_id,
                                'slug' => sanitize_title($city),
                            ]);
                            $city_term_id = is_array($city_term) ? $city_term['term_id'] : $city_term;
        
                            $key = 'City: ' . trim($city);
                            $content = isset($city_content[$key]) ? wp_kses_post($city_content[$key]) : "No content available for {$city}.";
                            
                            $title = "$service_name service in $city, $state_name";
                            $existing_post = get_page_by_title( $title, OBJECT, 'industry' );
                            
                            if ( !$existing_post ) {
                                $post_id = wp_insert_post([
                                    'post_title'   => "$service_name service in $city, $state_name",
                                    'post_content' => $content,
                                    'post_status'  => 'publish',
                                    'post_type'    => 'industry',
                                ]);
                            
                                if (!is_wp_error($post_id)) {
                                    wp_set_object_terms($post_id, [(int)$country_term_id, (int)$state_term_id, (int)$city_term_id], 'locations');
                                    if (!empty($service_term_id)) {
                                        wp_set_object_terms($post_id, (int)$service_term_id, 'services');
                                    }
                                    $created_posts[] = $post_id;
                                }
                            } else {
                                $created_posts = false;
                            }
                        }
                    } else {
                        $key = 'State: ' . trim($state_name);
                        $content = isset($city_content[$key]) ? wp_kses_post($city_content[$key]) : "No content available for State {$state_name}.";
                        
                        $title = "$service_name service in $city, $state_name";
                        $existing_post = get_page_by_title( $title, OBJECT, 'industry' );
                        
                        if ( !$existing_post ) {
                            $post_id = wp_insert_post([
                                'post_title'   => "$service_name service in $state_name",
                                'post_content' => $content,
                                'post_status'  => 'publish',
                                'post_type'    => 'industry',
                            ]);
            
                            if (!is_wp_error($post_id)) {
                                wp_set_object_terms($post_id, [(int)$country_term_id, (int)$state_term_id], 'locations');
                                if (!empty($service_term_id)) {
                                    wp_set_object_terms($post_id, (int)$service_term_id, 'services');
                                }
                                $created_posts[] = $post_id;
                            }
                        } else {
                            $created_posts = false;
                        }
                    }
                }
            } else {
                $key = 'Country: ' . $country;
                $content = $city_content[$key] ?? "No content available for Country {$country}.";
                $title = "$service_name service in $country";
                if (!get_page_by_title($title, OBJECT, 'industry')) {
                    $post_id = wp_insert_post([
                        'post_title'   => $title,
                        'post_content' => wp_kses_post($content),
                        'post_status'  => 'publish',
                        'post_type'    => 'industry',
                    ]);
                    if (!is_wp_error($post_id)) {
                        wp_set_object_terms($post_id, [(int)$country_term_id], 'locations');
                        if (!empty($service_term_id)) {
                            wp_set_object_terms($post_id, (int)$service_term_id, 'services');
                        }
                        $created_posts[] = $post_id;
                    }
                }
            }
        }
        
        
        wp_send_json_success([
            'locations' => $locations,
            'created'   => $created_posts
        ]);
        
    } elseif( empty($locations) && !empty($service) ){
        $service_term = term_exists($service_name, 'services');

        // Always define this key
        $service_content_key = 'Service: ' . $service_name;
        
        if (!$service_term) {
            // Insert term and capture result
            $service_term = wp_insert_term($service_name, 'services', [
                'slug' => sanitize_title($service_name),
            ]);
            
            if (!is_wp_error($service_term)) {
                $service_term_id = $service_term['term_id'];
        
                // Store WYSIWYG content if provided (optional)
                $service_content = isset($city_content[$service_content_key]) ? wp_kses_post($city_content[$service_content_key]) : '';
                if (!empty($service_content)) {
                    update_term_meta($service_term_id, 'service_description', $service_content);
                }
            }
        } else {
            $service_term_id = is_array($service_term) ? $service_term['term_id'] : $service_term;
            
            // Store WYSIWYG content if provided (optional)
            $service_content = isset($city_content[$service_content_key]) ? wp_kses_post($city_content[$service_content_key]) : '';
            if (!empty($service_content)) {
                update_term_meta($service_term_id, 'service_description', $service_content);
            }
        }
        
        wp_send_json_success([
            'service' => $service_content_key,
            'created' => $service_term
        ]);

    }

}


//Change Industry Title With Locations name
add_filter('the_title', 'custom_modify_industry_title', 10, 2);
function custom_modify_industry_title($title, $post_id) {
    if (get_post_type($post_id) !== 'industry' || is_admin()) {
        return $title;
    }

    $location_terms = wp_get_post_terms($post_id, 'locations');
    if (empty($location_terms) || is_wp_error($location_terms)) {
        return $title;
    }

    $deepest_term = null;
    $max_depth = -1;

    foreach ($location_terms as $term) {
        $depth = 0;
        $ancestor = $term;
        while ($ancestor->parent != 0) {
            $ancestor = get_term($ancestor->parent, 'locations');
            if (is_wp_error($ancestor)) {
                break;
            }
            $depth++;
        }

        if ($depth > $max_depth) {
            $max_depth = $depth;
            $deepest_term = $term;
        }
    }

    if (!empty($deepest_term)) {
        $title = $deepest_term->name;
    }

    return $title;
}

//Change Industry Title With Locations name Globally
add_filter('get_the_title', 'prepend_deepest_location_to_industry_title', 10, 2);
function prepend_deepest_location_to_industry_title($title, $post_id) {
    if (get_post_type($post_id) !== 'industry' || is_admin()) {
        return $title;
    }

    $location_terms = wp_get_post_terms($post_id, 'locations');
    if (empty($location_terms) || is_wp_error($location_terms)) {
        return $title;
    }

    $deepest_term = null;
    $max_depth = -1;

    foreach ($location_terms as $term) {
        $depth = 0;
        $ancestor = $term;
        while ($ancestor->parent != 0) {
            $ancestor = get_term($ancestor->parent, 'locations');
            if (is_wp_error($ancestor)) {
                break;
            }
            $depth++;
        }

        if ($depth > $max_depth) {
            $max_depth = $depth;
            $deepest_term = $term;
        }
    }

    if (!empty($deepest_term)) {
        $title = $deepest_term->name;
    }

    return $title;
}



//Fetch Locations based on related service on archive page
add_shortcode('related_locations_by_service', 'display_related_locations_by_service');
function display_related_locations_by_service() {
    if (!is_tax('services')) {
        return '';
    }

    $current_service = get_queried_object();
    if (!$current_service || is_wp_error($current_service)) {
        return '';
    }

    $taxonomy = 'locations';
    $service_term_id = $current_service->term_id;

    // Detect selected location
    $location_slug = null;
    $depth_param = null;

    if (isset($_GET['state'])) {
        $location_slug = sanitize_text_field($_GET['state']);
        $depth_param = 'state';
    } elseif (isset($_GET['country'])) {
        $location_slug = sanitize_text_field($_GET['country']);
        $depth_param = 'country';
    }

    $location_term = $location_slug ? get_term_by('slug', $location_slug, $taxonomy) : null;
    $parent_id = $location_term ? $location_term->term_id : 0;

    // If state is selected, show posts and exit
    if ($depth_param === 'state' && $location_term) {
        return get_related_posts_output($service_term_id, $location_term->term_id);
    }

    // Otherwise, fetch next-level terms (country → state)
    $child_terms = get_terms([
        'taxonomy' => $taxonomy,
        'parent' => $parent_id,
        'hide_empty' => false,
    ]);

    $filtered_terms = [];
    foreach ($child_terms as $term) {
        $query = new WP_Query([
            'post_type' => 'industry',
            'posts_per_page' => 1,
            'tax_query' => [
                'relation' => 'AND',
                [
                    'taxonomy' => 'locations',
                    'field' => 'term_id',
                    'terms' => $term->term_id,
                ],
                [
                    'taxonomy' => 'services',
                    'field' => 'term_id',
                    'terms' => $service_term_id,
                ],
            ],
        ]);

        if ($query->have_posts()) {
            $filtered_terms[] = $term;
        }

        wp_reset_postdata();
    }

    if (!empty($filtered_terms)) {
        $next_label = $depth_param === 'country' ? 'State' : 'Country';
        $output = "<h2>Select a {$next_label}</h2>";
        $output .= '<ul class="location-navigation">';

        foreach ($filtered_terms as $term) {
            $query_args = $depth_param === 'country'
                ? ['state' => $term->slug]
                : ['country' => $term->slug];

            $url = add_query_arg($query_args);
            $output .= '<li><a href="' . esc_url($url) . '">' . esc_html($term->name) . '</a></li>';
        }

        $output .= '</ul>';
        return $output;
    }

    // If no children or nothing selected
    return '<p>Please select a country to begin.</p>';
}

function get_related_posts_output($service_term_id, $location_term_id) {
    $query = new WP_Query([
        'post_type' => 'industry',
        'posts_per_page' => -1,
        'tax_query' => [
            'relation' => 'AND',
            [
                'taxonomy' => 'locations',
                'field' => 'term_id',
                'terms' => $location_term_id,
            ],
            [
                'taxonomy' => 'services',
                'field' => 'term_id',
                'terms' => $service_term_id,
            ],
        ],
    ]);

    if ($query->have_posts()) {
        $output = "<h2>Related Posts</h2><ul class='industry-posts'>";
        while ($query->have_posts()) {
            $query->the_post();
            $output .= '<li><a href="' . get_permalink() . '">' . get_the_title() . '</a></li>';
        }
        wp_reset_postdata();
        $output .= '</ul>';
    } else {
        $output = '<p>No industries found for this state and service.</p>';
    }

    return $output;
}




