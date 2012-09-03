<?php if (isset($presentations) && count($presentations)) { ?>
[list]
<?php     foreach ($presentations as $presentation) { ?>
  [__tag="li"]<?php include dirname(__FILE__) . '/_presentation.tpl'; ?>[/__tag]
<?php	  } ?>
[/list]
<?php } ?>
<?php // vim: ft=php
