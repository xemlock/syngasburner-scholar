[__tag="div" class="scholar-conferences"]
<?php $sections = count($this->year_conferences) > 1; ?>
<?php foreach($this->year_conferences as $year => $conferences) { ?>
<?php   if ($sections) { ?>
[section="<?php $this->displayAttr($year) ?>" collapsible="<?php echo intval($year < date('Y')) ?>" collapsed="1"]
<?php   } ?>

<?php   foreach ($conferences as $conference) { ?>
[block="<?php $this->displayAttr($conference['date_span']) ?>"]

  [__tag="div" class="hCite conference"]
    [__tag="span" class="dtstart" title="<?php $this->displayAttr($conference['start_date']) ?>"][/__tag]
<?php     if ($conference['end_date']) { ?>
    [__tag="span" class="dtend" title="<?php $this->displayAttr($conference['end_date']) ?>"][/__tag]
<?php     } ?>
    [__tag="cite" class="title"][url="<?php $this->displayAttr($conference['url']) ?>"]<?php $this->display($conference['title']) ?>[/url][/__tag]<?php 
          if ($conference['suppinfo']) { ?>, <?php $this->display($conference['suppinfo']) ?><?php }
          if ($conference['locality']) { ?>, [__tag="span" class="adr"][__tag="span" class="locality"]<?php $this->display($conference['locality']) ?>[/__tag]<?php          
            if ($conference['country']) { ?>, [__tag="span" class="country-name" title="<?php $this->displayAttr($conference['country']) ?>"]<?php $this->display($conference['country_name']) ?>[/__tag]<?php } ?>[/__tag]
<?php     }  ?>
  [/__tag]

  [list]
<?php     foreach ($conference['presentations'] as $presentation) { ?>
    [__tag="li"]  
      [__tag="div" class="hCite presentation"]
        [__tag="span" class="authors"]
<?php       $authors = $presentation['authors'];
            include dirname(__FILE__) . '/_authors.tpl'; ?>[/__tag]:
        [__tag="cite" class="title"][url="<?php $this->displayAttr($presentation['url']) ?>"]<?php $this->display($presentation['title']) ?>[/url][/__tag]
<?php       if ($presentation['category_name']) { ?> (<?php $this->display($presentation['category_name']) ?>)<?php } ?>
      [/__tag]

<?php       if ($presentation['suppinfo']) { ?>
      [__tag="div" class="description"]<?php $this->display($presentation['suppinfo']) ?>[/__tag]
<?php       } ?>

<?php       if ($presentation['files']) { ?>
      [__tag="div" class="files"]<?php
              $files = $presentation['files'];
              include dirname(__FILE__) . '/_files.tpl'; ?>[/__tag]
<?php       } ?>
    [/__tag]
<?php	 } ?>
  [/list]

[/block]
<?php     } ?>

<?php   if ($sections) { ?>
[/section]
<?php   } ?>
<?php } ?>
[/__tag]
<?php // vim: ft=php
