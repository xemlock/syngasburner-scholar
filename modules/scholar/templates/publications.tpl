[__tag="div" class="scholar-publications"]

[section="<?php $this->displayAttr($this->section_title) ?>"]
<?php $articles = $this->articles;
      include dirname(__FILE__) . '/_articles.tpl' ?>
[/section]

<?php foreach ($this->journal_articles as $category => $journals) { ?>
[section="<?php $this->displayAttr($category) ?>"]
<?php   foreach ($journals as $journal) { ?>
[block="<?php $this->displayAttr($journal['year']) ?>"]
  [__tag="div" class="hCite journal"]
    [__tag="cite" class="title"][url="<?php $this->displayAttr($journal['url']) ?>"]<?php $this->display($journal['title']) ?>[/url][/__tag]<?php $this->display($journal['bib_details']) ?>
  [/__tag]
  [list]
<?php     foreach ($journal['articles'] as $article) { ?>
    [__tag="li"]
      [__tag="div" class="hCite article"]
        [__tag="span" class="authors"]
<?php       $authors = $article['authors'];
            include dirname(__FILE__) . '/_authors.tpl'; ?>[/__tag]:
        [__tag="cite" class="title"][url="<?php $this->displayAttr($article['url']) ?>"]<?php $this->display($article['title']) ?>[/url][/__tag]<?php
            $this->display($article['bib_details']) ?>
      [/__tag]
<?php       if ($article['files']) { ?>
      [__tag="div" class="files"]
<?php       $files = $article['files'];
            include dirname(__FILE__) . '/_files.tpl'; ?>
      [/__tag]
<?php       } ?>
    [/__tag]
  [/list]
<?php     } ?>
[/block]
<?php   } ?>
[/section]
<?php } ?>

[/__tag]
<?php // vim: ft=php
