[__tag="div" class="scholar-trainings"]
<?php $sections = count($this->year_trainings) > 1; ?>
<?php foreach ($this->year_trainings as $year => $trainings) { ?>
<?php   if ($sections) { ?>
[section="<?php $this->displayAttr($year) ?>" collapsible="<?php echo intval($year < date('Y')) ?>" collapsed="1"]
<?php   } ?>
<?php   foreach ($trainings as $training) { ?>
[block="<?php $this->displayAttr($training['start_date'] . ' &ndash; ' . $training['end_date']) ?>"]
[__tag="div" class="training"]
  [__tag="cite" class="title"][url="<?php $this->displayAttr($training['url']) ?>"]<?php $this->display($training['title']) ?>[/url][/__tag]
<?php     if ($training['suppinfo']) { ?>
  [__tag="div" class="description"]<?php $this->display($training['suppinfo']) ?>[/__tag]
<?php     } ?>
<?php     if ($training['authors']) { ?>
  [__tag="div" class="speakers"]
  [t]Speakers[/t]: <?php 
            $authors = $training['authors'];
            include dirname(__FILE__) . '/_authors.tpl'; ?>
  [/__tag]
<?php     } ?>
[/__tag]
[/block]
<?php   } ?>
<?php   if ($sections) { ?>[/section]<?php } ?>
<?php } ?>
[/__tag]
<?php // vim: ft=php
