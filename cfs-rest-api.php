<?php

/*
Plugin Name: CFS Rest API
Description: Allow using custom fields with CFS plugin in REST API
Version: 2.6.4 
Author: Alexander Malstev
*/


add_filter('rest_prepare_page', 'add_custom_fields_to_api_response', 10, 2);
add_filter('rest_prepare_post', 'add_custom_fields_to_api_response', 10, 2);
add_filter('rest_prepare_review', 'add_custom_fields_to_api_response', 10, 2);
add_filter('rest_prepare_resolution', 'add_custom_fields_to_api_response', 10, 2);
add_filter('rest_prepare_shipping_time', 'add_custom_fields_to_api_response', 10, 2);
add_filter('woocommerce_rest_prepare_product_object', 'add_custom_fields_to_api_response', 10, 3);

add_filter('cfs-rest-api-fields', 'add_custom_fields_to_api_response', 1, 2);
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

    $term_fields = [];

    foreach ($fields as $value) {
        if ($value['type'] == 'term') {
            $term_fields[] = $value['name'];
        }
    }

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
        if ($field['type'] == 'term') {
            $fields = CFS()->get($field['name'], $post_id);

            if(!$fields){
                continue;
            }

            $convertedFields = [];

            foreach ($fields as $value) {
                $convertedFields[] = get_term($value);
            }

            $cfs[$field['name']] = $convertedFields;

            continue;
        }
        if ($field['type'] == 'loop') {
            $loopValues = CFS()->get($field['name'], $post_id);

            
            if (!$loopValues) {
                $loopValues = [];
            }
            
            foreach ($loopValues as $in_key => $loopValue) {
                foreach ($loopValue as $key => $valueArray) {

                    if (in_array($key, $term_fields)) {
                        foreach ($valueArray as $value) {
                            $loopValues[$in_key][$key] = [];
                            $loopValues[$in_key][$key][] = get_term($value);
                        }
                    }
                }
            }
            $cfs[$field['name']] = $loopValues;

            continue;
        }


        $cfs[$field['name']] = CFS()->get($field['name'], $post_id);

    }

    $taxonomies = get_taxonomies();

    $gottenTaxonomies = [];

    foreach ($taxonomies as $taxonomy) {

        // print_r(get_the_terms($post_id, $taxonomy));
        $terms = get_the_terms($post_id, $taxonomy);
        if($terms){
            $gottenTaxonomies[$taxonomy] = $terms;
        }
    }

    $response->data['cfs'] = $cfs;
    $response->data['taxonomies'] = $gottenTaxonomies;
    unset($response->data['meta_data']);

    return $response;
}
