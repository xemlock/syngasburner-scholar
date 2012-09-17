[nonl2br]
[__image]<?php $this->display($this->person['image_id']) ?>[/__image]
[__tag="div" class="scholar-person"]
<?php if ($this->articles) { ?>
[section][t]Publications[/t][/section]
<?php   foreach ($this->articles as $article) { ?>
[entry="<?php $this->displayAttr($article['year']) ?>"]<?php include dirname(__FILE__) . '/_article.tpl' ?>[/entry]
<?php   } ?>
<?php } ?>

<?php if ($this->conferences) { ?>
[section][t]Conferences, seminars, workshops[/t][/section]
<?php   foreach ($this->conferences as $conference) { ?>
[entry date="<?php $this->displayAttr($conference['start_date'] . '/' . $conference['end_date']) ?>"]
<?php     include dirname(__FILE__) . '/_conference.tpl'; ?>
[/entry]
<?php   } ?>
<?php } ?>
[/__tag]
[/nonl2br]
<?php // vim: ft=php
