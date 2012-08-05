/**
 * @fileOverview Biblioteka funkcji wykorzystycznych przez moduł Scholar.
 * @author xemlock
 */

/**
 * @namespace Przestrzeń nazw dla funkcji modułu.
 */
var Scholar = {
    /**
     * Zbiór identyfikatorów.
     * @constructor
     */
    idSet: function() {
        var _items = {};
        var _listeners = [];

        /**
         * Czy podany identyfikator jest obecny w zbiorze.
         * @returns {boolean}
         */
        this.has = function(id) {
            return typeof _items['_' + id] !== 'undefined';
        }

        /**
         * Dodaje identyfikator do zbioru.
         * @param id                    identyfikator
         * @param [value]               opcjonalna wartość powiązana 
         *                              z podanym identyfikatorem
         * @returns {idSet}             zbiór na którym wywołano metodę
         */
        this.add = function(id, value) {
            if (typeof value === 'undefined') {
                value = true;
            }
            _items['_' + id] = value;

            // powiadom sluchaczy o dodaniu nowego identyfikatora
            this.notify('onInsert', id);

            return this;
        }

        /**
         * Usuwa identyfikator ze zbioru.
         * @param id                    identyfikator
         * @returns {boolean}           czy identyfikator został usunięty
         */
        this.del = function(id) {
            var key = '_' + id;

            if (key in _items) {
                delete _items[key];
                this.notify('onDelete', id);
                return true;
            }

            return false;
        }

        /**
         * Iteruje podaną funkcję po wszystkich identyfikatorach w zbiorze.
         * Argumentem funkcji jest tekstowa reprezentacja identyfikatora. 
         * Zwrócenie przez funkcję wartości false przerywa iterację.
         * @param {function} callback   funkcja wywoływana dla każdego 
         *                              identyfikatora.
         * @returns {idSet}             zbiór na którym wywołano metodę
         */
        this.each = function(callback) {
            var id, key;

            for (key in _items) {
                id = key.substr(1);
                if (callback.call(id, id) === false) {
                    break;
                }
            }

            return this;
        }

        /**
         * Informuje słuchaczy o zajściu zdarzenia.
         * @param {string} event        nazwa zdarzenia
         * @returns {idSet}             zbiór na którym wywołano metodę
         */
        this.notify = function(event)
        {
            var args = Array.prototype.slice.call(arguments, 1);

            for (var i = 0; i < _listeners.length; ++i) {
                var listener = _listeners[i];
                if (listener && typeof listener[event] === 'function') {
                    listener[event].apply(listener, args);
                }
            }

            return this;
        }

        /**
         * Dodaje słuchacza zmian w zbiorze. Obsługiwane zdarzenia to
         * onInsert i onDelete, przyjmujące jako argument identyfikator.
         * @param {object} listener     słuchacz
         * @returns {number}            wewnętrzny numer nadany słuchaczowi
         */
        this.addListener = function(listener) {
            // upewnij sie, ze nie ma duplikatow
            for (var i = 0; i < _listeners; ++i) {
                if (_listeners[i] === listener) {
                    return i;
                }
            }

            var index = _listeners.length;
            _listeners[index] = listener;

            return index;
        }

        /**
         * Usuwa słuchacza z listy.
         * @param {object|number} listener słuchacz lub jego numer na liście
         * @returns {boolean}           czy słuchacz został usunięty
         */
        this.removeListener = function(listener) {
            var removed = false;

            if (typeof listener === 'number') {
                if (typeof _listeners[listener] !== 'undefined') {
                    delete _listeners[listener];
                    removed = true;
                }
            } else if (listener) {
                for (var i = 0; i < _listeners.length; ++i) {
                    if (_listeners[i] === listener) {
                        delete _listeners[i];
                        removed = true;
                        break;
                    }
                }
            }

            return removed;
        }
    },
    /**
     * Umieszcza w podanym selektorze widget zarządzający załącznikami.
     * @constructor
     * @param {string} selector         selektor jQuery wskazujacy element,
     *                                  w którym ma zostać umieszczony widget
     */
    attachmentManager: function(selector) {

                       
    }
};


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
