$(function() {

$('.scholar-attachment-manager').each(function() {
    var prefix = $(this).attr('data-name');

    var html = '<table id="temp-table"><thead><tr><th>File</th>';
    html += '<th>Label (Polish)</th>' + '<th>Label (English)</th>';    
    html += '</tr></thead>';
    html += '<tbody>';
    
    html += '<tr class="odd"><td>Plik 1</td><td><input type=text/></td><td><input type=text/></td></tr>';
    html += '<tr class="even"><td>Plik 2</td><td><input type=text/></td><td><input type=text/></td></tr>';
    html += '<tr class="odd"><td>Plik 3</td><td><input type=text/></td><td><input type=text/></td></tr>';    

    html += '</tbody>';

    $(this).html(html);

    new Drupal.tableDrag($('#temp-table')[0], {
        target: 'temp-table',
        source: 'temp-table',
        relationship: 'sibling',
        action: 'order'
    });
});

});
