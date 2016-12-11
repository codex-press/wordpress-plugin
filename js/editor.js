jQuery(function() {
  var $ = jQuery;

  $('#cp-enabled').on('change', function(e) {
    if ($('#cp-enabled')[0].checked)
      $('#cp-article-embed .cp-editor').css({display:'block'});
    else
      $('#cp-article-embed .cp-editor').css({display:'none'});
  });

  // open the story in the Codex editor
  $('#cp-edit').on('click', function(e) {
    var url = $('#cp-url').val();
    if (url)
      open('https://codex.press/edit/article' + url, '_blank');
    else
      open('https://codex.press/edit', '_blank');
  });

  // preventDefault on enter key and do the pull
  $('#cp-url').on('keypress', function(e) {
    if (e.which == 13) {
      e.preventDefault();
      pull();
    }
  });

  // pull the latest version and overwrite the body of the WP post
  $('#cp-pull').on('click', pull)
  
  function pull(e) {
    var url = $('#cp-url').val();

    $.ajax({
      url: 'https://codex.press' + url + '.json?full=true',
      success: function(response) {

        $('input#title').val(response.title);
        $('input#title').focus();
        $('input#title').blur();
 
        var editor = (
          $('#wp-' + wpActiveEditor + '-wrap').hasClass('tmce-active') &&
          tinyMCE.get(wpActiveEditor)
        );

        var content = response.content;
        content = content.filter(function(c) { return c.body; });
        content = content.map(function(c, i) { return '<p>' + c.body +'</p>';});
        // insert more thingie after 4th graf
        content.splice(4,0,'<!--more-->');
        content = content.join('');

        if (editor)
          return editor.setContent(content);
        else
          return $('#'+wpActiveEditor).val(content);
      },
      failure: function(response) {
        $('#cp-error').text('There was a problem fetching that article');
      },
    });
  };

});
