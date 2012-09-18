[nonl2br]
[__tag="div" class="scholar-conferences"]
<?php $sections = count($this->year_conferences) > 1; 
      $first = true; ?>
<?php foreach($this->year_conferences as $year => $conferences) { ?>
<?php   if ($sections) {
          if ($first) { $first = false; ?>[collapsible="0" title="<?php $this->displayAttr($year) ?>"]<?php }
          else { ?>[collapsible collapsed="1" title="<?php $this->displayAttr($year) ?>"]<?php }
        } ?>
<?php   foreach ($conferences as $conference) { ?>
[entry date="<?php $this->displayAttr($conference['start_date'] . '/' . $conference['end_date']) ?>"]
<?php     include dirname(__FILE__) . '/_conference.tpl'; ?>
[/entry]
<?php   } ?>
<?php   if ($sections) { ?>[/collapsible]<?php } ?>
<?php } ?>
[/__tag]
[/nonl2br]<?php // vim: ft=php
