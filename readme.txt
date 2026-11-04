=== Sbek ===
Contributors: helmab
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 8.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Khmer-first news theme: hero grid, video and colored category blocks, breaking-news ticker and infinite-scroll archives, in about 16 KB.

== Description ==

Sbek is a classic WordPress theme for news sites, designed for Khmer and equally at home in English. The home page has a hero grid, a video block, colored category blocks, and a breaking-news ticker. Archives use infinite scroll and can switch between list and grid. Articles get a clean reading page with a Khmer word-break toggle, related stories and threaded comments.

**Editorial layout**

* Sticky posts fill the hero; posts in the *Video* format fill the video block.
* Each category has its own accent color and homepage position.
* Breadcrumbs, a two-level menu that moves extra items into a "More" menu, and widget areas for a sidebar and three ad slots (empty slots take no space).
* Block patterns for articles (Key points, Fact box, Read also, Quote with source, Editor's note) under *Sbek news* in the inserter, and matching block styles for groups, headings, quotes, lists, separators and images.
* An optional header image behind the logo bar (Appearance > Customize > Header Image).

**For readers**

* Infinite scroll that switches to a "Load more" button, so the footer stays reachable; plain pagination without JavaScript.
* A list / grid switch on archives that is remembered.
* A "show word breaks" switch on Khmer articles that use zero-width spaces.

**Fast and accessible**

* 11 self-hosted Khmer fonts; visitors never contact a font service, and only the fonts in use are downloaded.
* About 10 KB of CSS and 6 KB of JavaScript (gzipped), with no jQuery.
* Skip link, visible focus styles, a pausable carousel and ticker, announced feed updates and reduced-motion support.

**One settings page**

*Appearance > Sbek Theme* sets the logo and site icon, brand colors and fonts (with a live preview), homepage sections, category colors and order, contact details, the copyright line and social links.

== Installation ==

1. In your admin area, go to Appearance > Themes and click Add New Theme.
2. Click Upload Theme, choose the theme zip file, then click Install Now.
3. Click Activate.
4. For Khmer dates and admin strings, choose Khmer under Settings > General > Site Language.
5. Open *Appearance > Sbek Theme* and work through the tabs.
6. Under Appearance > Menus, assign menus to *Primary menu* and *Footer links*.

== Frequently Asked Questions ==

= How do I choose which categories appear on the home page? =

In *Appearance > Sbek Theme > Homepage*, give categories a homepage position (1, 2, 3…). Up to six categories are shown, and empty ones are skipped. Without positions, the first top-level categories with posts are shown.

= What fills the breaking-news ticker? =

Posts with the post meta `_sbek_is_breaking` set to 1, published within the time set under *Homepage > Freshness*. Without such posts the ticker is hidden.

= Does the theme collect any data? =

No. The theme stores only its own settings, sets no cookies of its own and loads nothing from other sites: fonts, icons and scripts are all bundled. Comments use WordPress's own comment form.

== Changelog ==

= 1.0.0 - 2026-11-03 =
* Initial release.

== Copyright ==

Sbek WordPress Theme, (C) 2026 Hel Mab
Sbek is distributed under the terms of the GNU GPL.

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

This theme bundles the following third-party resources:

Tailwind CSS, Copyright (c) Tailwind Labs, Inc.
License: MIT
Source: https://tailwindcss.com
Used for: the base styles and utility classes compiled into assets/css/main.css, admin.css and editor.css.

Lucide icons, Copyright (c) Lucide Contributors 2022; portions Copyright (c) Cole Bemis 2013-2022 (Feather)
License: ISC
Source: https://lucide.dev
Used for: the interface icons in inc/icons.php.

Simple Icons
License: CC0 1.0 Universal
Source: https://simpleicons.org
Used for: the social network logos in inc/icons.php. The brand names and logos are trademarks of their owners.

Fonts (Khmer subsets from Google Fonts, in assets/fonts/; full license texts in assets/fonts/licenses/)
License: SIL Open Font License 1.1, https://openfontlicense.org

* Battambang, Copyright 2019 The Battambang Project Authors (https://github.com/danhhong/Battambang)
* Bayon, Copyright 2019 The Bayon Project Authors (https://github.com/danhhong/Bayon)
* Hanuman, Copyright 2019 The Hanuman Project Authors (https://github.com/danhhong/Hanuman)
* Kantumruy Pro, Copyright 2022 The Kantumruy Pro Project Authors (https://github.com/googlefonts/kantumruy)
* Kdam Thmor Pro, Copyright 2022 The Kdam Thmor Project Authors (https://www.github.com/sovichet/kdam-thmor-pro)
* Koulen, Copyright 2019 The Koulen Project Authors (https://github.com/danhhong/Koulen)
* Moul, Copyright 2020 The Moul Project Authors (https://github.com/danhhong/Moul)
* Nokora, Copyright 2019 The Nokora Project Authors (https://github.com/danhhong/Nokora)
* Noto Sans Khmer, Copyright 2022 The Noto Project Authors (https://github.com/notofonts/khmer)
* Siemreap, Copyright (c) 2010, Danh Hong (khmertype.blogspot.com)
* Suwannaphum, Copyright 2019 The Suwannaphum Project Authors (https://github.com/danhhong/Suwannaphum)

Images

* screenshot.png, Copyright 2026 Hel Mab
  License: GPLv2 or later
  Shows the theme with sample content; the sample images and logo in it were drawn for this theme.
* assets/images/theme-options-banner.png, Copyright 2026 Hel Mab
  License: GPLv2 or later
