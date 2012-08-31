[__tag="div" class="scholar-publications"]

[section="<?php echo t('Reviewed papers') ?>" collapsible="0"]
<?php foreach ($this->articles as $article) { ?>
[block="<?php $this->displayAttr($article['year']) ?>"]
  [__tag="div" class="hCite article"]
    [__tag="span" class="authors"]
<?php   $authors = $article['authors'];
        include dirname(__FILE__) . '/_authors.tpl'; ?>
    [/__tag]
    ([__tag="span" class="dtstart"]<?php $this->display($article['year']) ?>[/__tag]):
    [__tag="cite" class="title"][url="<?php $this->displayAttr($article['url']) ?>"]<?php $this->display($article['title']) ?>[/url][/__tag]<?php
        if ($article['parent_title']) { ?>, [__tag="span" class="fn journal"][url="<?php $this->display($article['parent_url']) ?>"]<?php $this->display($article['parent_title']) ?>[/url][/__tag]<?php 
        } ?><?php $this->display($article['bib_details']) ?>
  [/__tag]

<?php   if ($article['files']) { ?>
  [__tag="div" class="files"]<?php
          $files = $article['files'];
          include dirname(__FILE__) . '/_files.tpl'; ?>[/__tag]
<?php   } ?>
[/block]
<?php } ?>
[/section]

<?php foreach ($this->book_articles as $category => $books) { ?>
[section="<?php $this->displayAttr($category) ?>" collapsible=0]
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
