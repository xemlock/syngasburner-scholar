<?php $sections = count($this->year_conferences) > 1; ?>
<?php foreach($this->year_conferences as $year => $conferences) { ?>
<?php   if ($sections) { ?>[section="<?php echo $year ?>" collapsible=1]<?php } ?>
<?php   foreach ($conferences as $conference) { ?>
[block="<?php echo $conference->start_date, '--', $conference->end_date ?>"]
[box][url="<?php echo $conference->url ?>"]<?php echo $conference->title ?>[/url]<?php echo $conference->locality, $conference->country ?>[/box]
[color=red]-- znowu indented block potrzebny[/color]
<?php     foreach ($conference->presentations as $presentation) { ?>
  [box]
<?php   foreach ($presentation->authors as $author) { ?>
<?php     echo (!$author->first ? ', ' : ''), $author->first_name, ' ', $author->last_name ?>
<?php   } ?>:
    [i][url="<?php echo $presentation->url ?>"]<?php echo $presentation->title ?>[/url][/i]<?php echo $presentation->has('category_name') ? ' (' . $presentation->category_name . ')' : ''; ?>.
  [/box]
<?php   if ($presentation->has('details')) { ?>[box]<?php echo $presentation->details ?>[/box]<?php } ?>
  [box][color=orange]\[PLIKI\][/color][/box]
  ----
<?php     } ?>
[/block]
<?php   } ?>
<?php   if ($sections) { ?>[/section]<?php } ?>
<?php } ?>
<?php // vim: ft=php
