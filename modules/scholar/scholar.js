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
         * Przechowuje błędy wywołania funkcji obsługi zdarzeń słuchaczy.
         * @type Array
         */
        this.errors = [];

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
         * zdarzenia onAdd.
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
                this.notify('onAdd', id);
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
         * Usuwa wszystkie elementy ze zbioru.
         * @returns {idSet}
         */
        this.clear = function() {
            for (var key in _items) {
                delete _items[key];
            }
            _size = 0;
            return this;
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
                    try {
                        listener[event].apply(listener, args);
                    } catch (e) {
                        this.errors[this.errors.length] = e;
                    }
                }
            }

            return this;
        }

        /**
         * Dodaje słuchacza zmian w zbiorze. Obsługiwane zdarzenia to
         * onAdd i onDelete, przyjmujące jako argument identyfikator.
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
     * Kazdy item musi miec ustawione .id.
     * @constructor
     * @param {Array} items             lista elementów
     * @param {object} options          zbiór par klucz/wartość konfigurujących obiekt.
     * @param {string} options.itemSelector     wzorzec selektora elementu drzewa dokumentu odpowiadającego
     *                                          elementowi listy, ciąg znaków "{id}" będzie zastąpiony
     *                                          identyfikatorem konkretnego elementu listy
     * @param {string} [options.filterSelector] selektor elementu drzewa dokumentu, z ktorego bedzie brana wartosc do filtrowania (zwykle INPUT[type="text"])
     * @param {string} [options.filterReset]    selektor elementu drzewa dokumentu czyszczącego filtr (zwykle BUTTON lub INPUT[type="button"])
     * @param {string} [options.filterSubject]  nazwa właściwości elementu, po której lista będzie filtrowana,
     *                                          musi być podany, jeżeli podano filterSelector
     */
    itemSelector: function(items, options) { // {{{
        /**
         * Zwraca obiekt jQuery zawierający element drzewa dokumentu
         * odpowiadający elementowi o podanym identyfikatorze.
         * @param id
         * @returns {jQuery}
         */
        function _getElementById(id) {
            return $(options.itemSelector.replace(/\{id\}/g, id));
        }

        /** 
         * Ukrywa te elementy listy, które nie zawierają ciągu znaków
         * podanego w wybranym polu tekstowym.
         * @param {string} [value]        opcjonalna wartość do nadania elementowi filtrującemu
         */
        function _filter(value) {
            var filter = $(options.filterSelector);

            // Poniewaz funkcja jest uzywana jako obsluga zdarzenia keyup,
            // ustaw wartosc elementu filtrujacego tylko jezeli value
            // jest stringiem
            if (typeof value === 'string') {
                filter.val(value);
            }

            var needle = filter.val().toLowerCase();
            for (var i = 0; i < items.length; ++i) {
                var item = items[i],
                    elem = _getElementById(item.id),
                    haystack = String(item[options.filterSubject]).toLowerCase();
                elem.css('display', haystack.indexOf(needle) != -1 ? '' : 'none');
            }
        }

        // zbior buforowy przechowujacy zaznaczone elementy
        var buffer = new Scholar.idSet;

        // podepnij do zbioru sluchacza zdarzen
        buffer.addListener({
            onAdd: function(id) {
                var elem = _getElementById(id).addClass('selected');
                elem.html(elem.html() + ' (SELECTED)');
            },
            onDelete: function(id) {
                var elem = _getElementById(id).removeClass('selected');
                elem.html(elem.html().replace(/ \(SELECTED\)/, ''));
            }
        });

        // podepnij dodawanie / usuwanie elementow za pomoca klikniecia
        $(items).each(function (key, item) {
            var elem = _getElementById(item.id);
            elem.click(function() {
                buffer[buffer.has(item.id) ? 'del' : 'add'](item.id, item);
            });
        });

        var instanceId = window.location.hash.substr(2);
        window['__itemSelector_' + instanceId] = this;

        // jezeli podano selektor elementu, na podstawie wartosci ktorego
        // beda filtrowane elementy, podepnij filtrowanie po kazdym
        // wcisnieciu klawisza na klawiaturze
        if (options.filterSelector) {
            $(options.filterSelector).keyup(_filter);
        }

        // jezeli podano selektor elementu czyszczacego filter podepnij
        // czyszczenie filtra po kliknieciu w niego
        if (options.filterReset) {
            $(options.filterReset).click(function() {
                _filter('');
                return false;
            });
        }

        this.add = function(id, value) {
            return buffer.add(id, value);
        }

        this.each = function(callback) {
            return buffer.each(callback);
        }
    }, // }}}
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

        /**
         * Pozycjonuje okienko na środku ekranu.
         * @param {boolean} [animate]   jeżeli true pozycjonowanie bedzie animowane
         */
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
         * @returns {jQuery}            element przycisku
         */
        this.button = function(id) {
            return _getButtons().children('#button-' + id);
        }

        // Przycisk mozna zdefinowac jako: {label: string, type: string, click: function},
        // mozna uzyc rowniez predefiniowanej wartosci 'cancel', ktora tworzy
        // przycisk 'Anuluj' zamykajacy okienko.
        // Funkcja przekazana w .click bedzie miala kontekst elementu przycisku
        // (DIV.dialog-button), wzbogaconego o dodatkowe pole .parentDialog, ktore daje
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
                        if (item.disabled) {
                            btn.addClass('disabled');
                        }
                        if (item.type) {
                            btn.addClass(item.type);
                        }
                    }

                    btn.appendTo(container);

                    // jQuery nie radzi sobie z przekazywaniem danych miedzy
                    // okienkami za pomoca .data()
                    btn.get(0).parentDialog = self;
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
                                options.iframe.load.apply(self, [iframe]);
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
    }, // }}}
    /**
     * Umieszcza w podanym selektorze widget zarządzający załącznikami.
     * @constructor
     * @param {string} selector         selektor jQuery wskazujacy element,
     *                                  w którym ma zostać umieszczony widget
     */
    attachmentManager: function(selector, settings) { // {{{
        var self = this,
            j = $(selector)
            .addClass('scholar-attachment-manager')
            .html('<div class="table-wrapper"></div><div class="buttons-wrapper"></div>');

        var uniq = String(Math.random()).substr(2);
        var idset = new Scholar.idSet;
        var idsetId = '_attachmentManager' + uniq;

        // podepnij ten obiekt jako widok zbioru
        idset.attachmentManager = this;
        window[idsetId] = idset;

        var btnSelect = $('<button/>')
            .html('Wybierz plik')
            .click(function() {
                var _iframe, _selector;
                Scholar.modal.open({
                    width: 480,
                    height: 240,
                    iframe: {
                        url: settings.urlFileSelect + '#!' + idsetId,
                        load: function(iframe) {
                            _iframe = iframe;
                            var scholar = iframe[0].contentWindow.Scholar;
                            if (scholar) {
                                _selector = scholar.itemSelector.getInstance(idsetId);
                                idset.each(function (k, v) {
                                    _selector.add(k, v);
                                });

                                this.button('apply').removeClass('disabled');
                            }
                        }
                    },
                    buttons: {
                        apply: {
                            label: 'Zastosuj',
                            disabled: true,
                            click: function() {
                                if (_selector) {
                                    idset.clear();
                                    _selector.each(function(k, v) {
                                        idset.add(k, v);
                                    });
                                    self.redraw();
                                    this.parentDialog.close();
                                }
                            }
                        },
                        cancel: 'cancel',
                    }
                });
                return false;
            });
        var btnUpload = $('<button/>')
            .html('Wgraj plik')
            .click(function() {
                var _iframe;
                Scholar.modal.open({
                    width: 480,
                    height: 240,
                    iframe: {
                        url: settings.urlFileUpload + '#!' + uniq,
                        load: function(iframe) {
                            _iframe = iframe;
                            _iframe.contents().find('[type="submit"]').hide();
                            this.status('');
                            this.button('apply').removeClass('disabled');
                        }
                    },
                    buttons: {
                        apply: {
                            label: 'Prześlij',
                            disabled: true,
                            click: function() {
                                this.parentDialog.status('Przesyłanie pliku...');
                                this.parentDialog.button('apply').addClass('disabled');
                                _iframe.contents().find('form').submit();
                            }
                        },
                        cancel: 'cancel',
                    }
                });
                return false;
            });

        j.children('.buttons-wrapper').append(btnSelect).append(btnUpload);

        this.redraw = function() {
            var tbody = j.children('.table-wrapper').html('<table class="sticky-enabled"><thead><tr><th>Plik</th><th>Rozmiar</th><th>Etykieta</th><th></th></tr></thead><tbody></tbody></table>').find('tbody');
                                var odd = true;
                                idset.each(function(id, value) {
                                    $('<tr class="draggable ' + (odd ? 'odd' : 'even') + '"/>')
                                        .append('<td>' + value.filename + '</td>')
                                        .append('<td>' + value.filesize + '<input type="hidden" class="weight" /></td>')
                                        .append('<td><input type="text" /></td>')
                                        .append($('<td style="cursor:pointer">DELETE</td>').click(function() {
                                            // usun identyfikator pliku ze zbioru
                                            idset.del(id);

                                            // usun ewentualny komunikat o tym, ze zmiany w tej tabeli
                                            // nie beda zapisane dopoki formularz nie zostanie przeslany
                                            $(this).parents('table:first').next('.warning').fadeOut(function() {
                                                $(this).remove();
                                            });
                                                                                        // usun wiersz
                                            $(this).parent().remove();
                                        })).appendTo(tbody);
                                        odd = !odd;
                                });
                                // dodaj tabledrag
                                var td = new Drupal.tableDrag(j.find('.table-wrapper > table')[0], {weight: [{
                                    target: 'weight',
                                    source: 'weight',
                                    relationship: 'sibling',
                                    action: 'order',
                                    hidden: false,
                                    limit: 0
                                }] });
        
        }              
    } // }}}
};

