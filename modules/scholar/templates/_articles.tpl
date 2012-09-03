<?php if (isset($articles)) { ?>
<?php   foreach ((array) $articles as $article) { ?>
[block="<?php $this->displayAttr($article['year']) ?>"]<?php include dirname(__FILE__) . '/_article.tpl' ?>[/block]
<?php   } ?>
<?php } ?>
<?php // vim: ft=php
