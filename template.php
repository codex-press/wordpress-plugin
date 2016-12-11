<?php

  get_header();

  $url = get_post_meta( get_the_ID() , 'cp_url', true );
  $url = 'https://codex.press' . $url . '?embed';

  if ( get_post_meta( get_the_ID() , 'cp_include_menus', true ) )
    $style = 'width:100%;height:100vh;background:white;';
  else
    $style = 'position:fixed;top:0;left:0;width:100%;height:100%;background:white;';

?>

  <iframe style="<?php echo $style ?>" src="<?php echo $url ?>"></iframe>

<?php get_footer(); ?>
