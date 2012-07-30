<?php

function gallery_image_list() { // {{{
  // force drupal to load jquery ...
  drupal_add_js('', 'inline', 'header', false, false, false);
  gallery_add_css();

  $opts = '';
  $lang = Langs::lang();
  foreach (Langs::languages() as $code => $name) {
    $opts .= "<option value=\"$code\"" . 
            ($code == $lang ? ' selected="selected"' : '') .
            ">$name</option>";
  }
  ob_start();
?>
<script type="text/javascript">
var gallery = {
  pathinfo: function(path) {
    var name = path, ext = '';
    var p = path.lastIndexOf('.');
    if (p > -1) {
      ext = path.substr(p + 1);
      name = path.substr(0, p);
    }
    return {name:name, ext:ext};
  },
  format_str: "<?php echo gallery_image_js_string() ?>",
  format_keys: ['title','thumb','id'],
  format: function(o) {
    var html = this.format_str;
    for (var i = 0; i < this.format_keys.length; ++i) {
      var r = new RegExp('%' + this.format_keys[i], 'g');
      html = html.replace(r, o[this.format_keys[i]]);
    }
    return html;
  },
  search: function() {
    var phrase = document.getElementById('phrase');
    var what = phrase ? phrase.value : '';
    var url = '<?php echo url(GALLERY_MENU_IMAGE_RESULTS, array('absolute' => true)) ?>';
    var self = gallery;

    jQuery('#status-text').html('Loading images...');
    jQuery.getJSON(url, jQuery('#search-form').serialize(), function(data) {
      var arr = data.images;
      var html = '';
      if (arr.length) {
        for (var i = 0; i < arr.length; ++i) {
          var f = self.pathinfo(arr[i].filename);
          arr[i].thumb = data.dir.thumbs + '/' + f.name + '.' + arr[i].thumb + '.' + f.ext;
          html += self.format(arr[i]);
        }
        jQuery('#status-text').html('');
      } else {
        jQuery('#status-text').html('<i>No results for phrase: "' + what + '"</i>');
      }
      jQuery('#search-results').html(html);

      // custom action {{{      
      if (self.__imageCallback) {
        jQuery('.gallery-image-admin').each(function() { this.innerHTML = ''; });
        jQuery('.gallery-image').each(function() {
          var x = jQuery(this);
          var thumb;          
          x.find('div.gallery-image-admin').each(function() {
            var a = document.createElement('a');
            var id = x.attr('id').replace(/image-/, '');

            a.innerHTML = self.__imageCallbackTitle;
            a.href = 'javascript:void(null);'
            this.appendChild(a);

            x.find('div.gallery-image-thumb > a').each(function() {
              thumb = '' + this.parentNode.style.backgroundImage;
              thumb = thumb.replace(/^url\(['"]?/, '').replace(/['"]?\)$/, '');
              this.href = 'javascript:void(null);';
              a.onclick = function() {
                self.__imageCallback(id, thumb);
              }
              this.onclick = a.onclick;
            });
          });
        });
      }
      // }}}
    });
  },
  init: function() {
    var self = gallery;
    if (window.opener && window.opener.__imageCallback) {
      self.__imageCallback = window.opener.__imageCallback;
      self.__imageCallbackTitle = window.opener.__imageCallbackTitle;
      var b = document.body;
      b.style.display = 'none';      
      $(b).children().each(function() { this.style.display = 'none'; });
      var x = document.getElementById('gallery-search');      
      x.parentNode.removeChild(x);
      b.insertBefore(x, b.firstChild);
      b.style.display = '';
      b.style.width = 'auto';
      b.style.minWidth = '640px';
      x.style.padding = '10px';
      x.style.width = 'auto';
    }
    self.search();
  }
}
</script>
<div id="gallery-search">
<form id="search-form" action="" onsubmit="return false;">
Search image: <input type="text" id="phrase" name="phrase" />
in language: <select id="lang" name="lang"><?php echo $opts ?></select>
<input type="submit" name="submit" value="Search images" onclick="gallery.search()" />
</form>
<hr />
<div id="status-text"></div>
<div id="search-results"></div>
<noscript><i>JavaScript is required to view image list.</i></noscript>
</div>
<script type="text/javascript">jQuery(gallery.init);</script>
<?php
  return ob_get_clean();
} // }}}

function gallery_search_results() { // {{{
  while (ob_end_clean());

  $lang = trim(@$_GET['lang']);
  if ($lang == '' || !in_array($lang, array_keys(Langs::languages()))) {
    $lang = Langs::lang();
  }
  $phrase = trim(@$_GET['phrase']);
  if ($phrase != '') {
    $phrase_fmt = "AND (title LIKE '%s%%' OR title LIKE '%%%s')";
  } else {
    $phrase_fmt = '';
  }
  // TODO paginacja
  $q = db_query("SELECT i.id AS id, title, filename FROM {image} i JOIN {image_data} d ON i.id = d.image_id WHERE lang = '%s' $phrase_fmt ORDER BY weight", $lang, $phrase, $phrase);
  $dirs = toJSON(array(
            'images' => base_path() . gallery_image_dir(),
            'thumbs' => base_path() . gallery_thumb_dir(),
          ));
  echo "{\"dir\":".$dirs.",\"images\":[";
  $first = true;
  while ($row = db_fetch_array($q)) {
    if ($first) $first = false;
    else echo ',';
    $thumb = explode('.', basename(gallery_thumb($row['filename'])));
    $row['thumb'] = @$thumb[1]; // only thumb type (ie. m*, w*, h*)
    $firstkey = true;
    echo "{";
    foreach ($row as $key => $value) {
      if ($firstkey) $firstkey = false;
      else echo ",";
      echo '"' . js_escape($key) . '":"' . js_escape($value) . '"';
    }
    echo "}";
  }
  echo "]}";
  exit;
} // }}}


// vim: fdm=marker
