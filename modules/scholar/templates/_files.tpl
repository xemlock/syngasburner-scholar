<?php if ($files) { ?>
<?php   foreach ($files as $file) { ?>
[asset="<?php $this->displayAttr($file['url']) ?>" details="<?php $this->displayAttr($file['mimetype'] . ', ' . format_size($file['size'])) ?>"]<?php $this->display($file['label']) ?>[/asset]
<?php   } ?>
<?php } ?>
<?php // vim: ft=php
