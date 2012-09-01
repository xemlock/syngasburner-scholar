<?php if (isset($articles)) { ?>
<?php   foreach ((array) $articles as $article) { ?>
[block="<?php $this->displayAttr($article['year']) ?>"]
  [__tag="div" class="hCite article"]
<?php     if ($article['authors']) { ?>
    [__tag="span" class="authors"]
<?php       $authors = $article['authors'];
            include dirname(__FILE__) . '/_authors.tpl'; ?>
    [/__tag]
    ([__tag="span" class="dtstart"]<?php $this->display($article['year']) ?>[/__tag]):
<?php     } ?>
    [__tag="cite" class="title"][url="<?php $this->displayAttr($article['url']) ?>"]<?php $this->display($article['title']) ?>[/url][/__tag]<?php
          if ($article['parent_title']) { ?>, [__tag="span" class="fn journal"][url="<?php $this->display($article['parent_url']) ?>"]<?php $this->display($article['parent_title']) ?>[/url][/__tag]<?php 
          } ?><?php $this->display($article['bib_details']) ?>
  [/__tag]

<?php     if ($article['files']) { ?>
  [__tag="div" class="files"]<?php
            $files = $article['files'];
            include dirname(__FILE__) . '/_files.tpl'; ?>[/__tag]
<?php     } ?>
[/block]
<?php   } ?>
<?php } ?>
<?php // vim: ft=php
