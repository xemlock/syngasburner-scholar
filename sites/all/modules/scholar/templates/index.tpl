<div class="scholar-index">
<fieldset class="scholar collapsible">
  <legend><?php echo t('Publications') ?></legend>
  <table>
   <tr>
    <td>
     <h3><?php echo t('Articles') ?></h3>
     <div class="help"><?php echo t('Articles published in scientific journals, unpublished articles, book chapters.') ?></div>
     <?php echo $this->articles ?>
    </td>
    <td><h3><?php echo t('Journals') ?></h3>
     <div class="help"><?php echo t('Scientific journals, serial publications, books and other non-serial publications.') ?></div>
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
     <div class="help"><?php echo t('Scientific conferences or meetings, seminars or lecture cycles.') ?></div>
     <?php echo $this->conferences ?>
    </td>
    <td><h3><?php echo t('Presentations') ?></h3>
     <div class="help"><?php echo t('Presentations and lectures given during scientific conferences or seminars.') ?></div>
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
     <div class="help"><?php echo t('Scientific trainings or courses.') ?></div>
     <?php echo $this->trainings ?>
    </td>
    <td><h3><?php echo t('Classes') ?></h3>
     <div class="help"><?php echo t('Training classes, such as lectures or exercises.') ?></div>
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
     <div class="help"><?php echo t('Participants in scientific activities.') ?></div>
     <?php echo $this->people ?>
    </td>
    <td><h3><?php echo t('Files') ?></h3>
     <div class="help"><?php echo t('Documents referenced by scientific activity entries.') ?></div>
     <?php echo $this->files ?>
    </td>
   </tr>
  </table>
</fieldset>

<fieldset class="scholar collapsible">
  <legend><?php echo t('Module') ?></legend>
   <table>
    <tr>
     <td><h3><?php echo t('Pages') ?></h3>
      <div class="help">Special pages for displaying scientific activity reports, such as list of publications or conference attendance.</div>
      <ul>
       <li><?php echo scholar_oplink(t('Pages'), 'pages') ?></li>
      </ul>
     </td>
     <td><h3><?php echo t('Settings') ?></h3>
      <div class="help">Module configuration and overview.</div>
      <ul>
       <li><?php echo scholar_oplink(t('Settings'), 'settings') ?></li>
       <li><?php echo scholar_oplink(t('Database schema'), 'settings', '/schema') ?></li>
       <li><?php echo t('Version') ?>: <?php echo SCHOLAR_VERSION; if (SCHOLAR_REVISION) echo ' (', SCHOLAR_REVISION, ')'; ?></li>
      </ul>
     </td>
    </tr>
   </table>
</fieldset>
</div>
<?php // vim: ft=php
