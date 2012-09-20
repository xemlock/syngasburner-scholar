<div class="scholar-index">
<fieldset class="scholar collapsible">
  <legend><?php echo t('Publications') ?></legend>
  <table>
   <tr>
    <td><h3><?php echo t('Articles') ?></h3>
  <?php echo $this->articles ?>
    </td>
    <td><h3><?php echo t('Journals') ?></h3>
  <?php echo $this->journals ?>
    </td>
   </tr>
  </table>
</fieldset>

<fieldset class="scholar collapsible">
  <legend><?php echo t('Conferences, seminars, workshops') ?></legend>
  <table>
   <tr>
    <td><h3><?php echo t('Conferences') ?></h3>
  <?php echo $this->conferences ?>
    </td>
    <td><h3><?php echo t('Presentations') ?></h3>
  <?php echo $this->presentations ?>
    </td>
   </tr>
  </table>
</fieldset>

<fieldset class="scholar collapsible">
  <legend><?php echo t('Trainings and training classes') ?></legend>
  <table>
   <tr>
    <td><h3><?php echo t('Trainings') ?></h3>
  <?php echo $this->trainings ?>
    </td>
    <td><h3><?php echo t('Classes') ?></h3>
  <?php echo $this->classes ?>
    </td>
   </tr>
  </table>
</fieldset>

<fieldset class="scholar collapsible">
  <legend><?php echo t('Resources') ?></legend>
  <table>
   <tr>
    <td><h3><?php echo t('People') ?></h3>
  <?php echo $this->people ?>
    </td>
    <td><h3><?php echo t('Files') ?></h3>
  <?php echo $this->files ?>
    </td>
   </tr>
  </table>
</fieldset>

<fieldset class="scholar collapsible collapsed">
  <legend><?php echo t('Module') ?></legend>
  <?php echo l(t('Pages'), scholar_path('pages')) ?>
<?php echo scholar_oplink(t('Database schema'), 'system', '/schema'); ?>
<?php echo scholar_oplink(t('File import'), 'system', '/file-import'); ?>
<?php echo scholar_oplink(t('Setting'), 'system', '/schema'); ?>

</fieldset>
</div>
