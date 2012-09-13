<?php if (isset($articles)) { ?>
<?php   foreach ((array) $articles as $article) { ?>
[entry="<?php $this->displayAttr($article['year']) ?>"]<?php include dirname(__FILE__) . '/_article.tpl' ?>[/entry]
<?php   } ?>
<?php } ?>
<?php // vim: ft=php
