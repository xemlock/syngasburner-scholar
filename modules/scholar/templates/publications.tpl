[__tag="div" class="scholar-publications"]

[section="<?php $this->displayAttr($this->section_title) ?>"]
<?php $articles = $this->articles;
      include dirname(__FILE__) . '/_articles.tpl' ?>
[/section]

<?php foreach ($this->book_articles as $category => $books) { ?>
[section="<?php $this->displayAttr($category) ?>"]
<?php   foreach ($books as $book) { ?>
[block="<?php $this->displayAttr($book['year']) ?>"]
  [__tag="div" class="hCite book"]
    [__tag="cite" class="title"][url="<?php $this->displayAttr($book['url']) ?>"]<?php $this->display($book['title']) ?>[/url][/__tag]<?php $this->display($book['bib_details']) ?>
  [/__tag]
  [list]
<?php     foreach ($book['articles'] as $article) { ?>
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
