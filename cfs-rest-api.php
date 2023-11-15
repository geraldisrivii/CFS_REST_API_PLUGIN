<?php

/*
Plugin Name: CFS Rest API
Description: Allow using custom fields with CFS plugin in REST API
Version: 2.6.4 
Author: Alexander Malstev
*/


add_filter('rest_prepare_page', 'add_custom_fields_to_api_response', 10, 2);
add_filter('rest_prepare_post', 'add_custom_fields_to_api_response', 10, 2);
add_filter('pre_get_settings', 'add_custom_fields_to_api_response', 10, 2);
add_filter('rest_prepare_diginity', 'add_custom_fields_to_api_response', 10, 2);
add_filter('rest_prepare_review', 'add_custom_fields_to_api_response', 10, 2);
add_filter('rest_prepare_special', 'add_custom_fields_to_api_response', 10, 2);
add_filter('woocommerce_rest_prepare_product_object', 'add_custom_fields_to_api_response', 10, 3);

function add_custom_fields_to_api_response($response, $post)
{
    $post_id = 0;

    
    if (isset($post->ID)) {
        $post_id = $post->ID;
    } else {
        $post_id = $post->id;
    }

    $fields = CFS()->find_fields([
        'post_id' => $post_id
    ]); 

    $cfs = [];
    $expectingTypes = [
        'tab',
    ];
    foreach ($fields as $key => $field) {
        if (in_array($field['type'], $expectingTypes)) {
            continue;
        }
        if ($field['parent_id'] != 0) {
            continue;
        }
        $cfs[$field['name']] = CFS()->get($field['name'], $post_id);

    }

    $taxonomies = get_taxonomies([
        'object_type' => ['diginity'],
    ]);

    foreach ($taxonomies as $key => $taxonomy) {
        $response->data[$taxonomy] = get_the_terms( $post_id, $taxonomy);
    }

    $response->data['cfs'] = $cfs;
    $response->data['taxonomies'] = $taxonomies;
    
    return $response;
}

add_action('rest_after_insert_post', 'custom_editor_before_insert', 10, 3);
add_action('rest_after_insert_review', 'custom_editor_before_insert', 10, 3);

function custom_editor_before_insert($post, $request) {
    $body = json_decode($request->get_body(), true);
    foreach ($body['cfs'] as $key => $value) {
        CFS()->save( [$key =>  $value] , ['ID' => $post->ID] );
    }
}
