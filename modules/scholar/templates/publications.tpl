[section="<?php echo t('Reviewed papers') ?>" collapsible=0]
<?php foreach ($this->articles as $article) { ?>
[block="<?php echo $article->year ?>"]
  [box]
<?php   foreach ($article->authors as $author) { ?>
<?php     echo (!$author->first ? ', ' : ''), '[url="', $author->url, '" target="_self"]', $author->first_name, ' ', $author->last_name, '[/url]' ?>
<?php   } ?>
    [i][url="<?php echo $article->url ?>"]<?php echo $article->title ?>[/url][/i]<?php
        if ($article->has('parent_id')) { ?>, [url="<?php echo $article->parent_url ?>"]<?php echo $article->parent_title ?>[/url]<?php } ?><?php
        echo $article->details ?>
  [/box]
  [box][color=orange]\[PLIKI\][/color][/box]
[/block]
<?php } ?>
[/section]
<?php foreach ($this->book_articles as $category => $books) { ?>
[section="<?php echo $category ?>" collapsible=0]
<?php   foreach ($books as $book) { ?>
[block="<?php echo $book->year ?>"]
  [box]
    [url="<?php echo $book->url ?>"]<?php echo $book->title ?>[/url]<?php echo $book->details ?>
  [/box]
  [color=red]// potrzebny nowy tag do listy[/color]
<?php     foreach ($book->articles as $article) { ?>
  [box]
<?php   foreach ($article->authors as $author) { ?>
<?php     echo (!$author->first ? ', ' : ''), '[url="', $author->url, '" target="_self"]', $author->first_name, ' ', $author->last_name, '[/url]' ?>
<?php   } ?>:
    [i][url="<?php echo $article->url ?>"]<?php echo $article->title ?>[/url][/i]<?php echo $article->details ?>
  [/box]
  [box][color=orange]\[PLIKI\][/color][/box]
  ----
<?php     } ?>
[/block]
<?php   } ?>
[/section]
<?php } ?>
