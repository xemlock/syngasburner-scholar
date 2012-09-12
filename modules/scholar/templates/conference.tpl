[nonl2br]
<?php if ($this->conference['image_id']) { ?>
[preface="__unshift"][__tag="div" class="scholar-image"][gallery-img]<?php $this->display($this->conference['image_id']) ?>[/gallery-img][/__tag][/preface]
<?php } ?>
[__tag="div" class="scholar-conference"]
<?php $sections = count($this->year_date_presentations) > 1; 
      $first = true; ?>
<?php foreach ($this->year_date_presentations as $year => $date_presentations) { ?>
<?php   if ($sections) {
          if ($first) { $first = false; ?>[collapsible="0" title="<?php $this->displayAttr($year) ?>"]<?php }
          else { ?>[collapsible collapsed="1" title="<?php $this->displayAttr($year) ?>"]<?php }
        } ?>
<?php   foreach ($date_presentations as $date => $presentations) { ?>
[block="<?php $this->displayAttr($date) ?>"]<?php 
          include dirname(__FILE__) . '/_presentations.tpl'; ?>
[/block]
<?php   } ?>
<?php   if ($sections) { ?>[/collapsible]<?php } ?>
<?php } ?>
[/__tag]
[/nonl2br]
<?php // vim: ft=php
