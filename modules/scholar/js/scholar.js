/**
 * @fileOverview Biblioteka funkcji wykorzystycznych przez moduł Scholar.
 * @author xemlock
 * @version 2012-08-25
 */

/**
 * @namespace Przestrzeń nazw dla funkcji modułu.
 */
var Scholar = {
    /**
     * Narzędzia do manipulacji stringów
     * @namespace
     */
    str: {
        /**
         * Przekształca liczbę podaną w bajtach na rozmiar czytelny
         * dla człowieka.
         * @param {number} bytes
         *     liczba bajtów
         * @param {string} [separator=" "]
         *     separator oddzielający liczbę i jej jednostkę
         */
        filesize: function(bytes, separator) { // {{{
            var idx = 0,
                rdx = 1024,
                pre = ['', 'K', 'M', 'G', 'T', 'P'],
                end = pre.length - 1,
                sep = typeof separator === 'undefined' ? ' ' : String(separator);

            while (bytes > rdx) {
                bytes /= rdx;
                if (idx == end) {
                    break;
                }
                ++idx;
            }

            bytes = Math.round(100 * bytes) / 100;
            return bytes + sep + pre[idx] + 'B';
        } // }}}
    },
    /**
     * Narzędzia internacjonalizacji.
     * @namespace
     */
    i18n: {
        /**
         * @param {string} message
         * @returns {string}
         */
        tr: function(message) { // {{{
            return String(typeof this.dictionary[message] === 'undefined' ? message : this.dictionary[message]);
        }, // }}}
        dictionary: {}
    },
    /**
     * Prosty silnik renderowania szablonów.
     * @param {string} template
     *     zawartość szablonu. Wstawienie konktretnej właściwości obiektu
     *     przekazanego w parametrze vars do szablonu uzyskuje się pisząc
     *     <code>{nazwa_właściwości}</code>, odwołanie do zmiennej vars to
     *     <code>{.}</code>. Nawiasy wąsowe uzyskuje się pisząc je dwukrotnie
     *     (<code>{{</code> i <code>}}</code>), przy czym nawias zamykający
     *     może być pisany pojedynczo.
     * @param {object} vars
     *     kontener ze zmiennymi przekazanymi do szablonu
     */
    render: function(template, vars) { // {{{
        var $ = window.jQuery,
            regex  = /\{\{|\}\}|\{([^\{\}]*)\}/g,
            renderer = function($0, $1) {
                // Escape'owany nawias klamrowy
                switch ($0) {
                    case '{{':
                        return '{';
                    case '}}':
                        return '}';
                }

                var key = $.trim($1);

                // Kropka odpowiada calemu obiektowi podanemu jako argument
                // vars funkcji render()
                if (key == '.') {
                    return vars;
                }

                // Odwolanie do konkretnej zmiennej
                return typeof vars[key] === 'undefined' ? '' : vars[key];
            }

        return String(template).replace(regex, renderer);
    }, // }}}
    /**
     * Zbiór identyfikatorów.
     * @constructor
     */
    IdSet: function() { // {{{
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
         * Jeżeli z kluczem nie jest powiązana żadna wartość
         * zwrócona zostanie wartość undefined.
         * @param id
         */
        this.get = function(id) {
            var undef, key = '_' + id;

            if (typeof _items[key] !== 'undefined') {
                return _items[key];
            }

            return undef;
        }

        /**
         * Czy podany identyfikator jest obecny w zbiorze.
         * @returns {boolean}
         */
        this.has = function(id) {
            return typeof _items['_' + id] !== 'undefined';
        }

        /**
         * Dodaje identyfikator do zbioru. Funkcja wywołuje zdarzenie
         * onAdd, którego parametrami są kolejno: dodany identyfikator,
         * wartość logiczna mówiąca czy taki klucz został dodany po raz
         * pierwszy do zbioru.
         * @param id
         *     identyfikator
         * @param [value]
         *     opcjonalna wartość powiązana z podanym identyfikatorem
         * @returns {IdSet}
         *     zbiór na którym wywołano metodę
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
            }

            // powiadom sluchaczy o dodaniu nowego identyfikatora
            this.notify('onAdd', id, added);

            return this;
        }

        /**
         * Usuwa identyfikator ze zbioru.
         * @param id
         *     identyfikator
         * @returns {boolean}
         *     czy identyfikator został usunięty
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
         * @returns {IdSet}
         *     zbiór na którym wywołano metodę
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
         * @param {function} callback
         *     funkcja wywoływana dla każdego identyfikatora
         * @returns {IdSet}
         *     zbiór na którym wywołano metodę
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
         * @param {string} event
         *     nazwa zdarzenia
         * @returns {IdSet}
         *     zbiór na którym wywołano metodę
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
         * @param {object} listener
         *     słuchacz
         * @returns {number}
         *     wewnętrzny numer nadany słuchaczowi
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
         * @param {object|number} listener
         *     słuchacz lub jego numer na liście
         * @returns {boolean}
         *     czy słuchacz został usunięty
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
     * Rejestr.
     * @constructor
     * @param [context=window]
     *     obiekt, do którego ma zostać podpięty rejestr. Może być to
     *     bieżące okno (<code>window</code>) lub okno należące do ramki
     *     (<code>iframe.contentWindow</code>), w ogólności może to być
     *     dowolny obiekt.
     */
    Data: function(context) { // {{{
        if (0 == arguments.length) {
            context = window;
        }

        if (typeof context.__scholarData === 'undefined') {
            context.__scholarData = {};
        }

        /**
         * Zwraca wartość powiązaną z podanym kluczem.
         * @param {string} key
         */
        this.get = function(key) {
            return context.__scholarData['_' + key];
        }

        /**
         * Ustawia wartość powiązaną z podanym kluczem.
         * @param {string} key
         * @param value
         */
        this.set = function(key, value) {
            context.__scholarData['_' + key] = value;
            return this;
        }
    }, // }}}
    /**
     * Widget z listą wyboru elementów. W dalszej części dokumentacji
     * <em>element dokumentu</em> oznacza element drzewa DOM, zaś <em>element
     * wybieralny</em> odnosi się do obiektu prezentowanego na liście, który
     * może zostać zaznaczony przez użytkownika.
     * @constructor
     * @param {string|jQuery|element} selector
     *     element dokumentu, w którym ma zostać utworzony widget listy
     * @param {Array} items
     *     lista elementów wybieralnych
     * @param {object} [options]
     *     zbiór par klucz/wartość konfigurujących obiekt
     * @param {string} [options.idKey='id']
     *     właściwość elementu wybieralnego przechowująca jego identyfikator
     * @param {string} [options.template='{ label }']
     *     szablon określający jak przedstawiać elementy wybieralne, patrz 
     *     {@link Scholar.render}
     * @param {string} [options.filterSelector] 
     *     selektor elementu dokumentu (pola tekstowego), z ktorego bedzie
     *     brana wartosc do filtrowania listy elementów wybieralnych
     * @param {string} [options.filterReset]
     *     selektor elementu dokumentu (przycisku), do którego zostanie 
     *     podpięta obsługa zdarzenia click czyszcząca filtr
     * @param {string} [options.filterKey='label']
     *     nazwa właściwości elementu wybieralnego, po której lista będzie
     *     filtrowana
     * @param {boolean} [options.showOnInit=true]
     * @param {string} [options.emptyMessage]
     */
    ItemPicker: function(selector, items, options) { // {{{
        var $ = window.jQuery;

        options = $.extend({}, {
            idKey:     'id',
            filterKey: 'label',
            template:  '{ label }'
        }, options);

        var _domain,   // zbior przechowujacy wszystkie elementy
            _selected, // zbior przechowujacy elementy zaznaczone przez uzytkownika
            _elements; // zbior tagow LI odpowiadajacych elementom listy

        function _initDomain() {
            _domain = new Scholar.IdSet;

            // wypelnij zbior wszystkich elementow
            for (var i = 0, n = items.length; i < n; ++i) {
                var item = items[i];
                _domain.add(item[options.idKey], item);
            }
        }

        /**
         * Przygotowuje zbior zaznaczonych elementow.
         */
        function _initSelected() {
            _selected = new Scholar.IdSet;

            // podepnij sluchacza zdarzen do zbioru elementow
            _selected.addListener({
                onAdd: function(id) {
                    var elem = _elements.get(id);
                    if (elem) {
                        elem.addClass('selected');
                    }
                },
                onDelete: function(id) {
                    var elem = _elements.get(id);
                    if (elem) {
                        elem.removeClass('selected');
                    }
                }
            });
        }

        /**
         * Przygotowuje element UL z elementami LI odpowiadającymi 
         * elementom listy i umieszcza go jako jedyne dziecko selektora
         * podanego w konstruktorze.
         */
        function _initElements() {
            _elements = new Scholar.IdSet;

            var ul = $('<ul class="scholar-item-picker" />'),
                createElement = function(id, item, ul) {
                    return $('<li/>')
                        .html(Scholar.render(options.template, item))
                        .attr('data-id', id)
                        .click(function() {
                            $(this).removeClass('initial');
                            _selected[_selected.has(id) ? 'del' : 'add'](id, item);
                        })
                        .appendTo(ul);
                }

            _domain.each(function(id, item) {
                _elements.add(id, createElement(id, item, ul));
            });

            // jezeli podano komunikat dla pustej listy, dodaj go na koniec
            if (typeof options.emptyMessage === 'string') {
                $('<li class="empty-message" style="display:none" />')
                    .append(options.emptyMessage)
                    .appendTo(ul);
            }

            $(selector).hide().empty().append(ul);
        }

        /**
         * Pokazuje lub ukrywa komunikat z informacją, że lista elementów
         * jest pusta.
         * @param {boolean} [show=true]
         */
        function _showEmptyMessage(show) {
            var j = $(selector).find('> ul > li.empty-message');
            j[typeof show === 'undefined' || show ? 'show' : 'hide']();
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

            var needle = filter.val().toLowerCase(),
                vis = 0;

            _elements.each(function(id, element) {
                var item = _domain.get(id),
                    haystack = String(item[options.filterKey]).toLowerCase();

                // item na pewno istnieje, bo wskaznik do niego jest
                // przechowywany w domenie, na podstawie ktorej zbudowane
                // sa tagi LI odpowiadajace jej elementom

                element.css('display', haystack.indexOf(needle) != -1 ? '' : 'none');

                // tu musimy zbadac rzeczywista wartosc display
                if (element.is(':visible')) {
                    ++vis;
                }
            });

            _showEmptyMessage(0 == vis);
        } 

        /**
         * Dodaje element wybieralny o podanym identyfikatorze do zaznaczonych,
         * ale tylko wtedy, gdy taki element jest wśród elementów podanych 
         * w konstruktorze.
         * Metoda jest przeznaczona do ustawiania początkowego zaznaczania.
         * Elementy LI zaznaczone tą metodą (nie przez kliknięcie użytkownika)
         * mają nadaną dodatkową klasę <em>initial</em>.
         *
         * @param id
         *     identyfikator elementu wybieralnego
         * @returns {ItemPicker}
         *     obiekt, na którym wywołano tę metodę
         */
        this.add = function(id) {
            var item = _domain.get(id);

            if (typeof item !== 'undefined') {
                var elem = _elements.get(id);
                if (elem) {
                    elem.addClass('initial');
                }

                _selected.add(id, item);
            }

            return this;
        }

        /**
         * Iteruje po zbiorze zaznaczonych elementów.
         * @returns {ItemPicker}
         *     obiekt, na którym wywołano tę metodę
         */
        this.each = function(callback) {
            _selected.each(callback);
            return this;
        }

        /**
         * Pokazuje listę elementów.
         * @param {boolean} fadeIn
         *     czy do pokazywania użyć efektu stopniowego narastania 
         *     nieprzezroczystości czy pokazać od razu
         * @returns {ItemPicker}
         *     obiekt, na którym wywołano tę metodę
         */
        this.show = function(fadeIn) {
            $(selector)[fadeIn ? 'fadeIn' : 'show']();

            // jezeli wszystkie elementy wybieralne sa ukryte, pokaz komunikat
            // o braku elementow
            var vis = 0;

            _elements.each(function (id, element) {
                if (element.is(':visible')) {
                    ++vis;
                }
            });

            _showEmptyMessage(0 == vis);

            return this;
        }

        _initDomain();
        _initSelected();
        _initElements();

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

        // nie pokazuj listy, jezeli zaznaczono, zeby tego nie robic
        // podczas inicjalizacji - bedzie to zrobione recznie
        if (typeof options.showOnInit === 'undefined' || options.showOnInit) {
            this.show();
        }

        // podepnij globalny wskaznik do tego obiektu, aby mozna bylo
        // siegnac do niego z zewnatrz
        var external = window.location.hash.substr(1);
        if (external.length) {
            (new Scholar.Data(window)).set(external, this);
        }
    }, // }}}
    /**
     * Okienko.
     * @constructor
     */
    Dialog: function() { // {{{
        var $ = window.jQuery,
            self = this,
            _modal, _overlay,
            jStatus, jButtons,
            _translator;

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
         * @returns {jQuery}             element otaczający przyciski
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
                        btn.html(self.translate('Cancel'))
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
         * @returns {string|Scholar.modal}
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
         * @param {object} [options.iframe]
         * @param {object} [options.request]
         * @param {function} [options.translate]
         */
        this.open = function(options) {
            options = $.extend({}, {
                id:      'scholar-modal',
                title:   '',
                content: '',
                width:   320,
                height:  240,
                overlayColor: '#fff',
                overlayOpacity: 0.75
            }, options);

            if (typeof options.translate === 'function') {
                _translator = options.translate;
            }

            _modal = $('#' + options.id);
            if (!_modal.length) {
                _modal = $('<div class="dialog"/>').attr('id', options.id).appendTo('body');
            }

            _modal.css('display', 'none').html(
                '<div class="title-bar">' +
                '<div class="close" title="' + self.translate('Close') + '" role="button">&times;</div>' +
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
                // iframe.load: this - iframe element, this.parentDialog - dialog
                var iframe = $('<iframe/>')
                        .load(function() {
                            this.style.display = 'block';

                            if (options.iframe.expand) {
                                var h = iframe.contents().find('body').height();
                                iframe.height(h);
                                _modal.children('.content').css('height', '');
                            }

                            _modal.removeClass('loading');
                            _centerModal(true);

                            this.parentDialog = self;

                            if (options.iframe.load) {
                                options.iframe.load.apply(this);
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

        /**
         * Tłumaczy podany tekst.
         * @param {string} text
         * @returns {string}
         */
        this.translate = function(text) {
            return String(_translator ? _translator(text) : text);
        }
    }, // }}}
    /**
     * Widget wielokrotnego wyboru i sortowania elementów. W dalszej części
     * dokumentacji <em>element dokumentu</em> oznacza element drzewa DOM, zaś
     * <em>element wybieralny</em> odnosi się do obiektu prezentowanego na
     * liście lub w tabeli.
     * @constructor
     * @param {string|jQuery|element} target
     *     element dokumentu, do którego zostanie podpięty widget
     * @param {object} [options]
     *     zbiór par klucz/wartość konfigurujących widget
     * @param {Array} [options.header]
     *     lista tytułów kolumn tabeli prezentującej elementy wybieralne
     * @param {Array} [options.templates]
     *     lista rendererów wartości w kolejnych kolumnach wiersza tabeli
     *     reprezentującego element wybieralny. Jeżeli rendererem jest funkcja,
     *     musi ona przyjmować jako jedyny argument element wybieralny, oraz
     *     zwracać wartość akceptowaną przez jQuery.append (string, 
     *     jQuery lub element DOM), albo tablicę tych wartości (eliminuje to
     *     tworzenie niepotrzebnych wrapperów). Gdy rendererem jest string,
     *     zostanie on używaty jako szablon i przekazany do funkcji
     *     {@link Scholar.render} z elementem wybieralnym jako kontenerem
     *     zmiennych.
     * @param {string|function} [options.weightTemplate="weight[{ id }]"]
     *     szablon nazwy pola przechowującego wagę wiersza. Jeżeli jest to
     *     funkcja, musi ona zwracać string i przyjmować jako argument
     *     identyfikator elementu wybieralnego powiązanego z tym wierszem.
     * @param {boolean} [options.showOnInit=true]
     *     flaga mówiąca czy tablica prezentująca elementy wybieralne ma
     *     zostać dołączona do dokumentu podczas inicjalizacji widgeta
     * @param {function} [options.translate]
     *     funkcja do tłumaczenia napisów
     */
    SortableMultiselect: function(target, options) { // {{{
        var $ = window.jQuery,
            self = this,
            _element, _selected, _header,
            _templates, _weightTemplate, _translator;

        /**
         * Inicjalizuje prywatne zmienne obiektu.
         * @param target
         * @param {object} options
         */
        function _init(target, options) {
            _element   = $(target);
            _selected  = new Scholar.IdSet;
            _header    = [];
            _templates = [];

            // ustaw nazwy kolumn tabeli
            if (options.header) {
                for (var i = 0, n = options.header.length; i < n; ++i) {
                    _header[_header.length] = String(options.header[i]);
                }
            }

            // ustaw szablony do wyswietlania wierszy zaznaczonych elementow,
            // szablony musza odpowiadac kolumnom naglowka o tym samym indeksie
            // w tablicy, jezeli ich nie ma - jest ok, bo wartosc undefined
            // jest poprawnie obslugiwana
            if (options.templates) {
                for (var i = 0, n = _header.length; i < n; ++i) {
                    _templates[i] = options.templates[i];
                }
            }

            // zainicjuj tlumacza
            if (typeof options.translate === 'function') {
                _translator = options.translate;
            }

            // zainicjuj szablon nazwy pola z waga wiersza, uzyj wartosci
            // z ustawien tylko wtedy gdy jest to string lub funkcja
            switch (typeof options.weightTemplate) {
                case 'string':
                case 'function':
                    _weightTemplate = options.weightTemplate;
                    break;

                default:
                    _weightTemplate = 'weight[{ id }]';
                    break;
            }

            // inicjalizacja markupu
            _element
                .addClass('scholar-sortable-multiselect')
                .html('<div class="table-wrapper"></div><div class="buttons-wrapper"></div>')
                .data('sortableMultiselect', self);

            // nie generuj tabeli, jezeli zaznaczono, zeby tego nie robic
            // podczas inicjalizacji - bedzie to zrobione recznie
            if (typeof options.showOnInit === 'undefined' || options.showOnInit) {
                self.redraw();
            }
        }

        /**
         * Aktualizuje wartości wag dla elementów tabeli.
         * @param {jQuery} tbody
         *     obiekt jQuery przechowujący element TBODY tabeli
         */
        function _updateWeights(tbody)
        {
            var weight = 0;

            tbody.find('tr[data-id] input.weight').each(function() {
                $(this).val(weight++);
            });
        }

        /**
         * Ustawia elementy w zbiorze wybranych zgodnie z kolejnością
         * odpowiadających im wierszy tabeli. Funkcja aktualizuje
         * wagi wierszy.
         * @param {jQuery} tbody
         *     obiekt jQuery przechowujący element TBODY tabeli
         */
        function _reorderSelected(tbody)
        {
            var weight = 0, queue = [];

            // przejdz kolejno przez wszystkie wiersze w tabeli i dla 
            // kazdego z nich dodaj do kolejki odpowiadajacy mu element
            tbody.find('tr[data-id]').each(function() {
                var id = $(this).attr('data-id'),
                    item = _selected.get(id);

                if (typeof item !== 'undefined') {
                    queue[queue.length] = [id, item];
                }

                // waga jest zwiekszana leniwie, zeby nie robic inkrementacji
                // dla nieistniejacych elementow
                $(this).find('input.weight').each(function() {
                    $(this).val(weight++);
                });
            });

            _selected.clear();

            for (var i = 0, n = queue.length; i < n; ++i) {
                var pair = queue[i];
                _selected.add(pair[0], pair[1]);
            }
        }

        /**
         * Usuwa wiersz z tabeli.
         * @param {jQuery} tr
         *     obiekt jQuery przechowujący element TR tabeli
         */
        function _removeRow(tr) {
            var tbody = tr.parent();

            // usun identyfikator pliku ze zbioru
            _selected.del(tr.attr('data-id'));

            // usun wiersz i zaktualizuj wagi
            tr.remove();
            _updateWeights(tbody);

            // usun ewentualny komunikat pochodzacy z Drupal.tableDrag o tym,
            // ze zmiany w tej tabeli nie beda zapisane dopoki formularz nie
            // zostanie przeslany
            tbody.parent().next('.warning').fadeOut(function() {
                $(this).remove();
            });
        }

        /**
         * Tworzy wiersz tabeli odpowiadający obiektowi zbioru i podpina go 
         * do tabeli.
         * @param {jQuery} tbody
         *     obiekt jQuery przechowujący element TBODY tabeli
         * @param id
         * @param item
         * @param {number} [position]
         *     numer wiersza, potrzebny do określenia klasy CSS odpowiadającej
         *     wierszowi parzystemu (klasa <em>even</em>) czy nieparzystemu
         *     (klasa <em>odd</em>)
         */
        function _createRow(tbody, id, item, position) {
            var tr = $('<tr/>'), cls = 'draggable', weightName;

            if (typeof position === 'number') {
                cls += position % 2 ? ' odd' : ' even';
            }

            tr.attr({'class': cls, 'data-id': id});
            tr.mouseup(function() {
                // To zdarzenie wywolywane jest zmiana kolejnosci ulozenia
                // wierszy w tabeli. Skoro tak, uszereguj elementy w zbiorze
                // zeby ich kolejnosc odpowiadala wierszom.
                _reorderSelected($(this).parent())
            })

            // wygeneruj nazwe pola przechowujacego wage wiersza
            switch (typeof _weightTemplate) {
                case 'string':
                    weightName = Scholar.render(_weightTemplate, {id: id});

                    break;

                case 'function':
                    weightName = _weightTemplate(id);
                    break;
            }

            // zastap cudzyslowy encjami, zeby uniknac uszkodzenia markupu
            weightName = String(weightName).replace(/"/g, '&quot;');

            // wygeneruj wartosci kolumn w tym wierszu na podstawie
            // powiazanego obiektu
            for (var i = 0, n = _templates.length; i < n; ++i) {
                var td = $('<td/>'),
                    template = _templates[i],
                    result;

                switch (typeof template) {
                    case 'undefined':
                        result = '';
                        break;

                    case 'function':
                        result = template(item);

                        break;

                    case 'string':
                        result = Scholar.render(template, item);
                        break;

                    default:
                        result = String(template);
                        break;
                }

                // jezeli funkcja renderujaca zawartosc komorki tabeli zwrocila
                // tablice dodaj wszystkie znajdujace sie w niej elementy
                if (result instanceof Array) {
                    for (var j = 0, m = result.length; j < m; ++j) {
                        td.append(result[j]);
                    }
                } else {
                    td.append(result);
                }
                tr.append(td);
            }

            // dodaj kolumne z waga i wyzwalacz usuwania wiersza
            tr.append('<td><input type="text" name="' + weightName + '" class="weight" /></td>');
            tr.append(
                $('<td><a href="#!">' + self.translate('Delete') + '</a></td>')
                .click(function() {
                    _removeRow($(this).parent());
                })
            );

            // podepnij wiersz do tabeli
            tr.appendTo(tbody);

            return tr;
        }

        /**
         * Ustawia główne przyciski kontrolujące widget.
         * @param {array} buttons
         *     specyfikacja przycisków
         * @returns {SortableMultiselect}
         *     obiekt, na któym wywołano metodę
         */
        this.setButtons = function(buttons) {
            var container = _element.children('.buttons-wrapper').empty();

            for (var i = 0, n = buttons.length; i < n; ++i) {
                var options = buttons[i];

                $('<button/>')
                    .html(options.label)
                    .click(options.click)
                    .appendTo(container);
            }

            return this;
        }

        /**
         * Generuje tabelę na podstawie wybranych elementów.
         */
        this.redraw = function() {
            // utworz za kazdym razem nowa tabele, zeby odpiac Drupalowe
            // dodatki
            var wrapper = _element.children('.table-wrapper'),
                table = $('<table class="sticky-enabled"/>'),
                thead, tbody;

            // zbuduj naglowek w oparciu o specyfikacje
            thead = '<thead>';
            for (var i = 0, n = _header.length; i < n; ++i) {
                thead += '<th>' + self.translate(_header[i]) + '</th>';
            }
            // utworz dwie dodatkowe kolumny, z waga wiersza i wyzwalacz
            // usuwania wiersza
            thead += '<th>' + self.translate('Weight') + '</th>';
            thead += '<th></th></thead>';
            table.append(thead);

            // utworz wiersze tabeli na bazie wybranych elementow
            tbody = $('<tbody/>');

            var position = 0;
            _selected.each(function(id, item) {
                _createRow(tbody, id, item, position++);
            });
            _updateWeights(tbody);

            table.append(tbody);

            // wygenerowana tablice podepnij jako jedyne dziecko wrappera
            wrapper.empty().append(table);

            // jezeli istnieja jakiekolwiek wiersze dodaj efekty Drupalowe
            if (position && window.Drupal) {
                // dodaj przeciaganie i upuszczanie wierszy
                if (Drupal.tableDrag) {
                    self.tableDrag = new Drupal.tableDrag(table[0], {weight: [{
                        target: 'weight',
                        source: 'weight',
                        relationship: 'sibling',
                        action: 'order',
                        hidden: true,
                        limit: 0
                    }] });
                }

                // dodaj ruchomy naglowek tabeli
                Drupal.behaviors.tableHeader();
            }
        }

        /**
         * Dodaje podany element do zbioru zaznaczonych.
         * @param id
         * @param item
         * @returns {SortableMultiselect}
         *     obiekt, na którym wywołano tę metodę
         */
        this.add = function(id, item) {
            _selected.add(id, item);
            return this;
        }

        /**
         * Iteruje po zbiorze zaznaczonych elementów.
         * @returns {SortableMultiselect}
         *     obiekt, na którym wywołano tę metodę
         */
        this.each = function(callback) {
            _selected.each(callback);
            return this;
        }

        /**
         * Usuwa ze zbioru zaznaczonych wszystkie elementy.
         * @returns {SortableMultiselect}
         *     obiekt, na którym wywołano tę metodę
         */
        this.clear = function() {
            _selected.clear();
            return this;
        }

        /**
         * Tłumaczy tekst.
         * @param {string} text
         * @returns {string}
         */
        this.translate = function(text) {
            return String(_translator ? _translator(text) : text);
        }

        // zainicjuj obiekt
        _init(target, options || {});
    }, // }}}
    /**
     * Biblioteka funkcji operujących na widgetach.
     * @namespace
     */
    mixins: {
        /**
         * Funkcja otwierajaca widget wyboru elementów (patrz {@link 
         * Scholar.ItemPicker}) powiązanego ze zbiorem wybranych elementów
         * widgetu typu {@link Scholar.SortableMultiselect}.
         * @param {SortableMultiselect} widget
         *      widget, na którym operować ma widget wyboru elementów
         * @param {object} settings
         *     zbiór par klucz/wartość konfigurujących wywołanie funkcji
         * @param {string} settings.url
         *     adres strony z umieszczonym w niej widgetem ItemPicker
         * @param {string} settings.title
         *     tytuł okienka modalnego
         * @param {number} [settings.width=480]
         *     domyślna szerokość okienka modalnego
         * @param {number} [settings.height=240]
         *     domyślna wysokość okienka modalnego
         */
        openItemPicker: function(widget, settings) { // {{{
            var picker,
                key = '!' + String(Math.random()).substr(2);

            Scholar.modal.open({
                title: settings.title || '',
                width: settings.width || 480,
                height: settings.height || 240,
                iframe: {
                    // strona, ktora ma w sobie ItemPickera
                    url: settings.url + '#' + key,
                    load: function() {
                        var data = new Scholar.Data(this.contentWindow);
                        // uzyskaj dostep do itemPickera w ramce
                        picker = data.get(key);

                        if (picker) {
                            // poinformuj otwartego itemPickera o juz 
                            // wybranych elementach
                            widget.each(function (k, v) {
                                picker.add(k, v);
                            });

                            picker.show(true);

                            this.parentDialog.button('apply').removeClass('disabled');
                        }
                    }
                },
                buttons: {
                    apply: {
                        label: widget.translate('Apply'),
                        disabled: true,
                        click: function() {
                            // Przygotuj podpiety zbior do przyjecia 
                            // nowowybranych elementow.
                            // Nie usuwaj elementow ze zbioru za pomoca clear(),
                            // bo w ten sposob moglibysmy stracic wartosci,
                            // ktore nie wystepuja na liscie powiazanego 
                            // itemPickera. Duplikaty sa poprawnie obslugiwane.
                            if (picker) {
                                // dodaj wszystkie elementy z pickera do zbioru
                                picker.each(function(k, v) {
                                    widget.add(k, v);
                                });
                                widget.redraw();
                                this.parentDialog.close();
                            }
                        }
                    },
                    cancel: 'cancel'
                },
                translate: widget.translate
            });
        }, // }}}
        /**
         * @param {SortableMultiselect} widget
         * @param {object} settings
         *     zbiór par klucz/wartość konfigurujących wywołanie funkcji
         * @param {string} settings.url
         *     adres strony z formularzem do wgrywania plików. Po pomyślnym
         *     wgraniu pliku strona musi za pomocą rejestru 
         *     {@link Scholar.Data} ustawić dla klucza podanego 
         *     w <code>window.location.hash.substr(1)</code> wartość będącą 
         *     rekordem dodanego pliku.
         * @param {string} settings.title
         *     tytuł okienka modalnego
         * @param {number} [settings.width=480]
         *     domyślna szerokość okienka modalnego
         * @param {number} [settings.height=240]
         *     domyślna wysokość okienka modalnego
         */
        openFileUploader: function(widget, settings) { // {{{
            var iframe,
                key = '!' + String(Math.random()).substr(2);

            Scholar.modal.open({
                title: settings.title || '',
                width: settings.width || 480,
                height: settings.height || 240,
                iframe: {
                    url: settings.url + '#' + key,
                    load: function() {
                        // poniewaz upload pliku przeladowuje strone, zostanie
                        // jeszcze raz odpalona metoda load, trzeba sprawdzic,
                        // czy do rejestru nie zostal dodany klucz z informacja
                        // o dodanym pliku
                        var dialog = this.parentDialog,
                            data = new Scholar.Data(this.contentWindow),
                            file = data.get(key)

                        if (file) {
                            widget.add(file.id, file);
                            widget.redraw();
                            dialog.close();
                        } else {
                            iframe = $(this);
                            iframe.contents().find('[type="submit"]').hide();

                            dialog.status('');
                            dialog.button('apply').removeClass('disabled');
                        }
                    }
                },
                buttons: {
                    apply: {
                        label: widget.translate('Upload'),
                        disabled: true,
                        click: function() {
                            this.parentDialog.status(widget.translate('Uploading file...'));
                            this.parentDialog.button('apply').addClass('disabled');
                            iframe.contents().find('form').submit();
                        }
                    },
                    cancel: 'cancel'
                },
                translate: widget.translate
            });
        } // }}}
    },
    /**
     * @namespace
     */
    formElements: {
        /**
         * Umieszcza w podanym selektorze widget zarządzający załącznikami.
         * @param {string|jQuery|element} target
         *     element dokumentu, do którego zostanie podpięty widget
         * @param {string} name
         *     przedrostek używany w nazwie we wszystkich polach formularza
         *     generowanych przez ten widget
         * @param {Array} [values]
         */
        files: function(target, name, settings, values) { // {{{
            var labels = new Scholar.IdSet,
                language = settings.language;

            settings.header = ['', 'File name <span class="form-required">*</span>', 'Size'];
            settings.templates = [
                '',
                function(item) {
                    var label = labels.get(item.id);
                    if (typeof label === 'undefined') {
                        label = item.filename;
                        labels.add(item.id, label);
                    }
                    var fieldname = name + '[' + item.id + ']';

                    return [
                        '<input type="hidden" name="' + fieldname + '[id]" value="' + item.id + '"/>',
                        $('<input type="text" name="' + fieldname + '[label]" class="form-text" />')
                            .val(label ? label : '')
                            .change(function() {
                                labels.add(item.id, this.value);
                            }),
                        '<div class="description">' + String(item.filename).replace(/</g, '&lt;') + '</div>'
                    ];
                },
                function(item) {
                    // poza rozmiarem pliku dodaj jeszcze ukryte pola przechowujace
                    // nazwe i rozmiar pliku
                    var fieldname = name + '[' + item.id + ']';
                    return Scholar.str.filesize(item.size)
                         + '<input type="hidden" name="' + fieldname + '[filename]" value="' + item.filename + '" />'
                         + '<input type="hidden" name="' + fieldname + '[size]" value="' + item.size + '" />';
                }
            ];
            settings.showOnInit = false;
            settings.weightTemplate = name + '[{ id }][weight]';
            settings.translate = function (text) {
                return Scholar.i18n.tr(text);
            }

            var widget = new Scholar.SortableMultiselect(target, settings);

            widget.setButtons([
                {
                    label: widget.translate('Select file'),
                    click: function() {
                        Scholar.mixins.openItemPicker(widget, {
                            url: settings.urlFileSelect,
                            width: 480,
                            height: 240,
                            title: $(this).html() + ' (' + language.name + ')'
                        });
                        return false;
                    }
                },
                {
                    label: widget.translate('Upload file'),
                    click: function() {
                        Scholar.mixins.openFileUploader(widget, {
                            url: settings.urlFileUpload,
                            width: 480,
                            height: 240,
                            title: $(this).html()
                        });
                        return false;
                    }
                }
            ]);

            // ustaw wartosc poczatkowa
            if (values && values.length) {
                for (var i = 0, n = values.length; i < n; ++i) {
                    var value = values[i];
                    widget.add(value.id, value);
                    // dodaj etykiete
                    if (value.label) {
                        labels.add(value.id, value.label);
                    }
                }

                widget.redraw();
            }
        }, // }}}
        /**
         * @param target selektor
         * @param name prefiks do nazywania pól formulrza
         * @param url  adres URL strony z itemPickerem do wyboru osób
         * @param {Array} [items] wartość początkowa
         */
        people: function(target, name, url, items) { // {{{
            var widget = new Scholar.SortableMultiselect(target, {
                header: ['', 'Name'],
                templates: ['', function(item) {
                    var field = name + '[' + item.id + ']',
                        first = String(item.first_name).replace(/"/g, '&quot;'),
                        last  = String(item.last_name).replace(/"/g, '&quot;');

                    return '<input type="hidden" name="' + field + '[id]" value="' + item.id + '" />'
                         + '<input type="hidden" name="' + field + '[first_name]" value="' + first + '" />'
                         + '<input type="hidden" name="' + field + '[last_name]" value="' + last + '" />'
                         + first + ' ' + last;
                }],
                showOnInit: false,
                weightTemplate: name + '[{ id }][weight]'
            });

            widget.setButtons([
                {
                    label: widget.translate('Select people'),
                    click: function() {
                        Scholar.mixins.openItemPicker(widget, {
                            url: url,
                            width: 480,
                            height: 240,
                            title: $(this).html()
                        });
                        return false;
                    }
                }
            ]);

            if (items && items.length) {
                console.log(items);
                for (var i = 0, n = items.length; i < n; ++i) {
                    var item = items[i];
                    widget.add(item.id, item);
                }

                widget.redraw();
            }
        }, // }}}
        /**
         * Przekształca tablicę, tak by komórki z pierwszej kolumny stały
         * się zakładkami pionowymi, po kliknięciu których wuswietlona
         * zostaje zawartość komórki z drugiej kolumny tego samego wiersza
         * znajdującego się w oryginalnej tabeli.
         * Zawartość pojedynczej komórki z pierwszej kolumny jest owinięta w
         * DIV.scholar-vtable-vtab, a drugiej w DIV.scholar-vtable-pane.
         *
         * @param {string|jQuery|element} target
         *     element TABLE dokumentu, która ma zostać przetworzona
         */
        vtable: function(target) { // {{{
            $(target).filter('table:not(.vtable-processed)').each(function() {
                $(this).children('tbody').each(function() {
                    var tbody = $(this),
                        trows = $(this).children('tr'),
                        tr    = $('<tr/>'),
                        vtabs = $('<td class="vtable-vtabs" rowspan=' + trows.size() + '/>').appendTo(tr),
                        panes = $('<td class="vtable-panes"/>').appendTo(tr),
                        active;

                    trows.each(function() {
                        var tds  = $(this).children('td'),
                            id   = $(this).attr('id'),
                            vtab = $('<div class="vtable-vtab"/>'),
                            pane = $('<div class="vtable-pane"/>');
                    
                        // przenies atrybut id z wiersza, do diva z trescia drugiej
                        // kolumny. Chodzi o to, zeby w przypadku podania id elementu scroll
                        // okna byl ustawiony na gore tabeli.
                        pane.attr('id', id).data('vtab', vtab);

                        $(tds.get(0)).contents().appendTo(vtab);
                        $(tds.get(1)).contents().appendTo(pane);

                        if (!active) {
                            active = vtab;
                            vtab.addClass('active');
                            pane.css('display', 'block');
                        } else {
                            pane.css('display', 'none');    
                        }

                        vtab.click(function() {
                            if (active) {
                                active.removeClass('active');
                            }

                            panes.children().css('display', 'none');
                            pane.css('display', 'block');

                            active = vtab.addClass('active');
                        });

                        vtab.appendTo(vtabs);
                        pane.appendTo(panes);
                    });

                    // jezeli w hashu adresu znajduje sie poprawny identyfikator aktywnej
                    // zakladke, uczyn ja aktywna.
                    var hash = document.location.hash.substr(1);

                    // jezeli podano wersje identyfikatora, ktora nie powoduje przesuniecia
                    // scrolla okna, pomin wykrzyknik
                    if (hash.charAt(0) == '!') {
                        hash = hash.substr(1);
                    }

                    if (hash.length) {
                        panes.children().each(function() {
                            var j = $(this);
                            if (j.attr('id') == hash) {
                                j.data('vtab').click();
                            }
                        });
                    }

                    tbody.empty().append(tr);
                });
            }).addClass('vtable vtable-processed');
        } // }}}
    }
}

Scholar.modal = new Scholar.Dialog;

