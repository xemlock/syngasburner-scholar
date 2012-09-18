[nonl2br]
[__tag="div" class="scholar-publications"]

[section][t]Reviewed papers[/t][/section]
<?php foreach ($this->articles as $article) { ?>
[entry="<?php $this->displayAttr($article['year']) ?>"]<?php include dirname(__FILE__) . '/_article.tpl' ?>[/entry]
<?php } ?>

<?php foreach ($this->journal_articles as $category => $journals) { ?>
[section]<?php $this->display($category) ?>[/section]
<?php   foreach ($journals as $journal) { ?>
[entry="<?php $this->displayAttr($journal['year']) ?>"]
[__tag="div" class="journal" id="journal-<?php $this->displayAttr($journal['id']) ?>"]
  [__tag="div" class="journal-heading"]
    [__tag="cite" class="title"][url="<?php $this->displayAttr($journal['url']) ?>"]<?php $this->display($journal['title']) ?>[/url][/__tag]<?php $this->display($journal['bib_details']) ?>
  [/__tag]
<?php     if ($journal['articles']) { ?>
  [__tag="div" class="journal-articles"]
    [__tag="ul" class="components"]
<?php     foreach ($journal['articles'] as $article) { ?>
      [__tag="li"]<?php include dirname(__FILE__) . '/_article.tpl'; ?>[/__tag]  
<?php	  } ?>
    [/__tag]
  [/__tag]
<?php     } ?>
[/__tag]
[/entry]
<?php   } ?>
<?php } ?>
[/__tag]
[/nonl2br]<?php // vim: ft=php
