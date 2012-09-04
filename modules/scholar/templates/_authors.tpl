<?php if (isset($authors)) { ?>
<?php   foreach ((array) $authors as $author) { ?>
<?php     if (!$author['first']) { ?>, <?php } ?>
  [__tag="span" class="author vcard fn"][url="<?php $this->displayAttr($author['url']) ?>" target="_self"]<?php $this->display($author['first_name'] . ' ' . $author['last_name']) ?>[/url][/__tag]<?php
        } ?>
<?php } ?>
<?php // vim: ft=php
