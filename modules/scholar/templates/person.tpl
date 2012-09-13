[nonl2br]
[__image]<?php $this->display($this->person['image_id']) ?>[/__image]
[__tag="div" class="scholar-person"]
<?php if ($this->articles) { ?>
[section][t]Publications[/t][/section]
<?php   $articles = $this->articles;
        include dirname(__FILE__) . '/_articles.tpl' ?>
<?php } ?>

<?php if ($this->conferences) { ?>
[section][t]Conferences, seminars, workshops[/t][/section]
<?php   foreach ($this->conferences as $conference) { ?>
[block="<?php $this->displayAttr($conference['date_span']) ?>"]
<?php     include dirname(__FILE__) . '/_conference.tpl'; ?>
  [list]
<?php     foreach ($conference['presentations'] as $presentation) { ?>
    [__tag="li"]<?php include dirname(__FILE__) . '/_presentation.tpl'; ?>[/__tag]  
<?php	  } ?>
  [/list]
[/block]
<?php   } ?>
<?php } ?>
[/__tag]
[/nonl2br]
<?php // vim: ft=php
