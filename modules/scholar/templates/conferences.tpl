[__tag="div" class="scholar-conferences"]
<?php $sections = count($this->year_conferences) > 1; ?>
<?php foreach($this->year_conferences as $year => $conferences) { ?>
<?php   if ($sections) { ?>
[section="<?php $this->displayAttr($year) ?>" collapsible="<?php echo intval($year < date('Y')) ?>" collapsed="1"]
<?php   } ?>

<?php   foreach ($conferences as $conference) { ?>
[block="<?php $this->displayAttr($conference['date_span']) ?>"]
<?php     include dirname(__FILE__) . '/_conference.tpl'; ?>
  [list]
<?php     foreach ($conference['presentations'] as $presentation) { ?>
    [__tag="li"]<?php include dirname(__FILE__) . '/_presentation.tpl'; ?>[/__tag]  
<?php	  } ?>
  [/list]
[/block]
<?php   } ?>

<?php   if ($sections) { ?>
[/section]
<?php   } ?>
<?php } ?>
[/__tag]
<?php // vim: ft=php
