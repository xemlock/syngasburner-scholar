{
    "Formatowanie tekstu": {
        "b": {
            "syn": "[b]{tekst}[/b]",
            "desc": "Pogrubia podany tekst",
            "ex": "[b]Tekst pogrubiony[/b]",
            "res": "<b>Tekst pogrubiony</b>"
        },
        "i": {
            "syn": "[i]{tekst}[/i]",
            "desc": "Pochyla podany tekst",
            "ex": "[i]Tekst pochylony[/i]",
            "res": "<i>Tekst pochylony</i>"
        },
        "s": {
            "syn": "[s]{tekst}[/s]",
            "desc": "Przekreśla podany tekst",
            "ex": "[s]Tekst przekreślony[/s]",
            "res": "<s>Tekst przekreślony</s>"
        },
        "u": {
            "syn": "[u]{tekst}[/u]",
            "desc": "Podkreśla podany tekst",
            "ex": "[u]Tekst podkreślony[/u]",
            "res": "<u>Tekst podkreślony</u>"
        },
        "sub": {
            "syn": "[sub]{tekst}[/sub]",
            "desc": "Indeks dolny",
            "ex": "CO[sub]2[/sub]",
            "res": "CO<sub>2</sub>"
        },
        "sup": {
            "syn": "[sup]{tekst}[/sup]",
            "desc": "Indeks górny",
            "ex": "200 mg/Nm[sup]3[/sup]",
            "res": "200 mg/Nm<sup>3</sup>"
        },
        "color": {
            "syn": "[color]{tekst}[/color]",
            "desc": "Kolor tekstu",
            "notes": "Podany kolor może być nazwą koloru zdefiniowaną w CSS Level 3 (<a href=\"http://www.w3.org/TR/css3-color\">http://www.w3.org/TR/css3-color</a>), albo kolorem w postaci szesnastkowej <code>#rrggbb</code> lub <code>#rgb</code>.",
            "ex": "[color=\"#0000FF\"]Niebieski tekst[/color]",
            "res": "<span style=\"color:#0000FF\">Niebieski tekst</span>"
        },
        "size": {
            "syn": "[size=\"{rozmiar}\"]{tekst}[/size]",
            "desc": "Rozmiar tekstu",
            "notes": "Nowy rozmiar tekstu musi być podany w procentach.",
            "ex": "[size=\"80%\"]1[/size][size=\"120%\"]2[/size][size=\"160%\"]3[/size][size=\"200%\"]4[/size]",
            "res": "<span style=\"font-size:80%\">1</span><span style=\"font-size:120%\">2</span><span style=\"font-size:160%\">3</span><span style=\"font-size:200%\">4</span>"
        }
    },
    "Odnośniki, obrazy i multimedia": {
        "img": {
            "syn": "[img]{adres_obrazu}[/img]",
            "desc": "Obraz",
            "notes": "Podany adres musi być poprawnym względnym lub absolutnym (rozpoczynającym się od http(s)://) adresem URL, w przeciwnym razie wynik będzie pusty.",
            "attrs": {
                "width":  {"optional":true, "desc": "szerokość obrazu"},
                "height": {"optional":true, "desc": "wysokość obrazu"},
                "align":  {"optional":true, "desc": "wyrównanie obrazu do lewej lub prawej", "values": ["left", "right"]},
                "title":  {"optional":true, "desc": "tytuł obrazu, umieszczony w atrybucie alt"}
            },
            "ex": "[img]etc13.png[/img]",
            "res": "<img src=\"etc13.png\" alt=\"\" />"
        },
        "url": {
            "syn": [
                "[url]{adres}[/url]",
                "[url=\"{adres}\"]{tytuł_odnośnika}[/url]"
            ],
            "desc": "Odnośnik",
            "notes": "Podany adres musi być poprawnym względnym lub absolutnym (rozpoczynającym się od http(s)://) adresem URL. Adres względny zostanie automatycznie przekształcony na absolutny rozpoczynający się od ścieżki ",
            "ex": [
                "[url]http://syngasburner.eu[/url]",
                "[url=\"http://syngasburner.eu\"]Strona projektu[/url]"
            ],
            "res": [
                "<a href=\"http://syngasburner.eu\">http://syngasburner.eu</a>",
                "<a href=\"http://syngasburner.eu\">Strona projektu</a>"
            ]
        },
        "youtube": {
            "syn": "[youtube]{identyfikator_filmu}[/youtube]",
            "desc": "Film z serwisu YouTube",
            "notes": "Aby zmienić wymiary filmu trzeba podać atrybuty width i height, w przeciwym razie zostaną użyte wymiary domyślne 640x360 px. Poprawny identyfikator filmu składa się z 11 znaków.",
             "attrs": {
                "width":  {"optional":true, "desc": "szerokość filmu"},
                "height": {"optional":true, "desc": "wysokość filmu"}
            },
            "ex": "[youtube]Jie3noHnvG0[/youtube]",
            "res": "<img src=\"youtube.png\" alt=\"\" />"
        }
    },
    "Listy": {
        "*": {
            "syn" : "[*]{tekst}",
            "desc": "Element listy",
            "notes": "Treść elementu listy kończy się albo znakiem nowej linii, albo znacznikiem kolejnego elementu, albo znacznikiem zamykającym listę."
        },
        "li": {
            "syn" : "[li]{tekst}[/li]",
            "desc": "Element listy"
        },
        "list": {
            "syn" : [
                "[list]\n{elementy_listy}\n[/list]",
                "[list={typ_listy}]\n{elementy_listy}\n[/list]"
            ],
            "desc": "Lista wypunktowana lub numerowana",
            "notes": "Jeżeli nie podano typu listy będzie ona wypunktowana. Typ listy numerowanej może przyjąć jedną z następujących wartości: 1 (numerowanie liczbami arabskimi), A (numerowanie wielkimi literami), a (numerowanie małymi literami), I (numerowanie liczbami rzymskimi), i (numerowanie małymi liczbami rzymskimi). Numerowanie listy rozpoczyna się od wartości 1. Aby rozpocząć numerację od innej wartości należy podać tę wartość w atrybucie start.",
            "attrs": {
                "start": {
                    "optional": true,
                    "default": 1,
                    "desc": "początek numeracji"
                }
            },
            "ex"  : [
                "[list]\n[*] Punkt pierwszy\n[*] Punkt drugi\n[*] Punkt trzeci\n[/list]",
                "[list=1]\n[*] Punkt pierwszy\n[*] Punkt drugi\n[*] Punkt trzeci\n[/list]",
                "[list=A]\n[*] Punkt pierwszy\n[*] Punkt drugi\n[*] Punkt trzeci\n[/list]",
                "[list=A start=4]\n[*] Punkt czwarty\n[*] Punkt piąty\n[*] Punkt szósty\n[/list]"
            ],
            "res" : [
                "<ul><li>Punkt pierwszy</li><li>Punkt drugi</li><li>Punkt trzeci</li></ul>",
                "<ol><li>Punkt pierwszy</li><li>Punkt drugi</li><li>Punkt trzeci</li></ol>",
                "<ol type=\"A\"><li>Punkt pierwszy</li><li>Punkt drugi</li><li>Punkt trzeci</li></ol>",
                "<ol type=\"A\" start=\"4\"><li>Punkt czwarty</li><li>Punkt piąty</li><li>Punkt szósty</li></ol>"
            ]
        }
    },
    "Formatowanie zaawansowane": {
        "code": {
            // The [code] tag switches to a fixed-width (monospace) font and preserves all spacing.
        },
        "quote": {
            // The [quote] tag allows you to attribute text to someone else.
        },
        "noparse" : {
            "desc" : "Wyłącza interpretowanie znaczników BBCode."
        },
        "nonl2br": {
            "desc" : "Wszystkie przejścia do nowego wiersza między znacznikiem otwierającym i zamykającym zostają zignorowane. Nie dotyczy znaczników <code>[br]</code>."
        },
        "br": {
            "desc" : "Przechodzi do nowego wiersza.",
        },
        "\\[": {
            "syn"  : "\\[",
            "desc" : "Lewy nawias kwadratowy",
            "notes": "Równoznaczne z <code>[noparse][[/noparse]</code>."
        },
        "\\]": {
            "syn"  : "\\]",
            "desc" : "Prawy nawias kwadratowy",
            "notes": "Równoznaczne z <code>[noparse]][/noparse]</code>."
        }
    }
}
