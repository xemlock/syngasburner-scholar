[nonl2br]
[__tag="div" class="scholar-trainings"]
<?php $sections = count($this->year_trainings) > 1;
      $first = true; ?>
<?php foreach ($this->year_trainings as $year => $trainings) { ?>
<?php   if ($sections) {
          if ($first) { $first = false; ?>[collapsible="0" title="<?php $this->displayAttr($year) ?>"]<?php }
          else { ?>[collapsible collapsed="1" title="<?php $this->displayAttr($year) ?>"]<?php }
        } ?>
<?php   foreach ($trainings as $training) { ?>
[entry date="<?php $this->displayAttr($training['start_date'] . '/' . $training['end_date']) ?>"]
[__tag="div" class="training" id="training-<?php $this->displayAttr($training['id']) ?>"]
  [__tag="div" class="training-heading"]
    [__tag="cite" class="title"][url="<?php $this->displayAttr($training['url']) ?>" target="_self"]<?php $this->display($training['title']) ?>[/url][/__tag]
  [/__tag]
<?php     if ($training['suppinfo']) { ?>
  [__tag="div" class="description"]<?php $this->display($training['suppinfo']) ?>[/__tag]
<?php     } ?>
<?php     if ($training['authors']) { ?>
  [__tag="div" class="training-lecturers"]
  [t]Lecturers[/t]: <?php 
            $authors = $training['authors'];
            include dirname(__FILE__) . '/_authors.tpl'; ?>
  [/__tag]
<?php     } ?>
[/__tag]
[/entry]
<?php   } ?>
<?php   if ($sections) { ?>[/collapsible]<?php } ?>
<?php } ?>
[/__tag]
[/nonl2br]<?php // vim: ft=php
