[__tag="div" class="scholar-person"]

[section="<?php $this->displayAttr($this->publications_title) ?>"]
<?php $articles = $this->articles;
      include dirname(__FILE__) . '/_articles.tpl' ?>
[/section]

[section="<?php $this->displayAttr($this->conferences_title) ?>"]
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
[/section]

[/__tag]
<?php // vim: ft=php
