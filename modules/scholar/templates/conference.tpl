[__tag="div"]
<?php $sections = count($this->year_date_presentations) > 1; ?>
<?php foreach ($this->year_date_presentations as $year => $date_presentations) { ?>
<?php   if ($sections) { ?>[section="<?php echo $year ?>"]<?php } ?>
<?php   foreach ($date_presentations as $date => $presentations) { ?>
[block="<?php echo $date ?>"]
[list]
<?php     foreach ($presentations as $presentation) { ?>
[__tag="li"]
  [__tag="div" class="hCite"]
<?php       if ($presentation->has('date_start')) { ?>[__tag="span" class="dtstart" title="<?php echo $presentation->date_start ?>"][/__tag]<?php } ?>
    [__tag="span" class="authors"]
<?php       foreach ($presentation->authors as $author) { ?>
<?php         if (!$author->first) { ?>, <?php } ?>
      [__tag="span" class="author vcard fn"][url="<?php echo $author->url ?>"]<?php echo $author->first_name, ' ', $author->last_name ?>[/url][/__tag]<?php
              if ($author->last) { ?>: <?php } ?>
<?php       } ?>
    [/__tag]
    [__tag="cite" class="title"][url="<?php echo $presentation->url ?>"]<?php echo $presentation->title ?>[/url][/__tag]<?php
            if ($presentation->has('category_name')) { ?> (<?php echo $presentation->category_name ?>)<?php } ?>
  [/__tag]
<?php       if ($presentation->has('suppinfo')) { ?>
  [__tag="div" class="description"]<?php echo $presentation->suppinfo ?>[/__tag]
<?php } ?>
[/__tag]
<?php     } ?>
[/list]
[/block]
<?php   } ?>
<?php   if ($sections) { ?>[/section]<?php } ?>
<?php } ?>
[/__tag]
