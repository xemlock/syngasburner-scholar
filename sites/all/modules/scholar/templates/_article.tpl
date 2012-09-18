<?php if (isset($article)) { ?>
[__tag="div" class="article" id="article-<?php $this->displayAttr($article['id']) ?>"]
<?php   if ($article['authors']) { ?>
[__tag="div" class="article-heading hCite"]
  [__tag="span" class="authors"]
<?php       $authors = $article['authors'];
            include dirname(__FILE__) . '/_authors.tpl'; ?>[/__tag]<?php
            if (isset($article['year']) && $article['year']) {
              ?> ([__tag="span" class="dtstart"]<?php $this->display($article['year']) ?>[/__tag])<?php
            } ?>:
<?php   } ?>
  [__tag="cite" class="title"][url="<?php $this->displayAttr($article['url']) ?>"]<?php $this->display($article['title']) ?>[/url][/__tag]<?php
        if ($article['parent_title']) { ?>, [__tag="span" class="fn journal"][url="<?php $this->display($article['parent_url']) ?>"]<?php $this->display($article['parent_title']) ?>[/url][/__tag]<?php 
        } ?><?php $this->display($article['bib_details']) ?>
[/__tag]

<?php   if (isset($article['files']) && $article['files']) { ?>
  [__tag="div" class="article-files"]<?php
            $files = $article['files'];
            include dirname(__FILE__) . '/_files.tpl'; ?>[/__tag]
<?php   } ?>

<?php   if (isset($article['suppinfo']) && $article['suppinfo']) { ?>
  [__tag="div" class="article-description"]<?php $this->display($article['suppinfo']) ?>[/__tag]
<?php   } ?>
[/__tag]
<?php } ?>
<?php // vim: ft=php
