# Fonts

Khmer-script subsets (woff2) served from this theme — visitors' browsers never contact a
font CDN. All are licensed under the **SIL Open Font License 1.1**. Files were taken from Google Fonts' Khmer subsets.

| File | Family | Copyright (verbatim from its OFL.txt) |
| --- | --- | --- |
| battambang-khmer-400/700.woff2 | Battambang | Copyright 2019 The Battambang Project Authors (https://github.com/danhhong/Battambang) |
| koulen-khmer-400.woff2 | Koulen | Copyright 2019 The Koulen Project Authors (https://github.com/danhhong/Koulen) |
| kantumruy-pro-khmer-var.woff2 | Kantumruy Pro | Copyright 2022 The Kantumruy Pro Project Authors (https://github.com/googlefonts/kantumruy) |
| noto-sans-khmer-khmer-var.woff2 | Noto Sans Khmer | Copyright 2022 The Noto Project Authors (https://github.com/notofonts/khmer) |
| hanuman-khmer-var.woff2 | Hanuman | Copyright 2019 The Hanuman Project Authors (https://github.com/danhhong/Hanuman) |
| nokora-khmer-var.woff2 | Nokora | Copyright 2019 The Nokora Project Authors (https://github.com/danhhong/Nokora) |
| suwannaphum-khmer-400/700.woff2 | Suwannaphum | Copyright 2019 The Suwannaphum Project Authors (https://github.com/danhhong/Suwannaphum) |
| siemreap-khmer-400.woff2 | Siemreap | Copyright (c) 2010, Danh Hong (khmertype.blogspot.com) |
| moul-khmer-400.woff2 | Moul | Copyright 2020 The Moul Project Authors (https://github.com/danhhong/Moul) |
| bayon-khmer-400.woff2 | Bayon | Copyright 2019 The Bayon Project Authors (https://github.com/danhhong/Bayon) |
| kdam-thmor-pro-khmer-400.woff2 | Kdam Thmor Pro | Copyright 2022 The Kdam Thmor Project Authors (https://www.github.com/sovichet/kdam-thmor-pro) |

The full license text for each family is in `licenses/<family>-OFL.txt`.

`@font-face` rules live in `assets/css/src/fonts.css` (Khmer `unicode-range` only, so Latin
text falls through to the Latin font stack). Which fonts the site uses is chosen in
**Sbek Theme → Design** (`inc/design.php`); browsers download only the families in use.

To add a font: drop its Khmer-subset woff2 here, add an `@font-face` to `fonts.css`, add an
entry to `sbek_font_choices()` in `inc/design.php`, list it above, and rebuild CSS.
