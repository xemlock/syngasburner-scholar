<?php if (isset($conference)) { ?>
[__tag="div" class="conference" id="conference-<?php $this->displayAttr($conference['id']) ?>"]
  [__tag="div" class="conference-heading"]
    [__tag="cite" class="title"][url="<?php $this->displayAttr($conference['url']) ?>"]<?php $this->display($conference['title']) ?>[/url][/__tag]<?php
          if ($conference['suppinfo']) { ?>, <?php $this->display($conference['suppinfo']) ?><?php }
          if ($conference['locality'] || $conference['country']) { ?>, [__tag="span" class="adr"]<?php
              if ($conference['locality']) {
                  ?>[__tag="span" class="locality"]<?php $this->display($conference['locality']) ?>[/__tag]<?php
                  if ($conference['country']) { ?>, <?php }
              }
              if ($conference['country']) {
                  ?>[__tag="span" class="country-name" title="<?php $this->displayAttr($conference['country']) ?>"]<?php $this->display($conference['country_name']) ?>[/__tag]<?php
              } ?>
    [/__tag]<?php } ?>
  [/__tag]
<?php   if (isset($conference['presentations']) && $conference['presentations']) { ?>
  [__tag="div" class="conference-presentations"]<?php
          $presentations = $conference['presentations'];
          include dirname(__FILE__) . '/_presentations.tpl';
?>[/__tag]
<?php   } ?>
[/__tag]
<?php } ?>
<?php // vim: ft=php
