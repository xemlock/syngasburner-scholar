[section="<?php echo t('Reviewed papers') ?>"]
<?php foreach ($this->articles as $article) { ?>
[block="<?php echo $article->year ?>"]
  [box]
<?php   foreach ($article->authors as $author) { ?>

<?php   } ?>
    [url="<?php echo $article->url ?>"]<?php echo $article->title ?>[/url]
  [/box]
[/block]
<?php } ?>
<?php var_dump($this->raw('articles')) ?>
<?php var_dump($this->raw('book_articles')) ?>
[/section]