/**
 * Informuje powiązaną instancję menadżera załączników
 * o uploadzie nowego pliku. Funkcja przeznaczona do wywolania
 * w okienku lub IFRAME otwartej przez menadżera.
 * @static
 * @param {object} file                 reprezentacja rekordu przeslanego pliku
 * @param {string} [urlFragment]        opcjonalny fragment URL wskazujący na zbiór
 *                                      przechowujący identyfikatory plików znajdujący
 *                                      się w okienku-rodzicu
 */
Scholar.attachmentManager.notifyUpload = function(file, urlFragment) { // {{{
    var context, close;

    // wyznacz okienko-rodzica, przygotuj funkcje zamykajaca
    // okienko z biezaca strona
    if (window.opener) {
        // strona otwarta w okienku za pomoca window.open
        context = window.opener;
        close = function() {
            window.close();
        }
    } else if (window.parent !== window) {
        // strona w IFRAME w okienku dialogowym
        context = window.parent;
        close = function() {
            context.Scholar.modal.close();
        }
    }

    if (context && urlFragment) {
        // po przeslaniu pliku dodaj przeslany plik do attachmentManagera
        // w okienku-rodzicu i zamknij okienko
        var uniq = String(urlFragment).substr(1),
            storage = context['_attachmentManager' + uniq];

        if (storage) {
            storage.add(file.id, file);

            if (storage.attachmentManager) {
                // wymus odswiezenie listy plikow
                storage.attachmentManager.redraw();
            }
        }

        close();
    }
} // }}}

Scholar.itemSelector.getInstance = function(id) {
    alert('itemSelector.getInstance: ' + id);
    return window['__itemSelector_' + id];
}

Scholar.modal = new Scholar.dialog(window.jQuery);
