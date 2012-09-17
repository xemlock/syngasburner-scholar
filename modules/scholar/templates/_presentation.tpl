<?php if (isset($presentation)) { ?>
[__tag="div" class="presentation" id="presentation-<?php $this->displayAttr($presentation['id']) ?>"]
  [__tag="div" class="presentation-heading"]
<?php   if ($presentation['authors']) { ?>
    [__tag="span" class="authors"]
<?php     $authors = $presentation['authors'];
          include dirname(__FILE__) . '/_authors.tpl'; ?>[/__tag]:
<?php   } ?>
    [__tag="cite" class="title"][url="<?php $this->displayAttr($presentation['url']) ?>"]<?php $this->display($presentation['title']) ?>[/url][/__tag]
<?php   if ($presentation['category_name']) { ?> (<?php $this->display($presentation['category_name']) ?>)<?php } ?>
  [/__tag]
<?php   if ($presentation['files']) { ?>
  [__tag="div" class="presentation-files"]<?php
          $files = $presentation['files'];
          include dirname(__FILE__) . '/_files.tpl'; ?>[/__tag]<?php
        } ?>
<?php   if ($presentation['suppinfo']) { ?>
  [__tag="div" class="presentation-description"]<?php $this->display($presentation['suppinfo']) ?>[/__tag]<?php
        } ?>
[/__tag]
<?php } ?>
<?php // vim: ft=php
