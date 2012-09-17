<?php if (isset($presentations) && count($presentations)) { ?>
[__tag="ul" class="components"]<?php
        foreach ($presentations as $presentation) { ?>
[__tag="li"]<?php include dirname(__FILE__) . '/_presentation.tpl'; ?>[/__tag]<?php
	    } ?>
[/__tag]<?php 
      } ?>
<?php // vim: ft=php
