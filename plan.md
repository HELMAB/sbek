As a senior WordPress developer with 15+ years of experience, I approach a high-traffic news portal
like "Sabay News" differently than a standard brochure site. We need to prioritize performance (Core
Web Vitals), ad placement strategy, content hierarchy, and scalability.

Since we are using Tailwind CSS, we will move away from traditional heavy page builders (like
Elementor) and build a lightweight, custom hybrid theme.

Here is the comprehensive architectural plan to build this in your current environment.

Phase 1: Environment & Theme Architecture
We won't use a pre-made theme. We will build a custom theme to ensure the code is lean.

1. Directory Setup
   We will create a new theme directory: /wp-content/themes/sabay-clone.

2. Modern Build Stack (Node.js + Tailwind)
   Since WordPress doesn't understand Tailwind natively, we need a compilation step.

- Tooling: We will use npm to manage dependencies.
- Compiler: We will set up PostCSS and Tailwind CLI to watch for changes in our PHP files and
  compile a style.css.
- File Structure:


    1     sabay-clone/
    2     ├── assets/
    3     │   ├── css/
    4     │   │   └── src/ (Tailwind input)
    5     │   ├── js/
    6     │   └── images/
    7     ├── template-parts/
    8     │   ├── header/
    9     │   ├── footer/

10 │ └── home/ (Specific layouts for news sections)
11 ├── inc/ (Custom PHP classes/functions)
12 ├── functions.php
13 ├── style.css (Generated)
14 └── tailwind.config.js

Phase 2: Data Modeling (The "Senior" approach)
Before design, we define data. Sabay has specific content types.

1. Taxonomies & Categories

- Primary Categories: Entertainment, Technology, Life, Sport, Auto.
- Tags: Crucial for "Trending" topics.

2. Custom Fields (ACF or Native)
   We need specific metadata for news posts:

- Video Source: (If the post is a video).
- Trending Score: (To manually or automatically float posts to the top).
- Sponsored Flag: To style "Advertorial" content differently (Compliance).

Phase 3: Visual Components (Tailwind Implementation)
We will componentize the UI to reuse classes.

1. Typography (Khmer Support)
   Sabay relies heavily on readable Khmer fonts.

- Action: Import Google Fonts (Battambang or Kantumruy) in tailwind.config.js and extend the font
  family.

2. Layouts

- The "Hero" Grid: A 3-column layout where the center post is largest. We will use CSS Grid:
  grid-cols-12.
- The "Ticker": A scrolling marquee for breaking news.
- Sticky Sidebar: For ads that stay visible while reading.

Phase 4: Development Steps (Execution Plan)

I will guide you through these specific steps to execute this plan:

1.  Scaffold the Theme: Create the folder and necessary WP files.
2.  Initialize Tailwind: Set up package.json and the build process.
3.  Base Setup: Enqueue scripts in functions.php.
4.  Header Development: Implement the mega-menu and breaking news ticker.
5.  Homepage Construction: Build the custom query loops for different categories (e.g., "Technology"
    section vs "Entertainment" section).

Phase 5: Performance & SEO (The "Sabay" Scale)

- Image Optimization: We will use native lazy loading (loading="lazy") and correct aspect ratios to
  prevent Cumulative Layout Shift (CLS).
- Caching: Essential for news sites.
