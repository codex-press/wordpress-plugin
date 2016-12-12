<?php

/*
Plugin Name: Codex Press
Plugin URI: https://codex.press/docs/wordpress
Description: Does things
Version: 0.1
Author: Codex Press
Author URI: https://codex.press/
License: MIT
*/

// exit if accessed directly. would be nice to send a 404 here but WP sure
// don't make it easy
if ( ! defined( 'ABSPATH' ) )
  exit;

wp_oembed_add_provider( 'https://codex.press/*', 'https://codex.press/oembed');

// we overwrite the template for posts that have the cp_article_url meta
// property set in the editor
add_filter( 'template_include', 'override_template' );

function override_template( $template ) {
  $post_id = get_the_ID();

  if ( is_single() && !empty( $post_id ) ) {

    $article_url = get_post_meta( $post_id, 'cp_url', true );

    if ( !empty( $article_url) )
      $template = plugin_dir_path( __FILE__ ) . 'template.php';

  }

  return $template;
}


// following is editor-side things

// add JS / CSS for the meta box
add_action('admin_print_styles-post.php', 'custom_js_css');
add_action('admin_print_styles-post-new.php', 'custom_js_css');

function custom_js_css() {
    $base_url = '/wp-content/plugins/codex/';
    wp_enqueue_style('your-meta-box', $base_url . 'css/editor.css');
    wp_enqueue_script('your-meta-box', $base_url . 'js/editor.js', array('jquery'), null, true);
}

add_action( 'load-post.php', 'cp_post_meta_setup' );
add_action( 'load-post-new.php', 'cp_post_meta_setup' );

function cp_post_meta_setup() {
  add_action( 'add_meta_boxes', 'cp_add_meta_box' );
  add_action( 'save_post', 'cp_save_meta', 10, 2 );
}


function cp_add_meta_box() {
  add_meta_box(
    'cp-article-embed',
    'Codex Press',
    'cp_meta_box',
    'post',
    'side',
    'high'
  );
}

// show the box in the post editor
function cp_meta_box( $object, $box ) {

  $token = wp_nonce_field( basename( __FILE__ ), 'cp_token' ); 

  $url = esc_attr( get_post_meta( $object->ID, 'cp_url', true ) );
  $checked = $url !== '';
  $menus = get_post_meta( $object->ID, 'cp_include_menus', true );

  ?>

  <?php echo $token ?>

  <div class="cp-row">
    <label for="cp-enabled">
      <input type="checkbox" id="cp-enabled" name="cp_enabled" <?php echo ($checked ? 'checked' : '')?> />
      Replace with a Codex Press article.
    </label>
  </div>

  <div class="cp-editor" <?php echo ($checked ? 'style="display:block"' : '') ?>>
    <div class="cp-row">
      <label for="cp-url">URL of the article on Codex Press:</label>
      <input placeholder="e.g. /nimble/xela" type="text" size="30" id="cp-url" name="cp_url" value="<?php echo $url ?>" >
    </div>

    <div class="cp-row">
      <label for="cp-include-menus">
        <input type="checkbox" id="cp-include-menus" name="cp_include_menus" <?php echo ($menus ? 'checked' : '') ?>>
        Include WordPress menus
      </label>
    </div>

    <div class="cp-row">
      <input type="button" class="button button-large" id="cp-edit" value="Edit">
      <input type="button" class="button button-large" id="cp-pull" value="Pull Content">
      <input type="button" class="button button-large" id="cp-undo" value="Undo Pull">
    </div>

    <a target=_blank href=https://codex.press/docs/wordpress>More information about this box.</a>

  </div>

  <?php
}


function cp_save_meta( $post_id, $post ) {

  // verify the token
  if ( !isset( $_POST['cp_token'] ) ||
       !wp_verify_nonce( $_POST['cp_token'], basename( __FILE__ ) ) )
    return $post_id;

  // require permission to edit
  $post_type = get_post_type_object( $post->post_type );
  if ( !current_user_can( $post_type->cap->edit_post, $post_id ) )
    return $post_id;

  if ($_POST['cp_enabled'] !== 'on') {
    delete_post_meta( $post_id, 'cp_url' );
    delete_post_meta( $post_id, 'cp_include_menus' );
  }

  else {

    // save URL
    $old_url = get_post_meta( $post_id, 'cp_url', true );

    // add
    if ( $_POST['cp_url'] && $old_url == '')
      add_post_meta( $post_id, 'cp_url', $_POST['cp_url'], true );

    // update
    elseif ( $_POST['cp_url'] && $old_url !=  $_POST['cp_url'] )
      update_post_meta( $post_id, 'cp_url', $_POST['cp_url'] );

    // delete
    elseif ( $old_url && $_POST['cp_url'] == '' )
      delete_post_meta( $post_id, 'cp_url' );

    // add / update cp_include_menus: apparently there's no way to tell if
    // it's empty or doesn't exist since both return an empty string
    $value = $_POST['cp_include_menus'] == 'on';
    if ( $value !== get_post_meta( $post_id, 'cp_include_menus', true ) )
      update_post_meta($post_id, 'cp_include_menus', $value); 

  }

}

?>
