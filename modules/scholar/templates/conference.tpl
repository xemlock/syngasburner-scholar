[__tag="div" class="scholar-conference"]
<?php $sections = count($this->year_date_presentations) > 1; ?>
<?php foreach ($this->year_date_presentations as $year => $date_presentations) { ?>
<?php   if ($sections) { ?>[section="<?php $this->displayAttr($year) ?>" collapsible="<?php echo intval($year < date('Y')) ?>" collapsed="1"]<?php } ?>
<?php   foreach ($date_presentations as $date => $presentations) { ?>
[block="<?php $this->displayAttr($date) ?>"]
[list]
<?php     foreach ($presentations as $presentation) { ?>
[__tag="li"]
  [__tag="div" class="hCite"]
<?php       if ($presentation->start_date) { ?>[__tag="span" class="dtstart" title="<?php $this->displayAttr($presentation->start_date) ?>"][/__tag]<?php } ?>
    [__tag="span" class="authors"]<?php
            $authors = $presentation->authors;
            include dirname(__FILE__) . '/_authors.tpl' ?>[/__tag]:
    [__tag="cite" class="title"][url="<?php $this->displayAttr($presentation->url) ?>"]<?php $this->display($presentation->title) ?>[/url][/__tag]
<?php       if ($presentation->category_name) { ?> (<?php $this->display($presentation->category_name) ?>)<?php } ?>
  [/__tag]
<?php       if ($presentation->suppinfo) { ?>
  [__tag="div" class="description"]<?php $this->display($presentation->suppinfo) ?>[/__tag]<?php
            } ?>
<?php       if ($presentation->files) { ?>
  [__tag="div" class="files"]<?php
                $files = $presentation->files;
                include dirname(__FILE__) . '/_files.tpl' ?>[/__tag]<?php
            } ?>
[/__tag]
<?php     } ?>
[/list]
[/block]
<?php   } ?>
<?php   if ($sections) { ?>[/section]<?php } ?>
<?php } ?>
[/__tag]
<?php // vim: ft=php
