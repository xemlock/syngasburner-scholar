<?php foreach ($this->date_classes as $date => $classes) { ?>
[section="<?php $this->displayAttr($date) ?>"]
<?php   foreach ($classes as $class) { ?>
[block="<?php $this->displayAttr($class['start_time'] . ' &ndash; ' . $class['end_time']) ?>"]
[__tag="div" class="class"]
  [__tag="div" class="headline"]
    [__tag="cite" class="title"][url="<?php $this->displayAttr($class['url']) ?>"]<?php $this->display($class['title']) ?>[/url][/__tag]<?php
          if ($class['suppinfo']) { ?>, <?php $this->display($class['suppinfo']) ?><?php } ?>
<?php     if ($class['category_name']) { ?> (<?php $this->display($class['category_name']) ?>)<?php } ?>
  [/__tag]
<?php     if ($class['authors']) { ?>
  [__tag="div" class="speakers"]<?php
            $authors = $class['authors'];
            include dirname(__FILE__) . '/_authors.tpl'; ?>[/__tag]
<?php     } ?>
<?php     if ($class['files']) { ?>
  [__tag="div" class="files"]<?php
            $files = $class['files'];
            include dirname(__FILE__) . '/_files.tpl'; ?>[/__tag]
<?php     } ?>
[/__tag]
[/block]
<?php   } ?>
[/section]
<?php } ?>
<?php // vim: ft=php
