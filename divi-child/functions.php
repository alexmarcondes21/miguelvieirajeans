<?php

function my_theme_enqueue_styles() { 
    wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
}
add_action( 'wp_enqueue_scripts', 'my_theme_enqueue_styles' );

$filter_hook = apply_filters( 'add_hook_custom_size_chart_position', 'woocommerce_before_add_to_cart_button' );
add_action( $filter_hook, 'size_chart_popup_button_callback', 11 );

function size_chart_popup_button_callback()
    {
        global  $post ;
        $dup_id = array();
        $prod_id = scfw_size_chart_get_product( $post->ID );
        $prod_id = ( is_array( $prod_id ) ? $prod_id : [ $prod_id ] );
        if ( isset( $prod_id ) && is_array( $prod_id ) && !empty($prod_id) ) {
            foreach ( $prod_id as $prod_val ) {
                
                if ( '' !== get_post_status( $prod_val ) && 'publish' === get_post_status( $prod_val ) ) {
                    $chart_id = $prod_val;
                } else {
                    $chart_id =scfw_size_chart_id_by_category( $post->ID );
                }
                
                // Check if product is belongs to tag
                
                if ( 0 === intval( $chart_id ) || !$chart_id ) {
                    $chart_id = $this->scfw_size_chart_id_by_tag( $post->ID );
                    // Check if product is belongs to attribute
                    if ( 0 === intval( $chart_id ) || !$chart_id ) {
                        $chart_id = $this->scfw_size_chart_id_by_attributes( $post->ID );
                    }
                }
                
                $chart_label = scfw_size_chart_get_label_by_chart_id( $chart_id );
                $chart_position = scfw_size_chart_get_position_by_chart_id( $chart_id );
                
                if ( 0 !== $chart_id && 'popup' === $chart_position ) {
                    $chart_popup_label = scfw_size_chart_get_popup_label_by_chart_id( $chart_id );
                    
                    if ( isset( $chart_popup_label ) && !empty($chart_popup_label) ) {
                        $popup_label = $chart_popup_label;
                    } else {
                        $size_chart_popup_label = scfw_size_chart_get_popup_label();
                        
                        if ( isset( $size_chart_popup_label ) && !empty($size_chart_popup_label) ) {
                            $popup_label = $size_chart_popup_label;
                        } else {
                            $popup_label = $chart_label;
                        }
                    
                    }
                    
                    $size_chart_get_button_class = '';
                    ?>
					<div class="button-wrapper">
		                <a class="<?php 
                    echo  esc_attr( $size_chart_get_button_class ) ;
                    ?> md-size-chart-btn" chart-data-id="chart-<?php 
                    esc_attr_e( $chart_id );
                    ?>" href="javascript:void(0);" id="chart-button">
							<?php 
                    echo  esc_html( $popup_label ) ;
                    ?>
		                </a>
		            </div>
		            <div id="md-size-chart-modal" class="md-size-chart-modal scfw-size-chart-modal" chart-data-id="chart-<?php 
                    esc_attr_e( $chart_id );
                    ?>">
		                <div class="md-size-chart-modal-content">
		                    <div class="md-size-chart-overlay"></div>
		                    <div class="md-size-chart-modal-body">
		                        <button data-remodal-action="close" id="md-poup" class="remodal-close" aria-label="<?php 
                    esc_attr_e( 'Close', 'size-chart-for-woocommerce' );
                    ?>"></button>
		                        <div class="chart-container">
									<?php 
                    $file_dir_path = 'includes/common-files/size-chart-contents.php';
                    if ( file_exists( plugin_dir_path( dirname( __FILE__ ) ) . $file_dir_path ) ) {
                        include plugin_dir_path( dirname( __FILE__ ) ) . $file_dir_path;
                    }
                    ?>
		                        </div>
		                    </div>
		                </div>
		            </div>
					<?php 
                    $dup_id[] = $prod_val;
                }
            
            }
        }
        
        if ( isset( $post->ID ) && !empty($post->ID) ) {
            $chart_ids = scfw_size_chart_id_by_category( $post->ID );
            if ( !empty($chart_ids) ) {
                foreach ( $chart_ids as $chart_id ) {
                    
                    if ( !in_array( $chart_id, $dup_id ) ) {
                        scfw_size_chart_popup_button_area( $chart_id );
                        $dup_id[] = $chart_id;
                    }
                
                }
            }
            $chart_tag_id = scfw_size_chart_id_by_tag( $post->ID );
            if ( !empty($chart_tag_id) ) {
                foreach ( $chart_tag_id as $chart_id ) {
                    
                    if ( !in_array( $chart_id, $dup_id ) ) {
                        scfw_size_chart_popup_button_area( $chart_id );
                        $dup_id[] = $chart_id;
                    }
                
                }
            }
            $chart_attr_id =scfw_size_chart_id_by_attributes( $post->ID );
            if ( !empty($chart_attr_id) ) {
                foreach ( $chart_attr_id as $chart_id ) {
                    if ( !in_array( $chart_id, $dup_id ) ) {
                        scfw_size_chart_popup_button_area( $chart_id );
                    }
                }
            }
        }
    
    }



    function scfw_size_chart_id_by_category( $product_id )
    {
        $size_chart_id = 0;
        $product_terms = wc_get_product_term_ids( $product_id, 'product_cat' );
        
        if ( isset( $product_terms ) && !empty($product_terms) && (is_array( $product_terms ) && array_filter( $product_terms )) ) {
            $cache_key = 'size_chart_categories_with_product_categories_' . implode( "_", $product_terms );
            $size_chart_id = wp_cache_get( $cache_key );
            
            if ( false === $size_chart_id ) {
                $size_chart_args = array(
                    'posts_per_page'         => 10,
                    'order'                  => 'DESC',
                    'post_type'              => 'size-chart',
                    'post_status'            => 'publish',
                    'no_found_rows'          => true,
                    'update_post_term_cache' => false,
                    'fields'                 => 'ids',
                );
                $size_chart_args['meta_query']['relation'] = 'OR';
                foreach ( $product_terms as $product_term ) {
                    $size_chart_args['meta_query'][] = array(
                        'key'     => 'chart-categories',
                        'value'   => "[{$product_term},",
                        'compare' => 'LIKE',
                    );
                    $size_chart_args['meta_query'][] = array(
                        'key'     => 'chart-categories',
                        'value'   => ",{$product_term},",
                        'compare' => 'LIKE',
                    );
                    $size_chart_args['meta_query'][] = array(
                        'key'     => 'chart-categories',
                        'value'   => ",{$product_term}]",
                        'compare' => 'LIKE',
                    );
                    $size_chart_args['meta_query'][] = array(
                        'key'     => 'chart-categories',
                        'value'   => "[{$product_term}]",
                        'compare' => 'LIKE',
                    );
                }
                $size_chart_category_query = new WP_Query( $size_chart_args );
                if ( isset( $size_chart_category_query ) && !empty($size_chart_category_query) && $size_chart_category_query->have_posts() ) {
                    foreach ( $size_chart_category_query->posts as $chart_array_id ) {
                        $size_chart_id[] = $chart_array_id;
                    }
                }
                wp_cache_set( $cache_key, $size_chart_id );
            }
        
        }
        
        return $size_chart_id;
    }


    function scfw_size_chart_popup_button_area( $chart_id )
    {
        $chart_label = scfw_size_chart_get_label_by_chart_id( $chart_id );
        $chart_position = scfw_size_chart_get_position_by_chart_id( $chart_id );
        
        if ( 0 !== $chart_id && 'popup' === $chart_position ) {
            $chart_popup_label = scfw_size_chart_get_popup_label_by_chart_id( $chart_id );
            
            if ( isset( $chart_popup_label ) && !empty($chart_popup_label) ) {
                $popup_label = $chart_popup_label;
            } else {
                $size_chart_popup_label = scfw_size_chart_get_popup_label();
                
                if ( isset( $size_chart_popup_label ) && !empty($size_chart_popup_label) ) {
                    $popup_label = $size_chart_popup_label;
                } else {
                    $popup_label = $chart_label;
                }
            
            }
            
            $size_chart_get_button_class = '';
            ?>
			<div class="button-wrapper">
				<a class="<?php 
            echo  esc_attr( $size_chart_get_button_class ) ;
            ?> md-size-chart-btn" chart-data-id="chart-<?php 
            esc_attr_e( $chart_id );
            ?>" href="javascript:void(0);" id="chart-button">
					<?php 
            echo  esc_html( $popup_label ) ;
            ?>
				</a>
			</div>
			<div id="md-size-chart-modal" class="md-size-chart-modal scfw-size-chart-modal" chart-data-id="chart-<?php 
            esc_attr_e( $chart_id );
            ?>">
				<div class="md-size-chart-modal-content">
					<div class="md-size-chart-overlay"></div>
					<div class="md-size-chart-modal-body">
						<button data-remodal-action="close" id="md-poup" class="remodal-close" aria-label="<?php 
            esc_attr_e( 'Close', 'size-chart-for-woocommerce' );
            ?>"></button>
						<div class="chart-container">
							<?php 
            $file_dir_path = 'includes/common-files/size-chart-contents.php';
            if ( file_exists( plugin_dir_path( dirname( __FILE__ ) ) . $file_dir_path ) ) {
                include plugin_dir_path( dirname( __FILE__ ) ) . $file_dir_path;
            }
            ?>
						</div>
					</div>
				</div>
			</div>
			<?php 
        }
    
    }


    function scfw_size_chart_id_by_tag( $product_id )
    {
        $size_chart_id = 0;
        $product_terms = wc_get_product_term_ids( $product_id, 'product_tag' );
        
        if ( isset( $product_terms ) && !empty($product_terms) && (is_array( $product_terms ) && array_filter( $product_terms )) ) {
            $cache_key = 'size_chart_tags_with_product_tags_' . implode( "_", $product_terms );
            $size_chart_id = wp_cache_get( $cache_key );
            
            if ( false === $size_chart_id ) {
                $size_chart_args = array(
                    'posts_per_page'         => 10,
                    'order'                  => 'DESC',
                    'post_type'              => 'size-chart',
                    'post_status'            => 'publish',
                    'no_found_rows'          => true,
                    'update_post_term_cache' => false,
                    'fields'                 => 'ids',
                );
                $size_chart_args['meta_query']['relation'] = 'OR';
                foreach ( $product_terms as $product_term ) {
                    $size_chart_args['meta_query'][] = array(
                        'key'     => 'chart-tags',
                        'value'   => "[{$product_term},",
                        'compare' => 'LIKE',
                    );
                    $size_chart_args['meta_query'][] = array(
                        'key'     => 'chart-tags',
                        'value'   => ",{$product_term},",
                        'compare' => 'LIKE',
                    );
                    $size_chart_args['meta_query'][] = array(
                        'key'     => 'chart-tags',
                        'value'   => ",{$product_term}]",
                        'compare' => 'LIKE',
                    );
                    $size_chart_args['meta_query'][] = array(
                        'key'     => 'chart-tags',
                        'value'   => "[{$product_term}]",
                        'compare' => 'LIKE',
                    );
                }
                $size_chart_tags_query = new WP_Query( $size_chart_args );
                if ( isset( $size_chart_tags_query ) && !empty($size_chart_tags_query) && $size_chart_tags_query->have_posts() ) {
                    foreach ( $size_chart_tags_query->posts as $chart_array_id ) {
                        $size_chart_id[] = $chart_array_id;
                    }
                }
                wp_cache_set( $cache_key, $size_chart_id );
            }
        
        }
        
        return $size_chart_id;
    }


    function scfw_size_chart_id_by_attributes( $product_id )
    {
        $size_chart_id = 0;
        $product = wc_get_product( $product_id );
        $product_attributes = $product->get_attributes();
        $product_terms = [];
        foreach ( $product_attributes as $attribute ) {
            if ( !empty($attribute->get_options()) ) {
                $product_terms = array_merge( $product_terms, $attribute->get_options() );
            }
        }
        
        if ( isset( $product_terms ) && !empty($product_terms) && (is_array( $product_terms ) && array_filter( $product_terms )) ) {
            $cache_key = 'size_chart_attributes_with_product_attributes_' . implode( "_", $product_terms );
            $size_chart_id = wp_cache_get( $cache_key );
            
            if ( false === $size_chart_id ) {
                $size_chart_args = array(
                    'posts_per_page'         => 10,
                    'order'                  => 'DESC',
                    'post_type'              => 'size-chart',
                    'post_status'            => 'publish',
                    'no_found_rows'          => true,
                    'update_post_term_cache' => false,
                    'fields'                 => 'ids',
                );
                $size_chart_args['meta_query']['relation'] = 'OR';
                foreach ( $product_terms as $product_term ) {
                    $size_chart_args['meta_query'][] = array(
                        'key'     => 'chart-attributes',
                        'value'   => "[{$product_term},",
                        'compare' => 'LIKE',
                    );
                    $size_chart_args['meta_query'][] = array(
                        'key'     => 'chart-attributes',
                        'value'   => ",{$product_term},",
                        'compare' => 'LIKE',
                    );
                    $size_chart_args['meta_query'][] = array(
                        'key'     => 'chart-attributes',
                        'value'   => ",{$product_term}]",
                        'compare' => 'LIKE',
                    );
                    $size_chart_args['meta_query'][] = array(
                        'key'     => 'chart-attributes',
                        'value'   => "[{$product_term}]",
                        'compare' => 'LIKE',
                    );
                }
                $size_chart_attributes_query = new WP_Query( $size_chart_args );
                if ( isset( $size_chart_attributes_query ) && !empty($size_chart_attributes_query) && $size_chart_attributes_query->have_posts() ) {
                    foreach ( $size_chart_attributes_query->posts as $chart_array_id ) {
                        $size_chart_id[] = $chart_array_id;
                    }
                }
                wp_cache_set( $cache_key, $size_chart_id );
            }
        
        }
        
        return $size_chart_id;
    }