<?php if (isset($conference)) { ?>
  [__tag="div" class="hCite conference"]
    [__tag="span" class="dtstart" title="<?php $this->displayAttr($conference['start_date']) ?>"][/__tag]
<?php     if ($conference['end_date']) { ?>
    [__tag="span" class="dtend" title="<?php $this->displayAttr($conference['end_date']) ?>"][/__tag]
<?php     } ?>
    [__tag="cite" class="title"][url="<?php $this->displayAttr($conference['url']) ?>"]<?php $this->display($conference['title']) ?>[/url][/__tag]<?php 
          if ($conference['suppinfo']) { ?>, <?php $this->display($conference['suppinfo']) ?><?php }
          if ($conference['locality']) { ?>, [__tag="span" class="adr"][__tag="span" class="locality"]<?php $this->display($conference['locality']) ?>[/__tag]<?php          
            if ($conference['country']) { ?>, [__tag="span" class="country-name" title="<?php $this->displayAttr($conference['country']) ?>"]<?php $this->display($conference['country_name']) ?>[/__tag]<?php } ?>[/__tag]
<?php     }  ?>
  [/__tag]
<?php } ?>
<?php // vim: ft=php
