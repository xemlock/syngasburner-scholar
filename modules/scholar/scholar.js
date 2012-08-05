/**
 * @fileOverview Biblioteka funkcji wykorzystycznych przez moduł Scholar.
 * @author xemlock
 * @version 2012-08-05
 */

/**
 * @namespace Przestrzeń nazw dla funkcji modułu.
 */
var Scholar = {
    /**
     * Zbiór identyfikatorów.
     * @constructor
     */
    idSet: function() { // {{{
        var _items = {},
            _size  = 0,
            _listeners = [];

        /**
         * Zwraca liczbę elementów w zbiorze.
         * @returns {number}
         */
        this.size = function() {
            return _size;
        }

        /**
         * Czy podany identyfikator jest obecny w zbiorze.
         * @returns {boolean}
         */
        this.has = function(id) {
            return typeof _items['_' + id] !== 'undefined';
        }

        /**
         * Dodaje identyfikator do zbioru. Ustawienie nowej wartości
         * dla identyfikatora już obecnego w zbiorze nie wywołuje
         * zdarzenia onInsert.
         * @param id                    identyfikator
         * @param [value]               opcjonalna wartość powiązana 
         *                              z podanym identyfikatorem
         * @returns {idSet}             zbiór na którym wywołano metodę
         */
        this.add = function(id, value) {
            if (typeof value === 'undefined') {
                value = true;
            }

            var key = '_' + id,
                added = typeof _items[key] === 'undefined';

            _items[key] = value;

            if (added) {
                ++_size;

                // powiadom sluchaczy o dodaniu nowego identyfikatora
                this.notify('onInsert', id);
            }

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
                --_size;
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
                if (callback.apply(this, [id, _items[key]]) === false) {
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
    }, // }}}
    /**
     * Umieszcza w podanym selektorze widget zarządzający załącznikami.
     * @constructor
     * @param {string} selector         selektor jQuery wskazujacy element,
     *                                  w którym ma zostać umieszczony widget
     */
    attachmentManager: function(selector, settings) {
        console.log(settings);
        var self = this,
            j = $(selector)
            .addClass('scholar-attachment-manager')
            .html('<div class="table-wrapper"></div><div class="buttons-wrapper"></div>');

        var uniq = String(Math.random()).substr(2);
        var idset = new Scholar.idSet;
        var idsetId = '_attachmentManager' + uniq;
        window[idsetId] = idset;

        var btnSelect = $('<button/>')
            .html('Wybierz plik')
            .click(function() {
                Scholar.modal.open({
                    width: 480,
                    height: 240,
                    iframe: {
                        url: settings.urlFileSelect + '#!' + idsetId,
                        expand: false
                    }
                });
                return false;
            });
        var btnUpload = $('<button/>')
            .html('Wgraj plik')
            .click(function() {
                Scholar.modal.open({
                    width: 480,
                    height: 240,
                    iframe: {
                        url: settings.urlFileUpload + '#!' + uniq,
                        expand: false
                    }
                });
                return false;
            });

        j.children('.buttons-wrapper').append(btnSelect).append(btnUpload);
        this.redraw = function() {
            
        
        }              
    },

    /**
     * Kazdy item musi miec ustawione .id
     * filterSelector -> element z ktorego bedzie brana wartosc do filtrowania
     * filterSubject -> filtrowanie po tej wlasciwosci itema
     * itemSelector -> selector {id} placeholder dla identyfikatora
     * @constructor
     */
    itemSelector: function(items, options) {              
        /** 
         * Ukrywa te elementy listy, które nie zawierają ciągu znaków
         * podanego w wybranym polu tekstowym.
         */
        this.filter = function() {
            var filter = $(options.filterSelector);
            if (arguments.length > 0) {
                filter.value = arguments[0];
            }

            var needle = filter.value.toLowerCase();
            for (var i = 0; i < items.length; ++i) {
                var item = items[i],
                    elem = $(options.itemSelector.replace(/\{id\}/g, item.id)),
                    haystack = String(item[options.filterSubject]).toLowerCase();
                elem.css('display', haystack.indexOf(needle) != -1 ? '' : 'none');
            }
        }

        // okienko (jezeli strona otwarta za pomoca window.open) lub strona 
        // (jezeli otwarta w IFRAME)
        var trigger = window.opener ? window.opener : (window.parent !== window ? window.parent : null);

        // idset umieszczony w oknie otwierajacym przechowujacy wybrane elementy
        var storage = trigger ? trigger[window.location.hash.substr(2)] : null;

        if (storage instanceof Scholar.idSet) {
            storage.addListener({
                onAdd: function(id) {
                    if (!document) {
                        // jezeli okienko z itemSelectorem zostalo zamkniete,
                        // nie ma czego aktualizowac
                        return;
                    }
                    var elem = $(options.itemSelector.replace(/\{id\}/g, id));
                    elem.html(elem.html() + ' (SELECTED)');
                },
                onDelete: function(id) {
                    if (!document) {
                        return;
                    }

                    var elem = $(options.itemSelector.replace(/\{id\}/g, id));
                    elem.html(elem.html().replace(/ \(SELECTED\)/, ''));
                }
            });

            // zaznacz elementy juz obecne w zbiorze jako wybrane
            storage.each(function (id, value) {
                var elem = $(options.itemSelector.replace(/\{id\}/g, id));
                elem.html(elem.html() + ' (SELECTED)');
            });

            // podepnij dodawanie / usuwanie elementow za pomoca klikniecia
            $(items).each(function (key, item) {
                var elem = $(options.itemSelector.replace(/\{id\}/g, item.id));
                elem.click(function() {
                    storage[storage.has(item.id) ? 'del' : 'add'](item.id);
                });
            });
        }              
    },
    /**
     * Okienko.
     * @constructor
     * @param {jQuery} $                Funkcja jQuery
     */
    dialog: function($) { // {{{
        var self = this,
            _modal, _overlay,
            jStatus, jButtons;

        function _getStatusBar() {
            if (!jStatus) {
                jStatus = $('<div class="status-bar"><div class="status"/></div>').appendTo(_modal);
            }
            return jStatus;
        }

        function _getButtons() {
            if (!jButtons) {
                jButtons = $('<div class="buttons"/>').appendTo(_getStatusBar());
            }
            return jButtons;
        }

        function _centerModal(animate) {
            if (_modal) {
                var d = _modal.css('display');
                _modal.css('display', 'block');
                _modal.css('position', 'absolute');

                var st = $(window).scrollTop(),
                    sl = $(window).scrollLeft(),
                    // nie zezwalaj na ujemne wspolrzedne wzgledem scrollTop i scrollLeft
                    t = Math.max(st, (($(window).height() - _modal.outerHeight()) / 2) + st),
                    l = Math.max(sl, (($(window).width() - _modal.outerWidth()) / 2) + sl);

                // sprawdzenie czy animate jest wartoscia logiczna jest konieczne,
                // poniewaz ta funkcja moze byc przekazana jako handler zdarzen
                // (ktorej pierwszym argumentem jest event)
                if (typeof animate === 'boolean' && animate) {
                    _modal.animate({top:t, left:l});
                } else {
                    _modal.css({top:t, left:l});
                }
            }
        }

        /**
         * Zwraca przycisk o podanym identyfikatorze
         * @returns jQuery              element DOM przycisku
         */
        this.button = function(id) {
            return _getButtons().children('#button-' + id);
        }

        // Przycisk mozna zdefinowac jako: {label: string, type: string, click: function},
        // mozna uzyc rowniez predefiniowanej wartosci 'cancel', ktora tworzy
        // przycisk 'Anuluj' zamykajacy okienko.
        // Funkcja przekazana w .click bedzie miala kontekst elementu przycisku
        // (DIV.dialog-button), wzbogaconego o dodatkowe pole .parentModal, ktore daje
        // dostep do okienka, do ktorego przycisk jest podpiety.
        // Ustawienie klasy 'disabled' na przycisku sprawia, ze metoda .click nie
        // bedzie uruchamiana.
        /**
         * Ustawia przyciski w okienku modalnym. 
         * @param {object} options
         * @return {jQuery}             element otaczający przyciski
         */
        this.buttons = function(options) {
            var container = _getButtons();

            if (typeof options !== 'undefined') {
                container.empty();
                $.each(options, function(id, item) {
                    var btn = $('<div/>')
                              .attr({id: 'button-' + id, role: 'button'})
                              .addClass('dialog-button');

                    if (item === 'cancel') {
                        // predefiniowany przycisk
                        btn.html('Anuluj')
                           .click(function() { self.close() })
                           .addClass('cancel');
                    } else {
                        btn.html(item.label)
                           .click(function() {
                                if ($(this).hasClass('disabled')) {
                                    return false;
                                }
                                if (typeof item.click === 'function') {
                                    return item.click.apply(this);
                                }
                            });
                        if (item.type) {
                            btn.addClass(item.type);
                        }
                    }

                    btn.appendTo(container);

                    // jQuery nie radzi sobie z przekazywaniem danych miedzy
                    // okienkami za pomoca .data()
                    btn.get(0).parentModal = self;
                });
                return self;
            }

            return $(container);
        }

        /**
         * Ustawia lub zwraca tekst w polu statusu okienka.
         * @param {string} [text]
         * @return {string|Scholar.modal}
         */
        this.status = function(text) {
            var j = _getStatusBar().children('.status');

            if (typeof text !== 'undefined') {
                j.text(text);
                return self;
            }

            return j.text(); 
        }

        /**
         * Otwiera okienko modalne.
         * @param {object} options
         */
        this.open = function(options) {
            

            options = $.extend({}, {
                id:      'scholar-modal',
                title:   '',
                content: '',
                width:   320,
                height:  240,
                overlayColor: '#fff',
                overlayOpacity: 0.75,
            }, options);

            _modal = $('#' + options.id);
            if (!_modal.length) {
                _modal = $('<div class="dialog"/>').attr('id', options.id).appendTo('body');
            }

            _modal.css('display', 'none').html(
                '<div class="title-bar">' +
                '<div class="close" title="Zamknij" role="button">&times;</div>' +
                '<div class="title">' + options.title + '</div>' +
                '</div>' +
                '<div class="content"></div>'
            ).find('.close').click(function() {
                self.close();
            });

            // dodaj pasek stanu, jezeli zazadano go jawnie badz
            // niejawnie dodajac przyciski
            if (options.status) {
                self.status(typeof options.status !== 'boolean' ? options.status : '');
            }

            if (options.buttons) {
                self.buttons(options.buttons);
            }

            var c = _modal.children('.content'),
                pl = parseInt(c.css('paddingLeft')),
                pr = parseInt(c.css('paddingRight')),
                tb = _modal.children('.title-bar');
            c.width(options.width);
            c.height(options.height);
            tb.width(options.width + pl + pr);

            if (options.iframe) {
                // iframe: {url: string, expand: bool, load: function}
                var iframe = $('<iframe/>')
                        .load(function() {
                            console.log('iframe.load');
                            this.style.display = 'block';

                            if (options.iframe.expand) {
                                var h = iframe.contents().find('body').height();
                                iframe.height(h);
                                _modal.children('.content').css('height', '');
                            }

                            _modal.removeClass('loading');
                            _centerModal(true);

                            if (options.iframe.load) {
                                options.iframe.load.apply(_modal, [iframe]);
                            }
                        })
                        .attr('src', options.iframe.url)
                        .width(options.width)
                        .height(options.height)
                        .each(function() {
                            this.style.display = 'none';
                            this.style.border = 'none';
                            this.scrolling = 'no';
                            this.frameBorder = 0;
                            this.allowTransparency = true;
                        });

                _modal.addClass('loading');
                options.content = iframe;

            } else if (options.request) {
                // request: jQuery.ajax options
                var success = options.request.success;
                options.request.success = function(data, textStatus, jqXHR) {
                    _modal.removeClass('loading');
                    if (typeof options.request.content === 'function') {
                        options.request.content.apply(this, [self, data, textStatus, jqXHR]);
                    } else {
                        self.content(data);
                    }
                    if (success) {
                        success.apply(this, [data, textStatus, jqXHR]);
                    }
                }
                _modal.addClass('loading');
                $.ajax(options.request);
            }

            // nie polegaj na zadnej zewnetrznej bibliotece do overlayeringu,
            // bo nie wszystkie dzialaja pod roznymi wersjami jquery.
            _overlay = $('<div></div>');
            _overlay.css({
                background: options.overlayColor,
                opacity: options.overlayOpacity,
                top: 0,
                left: 0,
                width: '100%',
                height: '100%',
                position: 'fixed',
                zIndex: 999999999,
                display: 'none',
                overflow: 'hidden'
            }).appendTo('body').fadeIn('fast');
            _modal.css('zIndex', 1 + _overlay.css('zIndex'));
            _centerModal();

            function _onload() {
                self.content(options.content);
                _centerModal();
                if (options.load) {
                    options.load.apply(_modal, [_modal.children('.content').first()]);
                }            
            }
            _onload();

            $(window).bind('resize scroll', _centerModal);

            return _modal;
        }

        /**
         * Zamyka okienko modalne.
         */
        this.close = function() {
            if (_modal) {
                _modal.css('display', 'none');
                _overlay.remove();

                jStatus = jButtons = null;

                _modal.remove();
                _modal = null;

                $(window).unbind('resize', _centerModal);
            }
        }

        /**
         * Ustawia tytuł okienka modalnego.
         * @param {string} text
         */
        this.title = function(text) {
            if (_modal) {
                _modal.children('.title-bar > .title').html(text);
            }
            return self;
        }

        /**
         * Ustawia zawartość okienka modalnego.
         * @param {object|string}
         */
        this.content = function(text) {
            if (_modal) {
                if (typeof text == 'object') {
                    _modal.children('.content').empty().append(text);
                } else {
                    _modal.children('.content').html(text);
                }
            }
        }
    } // }}}
};

Scholar.modal = new Scholar.dialog(window.jQuery);


/*$(function() {

    
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

});*/
