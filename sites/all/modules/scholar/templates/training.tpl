[nonl2br]
[__image]<?php $this->display($this->conference['image_id']) ?>[/__image]
[__tag="div" class="scholar-training"]
<?php if ($this->date_classes) { ?>
[section][t]Training program[/t][/section]
<?php   foreach ($this->date_classes as $date => $classes) { ?>
[subsection][date]<?php $this->display($date) ?>[/date][/subsection]
<?php     foreach ($classes as $class) { ?>
[entry="<?php if ($class['start_time']) { $this->displayAttr($class['start_time'] . ' &ndash; ' . $class['end_time']); } ?>"]
[__tag="div" class="training-class" id="class-<?php $this->displayAttr($class['id']) ?>"]
  [__tag="div" class="training-class-heading"]
    [__tag="cite" class="title"][url="<?php $this->displayAttr($class['url']) ?>"]<?php $this->display($class['title']) ?>[/url][/__tag]<?php
            if ($class['suppinfo']) { ?>, <?php $this->display($class['suppinfo']) ?><?php } ?>
<?php       if ($class['category_name']) { ?> (<?php $this->display($class['category_name']) ?>)<?php } ?>
  [/__tag]
<?php       if ($class['authors']) { ?>
  [__tag="div" class="training-class-lecturers"]<?php
              $authors = $class['authors'];
              include dirname(__FILE__) . '/_authors.tpl'; ?>[/__tag]
<?php       } ?>
<?php       if ($class['files']) { ?>
  [__tag="div" class="training-class-files"]<?php
              $files = $class['files'];
              include dirname(__FILE__) . '/_files.tpl'; ?>[/__tag]
<?php       } ?>
[/__tag]
[/entry]
<?php     } ?>
<?php   } ?>
<?php } ?>
[/__tag]
[/nonl2br]<?php // vim: ft=php
