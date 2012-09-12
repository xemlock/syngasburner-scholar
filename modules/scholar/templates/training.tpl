[nonl2br]
<?php if ($this->training['image_id']) { ?>
[preface="__unshift"][__tag="div" class="scholar-image"][gallery-img]<?php $this->display($this->training['image_id']) ?>[/gallery-img][/__tag][/preface]
<?php } ?>
[__tag="div" class="scholar-training"]
<?php if ($this->date_classes) { ?>
[section][t]Training program[/t][/section]
<?php   foreach ($this->date_classes as $date => $classes) { ?>
[subsection][date]<?php $this->display($date) ?>[/date][/subsection]
<?php     foreach ($classes as $class) { ?>
[block="<?php $this->displayAttr($class['start_time'] . ' &ndash; ' . $class['end_time']) ?>"]
[__tag="div" class="class"]
  [__tag="div" class="headline"]
    [__tag="cite" class="title"][url="<?php $this->displayAttr($class['url']) ?>"]<?php $this->display($class['title']) ?>[/url][/__tag]<?php
            if ($class['suppinfo']) { ?>, <?php $this->display($class['suppinfo']) ?><?php } ?>
<?php       if ($class['category_name']) { ?> (<?php $this->display($class['category_name']) ?>)<?php } ?>
  [/__tag]
<?php       if ($class['authors']) { ?>
  [__tag="div" class="speakers"]<?php
              $authors = $class['authors'];
              include dirname(__FILE__) . '/_authors.tpl'; ?>[/__tag]
<?php       } ?>
<?php       if ($class['files']) { ?>
  [__tag="div" class="files"]<?php
              $files = $class['files'];
              include dirname(__FILE__) . '/_files.tpl'; ?>[/__tag]
<?php       } ?>
[/__tag]
[/block]
<?php     } ?>
<?php   } ?>
<?php } ?>
[/__tag]
[/nonl2br]
<?php // vim: ft=php
