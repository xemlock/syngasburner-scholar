[__tag="div" class="scholar-conference"]
<?php $sections = count($this->year_date_presentations) > 1; ?>
<?php foreach ($this->year_date_presentations as $year => $date_presentations) { ?>
<?php   if ($sections) { ?>[section="<?php $this->displayAttr($year) ?>" collapsible="<?php echo intval($year < date('Y')) ?>" collapsed="1"]<?php } ?>
<?php   foreach ($date_presentations as $date => $presentations) { ?>
[block="<?php $this->displayAttr($date) ?>"]
[list]
<?php     foreach ($presentations as $presentation) { ?>
[__tag="li"]<?php include dirname(__FILE__) . '/_presentation.tpl'; ?>[/__tag]
<?php     } ?>
[/list]
[/block]
<?php   } ?>
<?php   if ($sections) { ?>[/section]<?php } ?>
<?php } ?>
[/__tag]
<?php // vim: ft=php
