{
    "Formatowanie tekstu": {
        "b": {
            "syn": "[b]tekst[/b]",
            "desc": "Pogrubia podany tekst",
            "ex": "[b]Tekst pogrubiony[/b]",
            "res": "<b>Tekst pogrubiony</b>"
        },
        "i": {
            "syn": "[i]tekst[/i]",
            "desc": "Pochyla podany tekst",
            "ex": "[i]Tekst pochylony[/i]",
            "res": "<i>Tekst pochylony</i>"
        },
        "s": {
            "syn": "[s]tekst[/s]",
            "desc": "Przekreśla podany tekst",
            "ex": "[s]Tekst przekreślony[/s]",
            "res": "<s>Tekst przekreślony</s>"
        },
        "u": {
            "syn": "[u]tekst[/u]",
            "desc": "Podkreśla podany tekst",
            "ex": "[u]Tekst podkreślony[/u]",
            "res": "<u>Tekst podkreślony</u>"
        },
        "sub": {
            "syn": "[sub]tekst[/sub]",
            "desc": "Indeks dolny",
            "ex": "CO[sub]2[/sub]",
            "res": "CO<sub>2</sub>"
        },
        "sup": {
            "syn": "[sup]tekst[/sup]",
            "desc": "Indeks górny",
            "ex": "200 mg/Nm[sup]3[/sup]",
            "res": "200 mg/Nm<sup>3</sup>"
        },
        "color": {
            "syn": "[color]tekst[/color]",
            "desc": "Kolor tekstu",
            "notes": "Podany kolor może być nazwą koloru zdefiniowaną w CSS Level 3 (<a href=\"http://www.w3.org/TR/css3-color\">http://www.w3.org/TR/css3-color</a>), lub kolorem w postaci <code>#rrggbb</code> lub <code>#rgb</code>.",
            "ex": "[color=\"#0000FF\"]Niebieski tekst[/color]",
            "res": "<span style=\"color:#0000FF\">Niebieski tekst</span>"
        },
        "size": {
            "syn": "[size=\"rozmiar\"]tekst[/size]",
            "desc": "Rozmiar tekstu",
            "notes": "Nowy rozmiar tekstu musi być podany w procentach.",
            "ex": "[size=\"80%\"]1[/size][size=\"120%\"]2[/size][size=\"160%\"]3[/size][size=\"200%\"]4[/size]",
            "res": "<span style=\"font-size:80%\">1</span><span style=\"font-size:120%\">2</span><span style=\"font-size:160%\">3</span><span style=\"font-size:200%\">4</span>"
        }
    },
    "Odnośniki, obrazy i multimedia": {
        "img": {
            "syn": "[img]adres_obrazu[/img]",
            "desc": "Obraz",
            "notes": "Podany adres obrazu musi być poprawnym względnym lub absolutnym (rozpoczynającym się od http(s)://) adresem URL, w przeciwnym razie wynik będzie pusty.",
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
                "[url]adres[/url]",
                "[url=\"adres\"]tytuł_odnośnika[/url]"
            ],
            "desc": "Odnośnik",
            "notes": "Podany cel odnośnika musi być poprawnym względnym lub absolutnym (rozpoczynającym się od http(s)://) adresem URL, w przeciwnym razie wynik będzie pusty.",
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
            "syn": "[youtube]identyfikator_filmu[/youtube]",
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
    "Listy i wypunktowania": {
        "*": {
            "syn" : "[*] element",
            "desc": "Element listy",
            "notes": "Treść elementu listy kończy się na znaku nowej linii, na znaczniku kolejnego elementu lub na znaczniku zamykającym listę.",
            "ex"  : "[list]\n[*] Publikacje i prezentacja wyników\n[*]Wystąpienia na konferencjach, warsztatach i seminariach\n[/list]",
            "res" : "<ul><li>Publikacje i prezentacja wyników</li><li>Wystąpienia na konferencjach, warsztatach i seminariach</li>"
        },
        "item": {
            "syn" : "[item]element[/item]",
            "desc": "Element listy",

            "ex"  : "[list]\n[item][b]The International Conference on Thermochemical Conversion Science, tcbiomass2013[/b]\n03 - 06 września 2013, Chicago, USA[/item]\n[item][b]6th European Combustion Meeting ECM2013[/b]25 - 28 czwerca 2013, Lund, Szwecja[/item]\n[/list]",
            "res" : "<ul><li><b>The International Conference on Thermochemical Conversion Science, tcbiomass2013</b><br/>03 - 06 września 2013, Chicago, USA</li><li><b>6th European Combustion Meeting ECM2013</b><br/>25 - 28 czwerca 2013, Lund, Szwecja</li></ul>"
        },
        "list": {
            "syn" : "[list]\nelementy_listy\n[/list]",
            "desc": "Lista wypunktowana",
            
        },
        "list=1": {
            "syn" : "[list=1]\nelementy_listy\n[/list]",
            "desc": "Lista numerowana",
            "notes": "Numerowanie listy rozpoczyna się od wartości 1. Aby rozpocząć numerację od innej wartości należy podać tę wartość jako argument znacznika (np. [list=10])."
        },
        "list=A": {
            "syn" : "[list=A]\nelementy_listy\n[/list]",
            "desc": "Lista numerowana wielkimi literami",
            "attrs": {
                "start": {
                    "optional": true,
                    "default": 1,
                    "desc": "początek numeracji (1 odpowiada 'A', 2 &ndash; 'B', etc.)"
                }
            }
        },
        "list=a": {
            "syn" : "[list=a]\nelementy_listy\n[/list]",
            "desc": "Lista numerowana małymi literami",
            "attrs": {
                "start": {
                    "optional": true,
                    "default": 1,
                    "desc": "ppoczątek numeracji (1 odpowiada 'a', 2 &ndash; 'b', etc.)"
                }
            }
        },
        "list=I": {
            "syn" : "[list=I]\nelementy_listy\n[/list]",
            "desc": "Lista numerowana cyframi rzymskimi",
            "attrs": {
                "start": {
                    "optional": true,
                    "default": 1,
                    "desc": "początek numeracji (1 odpowiada 'I', 2 &ndash; 'II', etc.)"
                }
            }
        },
        "list=i": {
            "syn" : "[list=i]\nelementy_listy\n[/list]",
            "desc": "Lista numerowana małymi cyframi rzymskimi",
            "attrs": {
                "start": {
                    "optional": true,
                    "default": 1,
                    "desc": "początek numeracji (1 odpowiada 'i', 2 &ndash; 'ii', etc.)"
                }
            }
        }
    }
}
