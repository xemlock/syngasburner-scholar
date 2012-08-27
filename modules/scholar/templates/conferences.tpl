<h2>Conferences</h2>
<?php foreach($this->conferences as $conference) {
    echo '[';
    foreach ($conference as $key => $value) {
        echo "$key => $value\n";
    }
    echo '][br]';
}?>
<?php echo $this->conferences ?>
<?php exit ?>
