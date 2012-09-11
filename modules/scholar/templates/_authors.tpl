<?php if (isset($authors)) { ?>
<?php   foreach ((array) $authors as $author) { ?>
<?php     if (!$author['first']) { ?>, <?php } ?>
  [__tag="span" class="author vcard fn"][url="<?php $this->displayAttr($author['url']) ?>" target="_self"]<?php $this->display($author['first_name'] . ' ' . $author['last_name']) ?>[/url]<?php if ($author['category_name']) { ?> (<?php $this->display($author['category_name']) ?>)<?php } ?>[/__tag]<?php
        } ?>
<?php } ?>
<?php // vim: ft=php
