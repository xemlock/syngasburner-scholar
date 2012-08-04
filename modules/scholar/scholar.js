$(function() {

$('.scholar-attachment-manager').each(function() {
    var prefix = $(this).attr('data-name');

    var html = '<table id="temp-table" class="sticky-enabled"><thead><tr><th>File</th><th>Size</th>';
    html += '<th>Label (Polish)</th>' + '<th>Label (English)</th>';
    html += '</tr></thead>';
    html += '<tbody>';
    
    html += '<tr class="draggable odd"><td>Plik 1</td><td>22 KB</td><td><img style="display:inline" title="Polish" alt="" src="/ventures/i/flags/pl.png"> <input type=text/></td><td><img style="display:inline" title="English" alt="" src="/ventures/i/flags/en.png"> <input type=text/><input class="weight" type="hidden" value=0 /></td></tr>';
    html += '<tr class="draggable even"><td>Plik 2</td><td>333 KB</td><td><img style="display:inline" title="Polish" alt="" src="/ventures/i/flags/pl.png"> <input type=text/></td><td><img style="display:inline" title="English" alt="" src="/ventures/i/flags/en.png"> <input type=text/><input class="weight" type="hidden" value=0 /></td></tr>';
    html += '<tr class="draggable odd"><td>Plik 3</td><td>4.44 MB</td><td><img style="display:inline" title="Polish" alt="" src="/ventures/i/flags/pl.png"> <input type=text/></td><td><img style="display:inline" title="English" alt="" src="/ventures/i/flags/en.png"> <input type=text/><input class="weight" type="hidden" value=0 /></td></tr>';

    html += '</tbody>';

    $(this).html(html);


    var td = new Drupal.tableDrag($('#temp-table')[0], {weight: [{
        target: 'weight',
        source: 'weight',
        relationship: 'sibling',
        action: 'order',
        hidden: false,
        limit: 0
    }] });
    console.log(td);
});

});
