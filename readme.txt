=== Sbek ===
Contributors: helmab
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 8.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Khmer-first news, bilingual by design. A fast classic news theme for Khmer / English sites, with no translation plugin needed.

== Description ==

Sbek is a classic WordPress theme for news sites that publish in Khmer first and English second. The home page has a hero grid, a video block, colored category blocks, and a breaking-news ticker. Archives use infinite scroll and can switch between list and grid. Articles get a clean reading page with a Khmer word-break toggle.

**Bilingual without a plugin**

* `/` is Khmer and `/en/` is English. Theme strings, dates and WordPress core strings follow the URL.
* Link each post to its translation. This drives the header language switcher and the hreflang tags.
* Categories and tags have an English name field.
* One click creates the English draft of a post, with its title, content, image, categories and tags copied over.

**Editorial controls**

* Sticky posts fill the hero; posts in the *Video* format fill the video block.
* Each category has its own accent color and homepage position.
* Popular-post score, a breaking-news ticker that expires on its own, a sponsored label shown on every card, and a source name and link.

**For readers**

* Breadcrumb, a two-level menu that moves extra items into a "More" menu, and sharing to Facebook, Telegram, X, email or copy link.
* Infinite scroll that switches to a "Load more" button, so the footer stays reachable.
* A double opt-in newsletter with unsubscribe links.

**Fast and accessible**

* 11 self-hosted Khmer fonts; visitors never contact a font CDN, and only the fonts in use are downloaded.
* About 10 KB of CSS and 6 KB of JavaScript (gzipped), with no jQuery.
* Skip link, visible focus styles, a pausable carousel and ticker, announced feed updates and reduced-motion support.

**One settings page**

The *Sbek Theme* admin page sets the site title and tagline in both languages, logo and icons, brand colors and fonts, homepage sections, category colors and order, contact details, social links and analytics. It can also import demo content and remove it again.

== Installation ==

1. In your admin area, go to Appearance > Themes and click Add New Theme.
2. Click Upload Theme, choose the theme zip file, then click Install Now.
3. Click Activate.
4. For Khmer dates and admin strings, install the Khmer language pack under Settings > General > Site Language.
5. Open *Sbek Theme* in the admin menu and work through the tabs.
6. Under Appearance > Menus, assign menus to *Primary menu (Khmer)*, *Primary menu (English)*, *Footer links (Khmer)* and *Footer links (English)*.

To see the theme with sample posts, use *Sbek Theme > Tools > Import demo content*. *Remove demo content* deletes exactly what the import created.

== Frequently Asked Questions ==

= Do I need a translation plugin? =

No. The theme handles Khmer and English itself. Set each post's language in the editor, and link translations in the *Translation* box.

= How do I choose which categories appear on the home page? =

In *Sbek Theme > Homepage*, give categories a homepage order (1, 2, 3…). Up to six categories are shown, and empty ones are skipped.

= Why doesn't the newsletter send emails? =

Subscribers must confirm by email, so your site needs to be able to send mail. Use an SMTP plugin if your host doesn't send it.

= Does the theme support comments? =

No. The theme has no comment form or comment list. Existing comments stay in the database.

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
Used for: the base styles and utility classes compiled into assets/css/main.css and assets/css/admin.css.

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
  Shows the theme with its own generated demo content.
* assets/images/demo-logo.png, Copyright 2026 Hel Mab
  License: GPLv2 or later
* assets/images/theme-options-banner.png, Copyright 2026 Hel Mab
  License: GPLv2 or later

The demo content images are not bundled: the demo importer draws them with the PHP GD extension when it runs.
