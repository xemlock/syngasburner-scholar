{
    "Formatowanie tekstu": {
        "[b]": {
            "syn"  : "[b]{tekst}[/b]",
            "desc" : "Pogrubia podany tekst",
            "ex"   : "[b]Tekst pogrubiony[/b]",
            "res"  : "<b>Tekst pogrubiony</b>"
        },
        "[i]": {
            "syn": "[i]{tekst}[/i]",
            "desc": "Pochyla podany tekst",
            "ex": "[i]Tekst pochylony[/i]",
            "res": "<i>Tekst pochylony</i>"
        },
        "[s]": {
            "syn": "[s]{tekst}[/s]",
            "desc": "Przekreśla podany tekst",
            "ex": "[s]Tekst przekreślony[/s]",
            "res": "<s>Tekst przekreślony</s>"
        },
        "[u]": {
            "syn": "[u]{tekst}[/u]",
            "desc": "Podkreśla podany tekst",
            "ex": "[u]Tekst podkreślony[/u]",
            "res": "<u>Tekst podkreślony</u>"
        },
        "[sub]": {
            "syn": "[sub]{tekst}[/sub]",
            "desc": "Indeks dolny",
            "ex": "CO[sub]2[/sub]",
            "res": "CO<sub>2</sub>"
        },
        "[sup]": {
            "syn": "[sup]{tekst}[/sup]",
            "desc": "Indeks górny",
            "ex": "200 mg/Nm[sup]3[/sup]",
            "res": "200 mg/Nm<sup>3</sup>"
        },
        "[color]": {
            "syn": "[color]{tekst}[/color]",
            "desc": "Kolor tekstu",
            "note": "Podany kolor może być nazwą koloru zdefiniowaną w CSS Level 3 (<a href=\"http://www.w3.org/TR/css3-color\">http://www.w3.org/TR/css3-color</a>), albo kolorem w postaci szesnastkowej <code>#rrggbb</code> lub <code>#rgb</code>.",
            "ex": "[color=\"#0000FF\"]Niebieski tekst[/color]",
            "res": "<span style=\"color:#0000FF\">Niebieski tekst</span>"
        },
        "[size]": {
            "syn": "[size=\"{rozmiar}\"]{tekst}[/size]",
            "desc": "Rozmiar tekstu",
            "note": "Nowy rozmiar tekstu musi być podany w procentach.",
            "ex": "[size=\"80%\"]1[/size][size=\"120%\"]2[/size][size=\"160%\"]3[/size][size=\"200%\"]4[/size]",
            "res": "<span style=\"font-size:80%\">1</span><span style=\"font-size:120%\">2</span><span style=\"font-size:160%\">3</span><span style=\"font-size:200%\">4</span>"
        }
    },
    "Odnośniki, obrazy i multimedia": {
        "[img]": {
            "syn": "[img]{adres_obrazu}[/img]",
            "desc": "Obraz",
            "note": "Podany adres musi być poprawnym względnym lub absolutnym (rozpoczynającym się od http(s)://) adresem URL, w przeciwnym razie wynik będzie pusty. Jeżeli podano adres względny, zostanie on automatycznie przekształcony w bezwzględny poprzez dodanie do jego początku adresu bezwzględnego bieżącej instalacji Drupala.",
            "attrs": {
                "width":  {"optional":true, "desc": "szerokość obrazu"},
                "height": {"optional":true, "desc": "wysokość obrazu"},
                "align":  {"optional":true, "desc": "wyrównanie obrazu do lewej lub prawej", "values": ["left", "right"]},
                "title":  {"optional":true, "desc": "tytuł obrazu, umieszczony w atrybucie alt"}
            },
            "ex": "[img]etc13.png[/img]",
            "res": "<img src=\"etc13.png\" alt=\"\" />"
        },
        "[url]": {
            "syn": [
                "[url]{adres}[/url]",
                "[url=\"{adres}\"]{tytuł_odnośnika}[/url]"
            ],
            "desc": "Odnośnik",
            "note": "Podany adres musi być poprawnym względnym lub bezwzględnym (rozpoczynającym się od http(s)://) adresem URL. Jeżeli podano adres względny, zostanie on automatycznie przekształcony w bezwzględny poprzez dodanie do jego początku adresu bezwzględnego bieżącej instalacji Drupala.",
            "ex": [
                "[url]http://syngasburner.eu[/url]",
                "[url=\"http://syngasburner.eu\"]Strona projektu[/url]"
            ],
            "res": [
                "<a href=\"http://syngasburner.eu\">http://syngasburner.eu</a>",
                "<a href=\"http://syngasburner.eu\">Strona projektu</a>"
            ]
        },
        "[youtube]": {
            "syn"  : "[youtube]{identyfikator_filmu}[/youtube]",
            "desc" : "Film z serwisu YouTube",
            "block": true,
            "note" : "Aby zmienić wymiary filmu trzeba podać atrybuty określające szerokość i wysokość filmu, w przeciwym razie zostaną użyte wymiary domyślne 640x360 px. Poprawny identyfikator filmu składa się z 11 znaków: cyfr, liter małych i wielkich, myślników oraz podkreśleń.",
            "attrs": {
                "width":  {
                    "optional": true,
                    "desc": "szerokość filmu"
                },
                "height": {
                    "optional": true,
                    "desc": "wysokość filmu"
                }
            },
            "ex"   : "[youtube]Jie3noHnvG0[/youtube]",
            "res"  : "<img src=\"youtube.png\" alt=\"\" />"
        }
    },
    "Listy": {
        "[*]": {
            "syn"  : "[*]{tekst}",
            "block": true,
            "desc" : "Element listy",
            "note" : "Treść elementu listy kończy się albo znakiem nowej linii, albo znacznikiem kolejnego elementu, albo znacznikiem zamykającym listę."
        },
        "[li]": {
            "syn"  : "[li]{tekst}[/li]",
            "block": true,
            "desc" : "Element listy"
        },
        "[list]": {
            "syn"  : [
                "[list]\n{elementy_listy}\n[/list]",
                "[list={typ_listy}]\n{elementy_listy}\n[/list]"
            ],
            "block": true,
            "desc" : "Lista wypunktowana lub numerowana",
            "note" : "Jeżeli nie podano typu listy będzie ona wypunktowana. Typ listy numerowanej może przyjąć jedną z następujących wartości: 1 (numerowanie liczbami arabskimi), A (numerowanie wielkimi literami), a (numerowanie małymi literami), I (numerowanie liczbami rzymskimi), i (numerowanie małymi liczbami rzymskimi). Numerowanie listy rozpoczyna się od wartości 1. Aby rozpocząć numerację od innej wartości należy określić ją w atrybucie start.",
            "attrs": {
                "start": {
                    "optional": true,
                    "default": 1,
                    "desc": "początek numeracji"
                }
            },
            "ex"   : [
                "[list]\n[*] Punkt pierwszy\n[*] Punkt drugi\n[*] Punkt trzeci\n[/list]",
                "[list=1]\n[*] Punkt pierwszy\n[*] Punkt drugi\n[*] Punkt trzeci\n[/list]",
                "[list=A]\n[*] Punkt pierwszy\n[*] Punkt drugi\n[*] Punkt trzeci\n[/list]",
                "[list=A start=4]\n[*] Punkt czwarty\n[*] Punkt piąty\n[*] Punkt szósty\n[/list]"
            ],
            "res"  : [
                "<ul><li>Punkt pierwszy</li><li>Punkt drugi</li><li>Punkt trzeci</li></ul>",
                "<ol><li>Punkt pierwszy</li><li>Punkt drugi</li><li>Punkt trzeci</li></ol>",
                "<ol type=\"A\"><li>Punkt pierwszy</li><li>Punkt drugi</li><li>Punkt trzeci</li></ol>",
                "<ol type=\"A\" start=\"4\"><li>Punkt czwarty</li><li>Punkt piąty</li><li>Punkt szósty</li></ol>"
            ]
        }
    },
    "Formatowanie zaawansowane": {
        "[code]": {
            "syn"  : "[code]{kod_źródłowy}[/code]",
            "block": true,
            "desc" : "Kod źródłowy",
            "note" : "Wyświetla podany tekst jako blok kodu źródłowego zachowując formatowanie i używajac czcionki o ustalonej szerokości znaków.",
            "ex"   : "[code]#ifdef __cplusplus\ntypedef bool boolean;\n#else\ntypedef enum {false, true} boolean;\n#endif[/code]",
            "res"  : "<pre><code>#ifdef __cplusplus\ntypedef bool boolean;\n#else\ntypedef enum {false, true} boolean;\n#endif</code></pre>"
        },
        "[quote]": {
            "syn"  : "[quote]{tekst}[/quote]",
            "block": true,
            "desc" : "Cytat",
            "note" : "Wyświetla podany tekst jako wyróżniony blok cytatu.",
            "ex"   : "[quote]Artificial Intelligence is no match for natural stupidity.[/quote]",
            "res"  : "<div class=\"quote\"><blockquote><div>Artificial Intelligence is no match for natural stupidity.</div></blockquote></div>"
        },
        "[noparse]" : {
            "syn"  : "[noparse]{tekst}[/noparse]",
            "desc" : "Wyłącza interpretowanie znaczników",
            "ex"   : "[noparse][url]http://www.fuw.edu.pl[/url][/noparse]",
            "res"  : "[url]http://www.fuw.edu.pl[/url]"
        },
        "[nonl2br]": {
            "syn"  : "[nonl2br]{tekst}[/nonl2br]",
            "desc" : "Ignoruje znaki nowego wiersza",
            "note" : "Wszystkie przejścia do nowego wiersza między znacznikiem otwierającym i zamykającym zostają zignorowane. Nie dotyczy znaczników <code>[br]</code>."
        },
        "[br]": {
            "syn"  : "[br]",
            "desc" : "Przejście do nowej linii",
            "note" : "Znacznik ten nie posiada znacznika zamykającego.",
            "ex"   : "Ta część zdania znajduje się przed przejściem do nowej linii,[br]a ta po.",
            "res"  : "Ta część zdania znajduje się przed przejściem do nowej linii,<br/>a ta po.",
        },
        "\\[": {
            "syn"  : "\\[",
            "desc" : "Lewy nawias kwadratowy",
            "note" : "Równoznaczne z <code>[noparse][[/noparse]</code>."
        },
        "\\]": {
            "syn"  : "\\]",
            "desc" : "Prawy nawias kwadratowy",
            "note" : "Równoznaczne z <code>[noparse]][/noparse]</code>."
        }
    },
    
}
