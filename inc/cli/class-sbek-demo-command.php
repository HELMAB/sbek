<?php
/**
 * WP-CLI: demo content for the Sbek theme.
 *
 *   wp sbek demo import [--skip-site-setup]   Create demo content.
 *   wp sbek demo reset [--yes]                Remove everything import created.
 *
 * Everything created is recorded in the `sbek_demo_manifest` option, so reset
 * deletes exactly that — never pre-existing posts, terms or media. Images are
 * generated with GD (no binary fixtures in the repo). All headlines and body
 * text are original placeholders.
 *
 * Loaded only under WP-CLI (see functions.php).
 *
 * @package sbek
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

/**
 * Create and remove Sbek demo content.
 */
class Sbek_Demo_Command {

	private const MANIFEST = 'sbek_demo_manifest';
	private const ZWSP     = "\u{200B}";

	/**
	 * Created object IDs, persisted for reset.
	 *
	 * @var array<string,array<int,int|string>>
	 */
	private array $manifest = array(
		'posts'       => array(),
		'attachments' => array(),
		'terms'       => array(),
		'menus'       => array(),
		'widgets'     => array(),
	);

	/** Attachment IDs per palette. @var array<string,int[]> */
	private array $images = array();

	private int $author = 0;

	/**
	 * Category definitions: slug => [km name, en name, color, image palette].
	 *
	 * @return array<string,array{0:string,1:string,2:string,3:array{0:string,1:string}}>
	 */
	private function categories(): array {
		return array(
			'entertainment' => array( 'កម្សាន្ត', 'Entertainment', '#fa5480', array( '#fa5480', '#7b2ff7' ) ),
			'technology'    => array( 'បច្ចេកវិទ្យា', 'Technology', '#4390f8', array( '#4390f8', '#0b2a5b' ) ),
			'life'          => array( 'ជីវិតនិងសង្គម', 'Life & Society', '#38c378', array( '#38c378', '#0f4c3a' ) ),
			'sport'         => array( 'កីឡា', 'Sport', '#f9a62b', array( '#f9a62b', '#b8360f' ) ),
		);
	}

	/**
	 * Import demo content.
	 *
	 * ## OPTIONS
	 *
	 * [--skip-site-setup]
	 * : Don't touch site options (language, permalinks, timezone, title, menus, widgets).
	 *
	 * [--posts=<n>]
	 * : Khmer posts per category.
	 * ---
	 * default: 24
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp sbek demo import
	 *
	 * @subcommand import
	 *
	 * @param string[]             $args       Positional args.
	 * @param array<string,string> $assoc_args Flags.
	 */
	public function import( array $args, array $assoc_args ): void {
		if ( get_option( self::MANIFEST ) ) {
			WP_CLI::error( 'Demo content is already imported. Run `wp sbek demo reset` first.' );
		}

		if ( ! function_exists( 'imagecreatetruecolor' ) ) {
			WP_CLI::error( 'The PHP GD extension is required to generate demo images.' );
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$admins = get_users(
			array(
				'role'   => 'administrator',
				'number' => 1,
				'fields' => 'ID',
			)
		);
		if ( ! $admins ) {
			WP_CLI::error( 'No administrator account found to author the demo posts.' );
		}
		$this->author = (int) $admins[0];
		wp_set_current_user( $this->author );

		$per_category = max( 9, absint( $assoc_args['posts'] ?? 24 ) );
		$site_setup   = ! WP_CLI\Utils\get_flag_value( $assoc_args, 'skip-site-setup', false );

		// Persist the manifest even if something fails half-way, so reset works.
		register_shutdown_function( array( $this, 'save_manifest' ) );

		wp_defer_term_counting( true );
		sbek_ensure_language_terms();

		if ( $site_setup ) {
			$this->setup_site();
		}

		$terms = $this->create_terms();
		$this->create_images();

		$km_posts = $this->create_khmer_posts( $terms, $per_category );
		$this->create_english_posts( $terms, $km_posts );
		$this->create_pages();

		if ( $site_setup ) {
			$this->create_menus( $terms );
			$this->create_widgets();
			$this->save_site_settings();
		}

		wp_defer_term_counting( false );
		sbek_bump_cache_version();
		$this->save_manifest();

		WP_CLI::success(
			sprintf(
				'Imported %d posts/pages, %d images, %d terms. Visit %s',
				count( $this->manifest['posts'] ),
				count( $this->manifest['attachments'] ),
				count( $this->manifest['terms'] ),
				home_url( '/' )
			)
		);
	}

	/**
	 * Remove all demo content created by `import`.
	 *
	 * ## OPTIONS
	 *
	 * [--yes]
	 * : Skip the confirmation prompt.
	 *
	 * @subcommand reset
	 *
	 * @param string[]             $args       Positional args.
	 * @param array<string,string> $assoc_args Flags.
	 */
	public function reset( array $args, array $assoc_args ): void {
		$manifest = get_option( self::MANIFEST );

		if ( ! is_array( $manifest ) ) {
			WP_CLI::error( 'No demo manifest found — nothing to reset.' );
		}

		WP_CLI::confirm( 'Delete all Sbek demo posts, pages, images, terms, menus and widgets?', $assoc_args );

		foreach ( (array) ( $manifest['posts'] ?? array() ) as $id ) {
			wp_delete_post( (int) $id, true );
		}
		foreach ( (array) ( $manifest['attachments'] ?? array() ) as $id ) {
			wp_delete_attachment( (int) $id, true );
		}
		foreach ( (array) ( $manifest['terms'] ?? array() ) as $entry ) {
			list( $taxonomy, $term_id ) = explode( ':', (string) $entry );
			wp_delete_term( (int) $term_id, $taxonomy );
		}
		foreach ( (array) ( $manifest['menus'] ?? array() ) as $menu_id ) {
			wp_delete_nav_menu( (int) $menu_id );
		}

		// Widgets: drop our block instances from their areas.
		$instances = (array) get_option( 'widget_block', array() );
		$sidebars  = (array) get_option( 'sidebars_widgets', array() );
		foreach ( (array) ( $manifest['widgets'] ?? array() ) as $number ) {
			unset( $instances[ (int) $number ] );
			foreach ( $sidebars as $sidebar => $widgets ) {
				if ( is_array( $widgets ) ) {
					$sidebars[ $sidebar ] = array_values( array_diff( $widgets, array( 'block-' . (int) $number ) ) );
				}
			}
		}
		update_option( 'widget_block', $instances );
		update_option( 'sidebars_widgets', $sidebars );

		if ( ! empty( $manifest['settings'] ) ) {
			delete_option( 'sbek_site_settings' );
		}

		delete_option( self::MANIFEST );
		sbek_bump_cache_version();

		WP_CLI::success( 'Demo content removed. (Site options such as language and permalinks were left as they are.)' );
	}

	/**
	 * Shutdown handler / final save.
	 */
	public function save_manifest(): void {
		if ( array_filter( $this->manifest ) ) {
			update_option( self::MANIFEST, $this->manifest, false );
		}
	}

	// ─── Site ─────────────────────────────────────────────────────────────

	private function setup_site(): void {
		global $wp_rewrite;

		$wp_rewrite->set_permalink_structure( '/article/%post_id%/' );
		$wp_rewrite->set_category_base( 'topics' );
		$wp_rewrite->set_tag_base( 'article/tag' );
		// category/post_tag registered their permastructs with the old bases
		// during init — re-register them, or the flush keeps stale rules.
		create_initial_taxonomies();
		flush_rewrite_rules( false );

		update_option( 'timezone_string', 'Asia/Phnom_Penh' );
		update_option( 'posts_per_page', 8 );
		update_option( 'blogname', 'Sbek News' );
		update_option( 'blogdescription', 'ព័ត៌មានថ្មីៗ រហ័ស និងទុកចិត្តបាន' );

		// Khmer is the primary language: core pack for dates and admin.
		if ( ! in_array( 'km', get_available_languages(), true ) ) {
			require_once ABSPATH . 'wp-admin/includes/translation-install.php';
			$installed = wp_download_language_pack( 'km' );
			if ( ! $installed ) {
				WP_CLI::warning( 'Could not download the Khmer core language pack; theme strings are still translated.' );
			}
		}
		update_option( 'WPLANG', 'km' );

		WP_CLI::log( 'Site options set (permalinks /article/%post_id%/, Khmer locale, Asia/Phnom_Penh).' );
	}

	// ─── Terms ────────────────────────────────────────────────────────────

	/**
	 * @return array<string,int> slug => term ID
	 */
	private function create_terms(): array {
		$ids   = array();
		$order = 1;

		foreach ( $this->categories() as $slug => $category ) {
			$ids[ $slug ] = $this->ensure_term( 'category', $slug, $category[0] );
			update_term_meta( $ids[ $slug ], 'sbek_color', $category[2] );
			update_term_meta( $ids[ $slug ], 'sbek_name_en', $category[1] );
			update_term_meta( $ids[ $slug ], 'sbek_home_order', $order++ );
		}

		$ids['autotalk'] = $this->ensure_term( 'post_tag', 'autotalk', 'AutoTalk' );
		update_term_meta( $ids['autotalk'], 'sbek_color', '#fa1939' );

		$ids['tag-news']      = $this->ensure_term( 'post_tag', 'news', 'ព័ត៌មាន' );
		$ids['tag-highlight'] = $this->ensure_term( 'post_tag', 'highlight', 'អត្ថបទសំខាន់' );
		update_term_meta( $ids['tag-news'], 'sbek_name_en', 'News' );
		update_term_meta( $ids['tag-highlight'], 'sbek_name_en', 'Highlights' );

		WP_CLI::log( sprintf( 'Terms ready: %s', implode( ', ', array_keys( $ids ) ) ) );

		return $ids;
	}

	/**
	 * Reuse a term with this slug, or create it (and record it for reset).
	 */
	private function ensure_term( string $taxonomy, string $slug, string $name ): int {
		$existing = get_term_by( 'slug', $slug, $taxonomy );
		if ( $existing instanceof WP_Term ) {
			return $existing->term_id;
		}

		$created = wp_insert_term( $name, $taxonomy, array( 'slug' => $slug ) );
		if ( is_wp_error( $created ) ) {
			WP_CLI::error( $created );
		}

		$this->manifest['terms'][] = $taxonomy . ':' . $created['term_id'];

		return (int) $created['term_id'];
	}

	// ─── Images ───────────────────────────────────────────────────────────

	private function create_images(): void {
		$palettes = array( 'video' => array( '#f2272f', '#1a1a1a' ), 'auto' => array( '#fa1939', '#2b2d42' ) );
		foreach ( $this->categories() as $slug => $category ) {
			$palettes[ $slug ] = $category[3];
		}

		$progress = WP_CLI\Utils\make_progress_bar( 'Generating images', count( $palettes ) * 6 );
		$seed     = 7;

		foreach ( $palettes as $name => $colors ) {
			for ( $i = 1; $i <= 6; $i++ ) {
				$file = $this->render_image( $seed++ * 97, $colors );
				$id   = media_handle_sideload(
					array(
						'name'     => "sbek-demo-{$name}-{$i}.jpg",
						'tmp_name' => $file,
					),
					0,
					'Sbek demo image'
				);

				if ( is_wp_error( $id ) ) {
					wp_delete_file( $file );
					WP_CLI::warning( $id->get_error_message() );
					continue;
				}

				update_post_meta( $id, '_wp_attachment_image_alt', 'រូបភាពគំរូ' );
				$this->manifest['attachments'][] = $id;
				$this->images[ $name ][]         = $id;
				$progress->tick();
			}
		}

		$progress->finish();
	}

	/**
	 * Draw a 1280×720 placeholder (diagonal gradient, soft circles, picture
	 * glyph) and return the temp file path.
	 *
	 * @param int                     $seed   Deterministic seed.
	 * @param array{0:string,1:string} $colors Gradient endpoints.
	 */
	private function render_image( int $seed, array $colors ): string {
		$rand = static function ( float $n ) use ( &$seed ): float {
			$seed = ( $seed * 9301 + 49297 ) % 233280;
			return ( $seed / 233280 ) * $n;
		};

		$w   = 640;
		$h   = 360;
		$img = imagecreatetruecolor( $w, $h );
		$a   = sscanf( $colors[0], '#%02x%02x%02x' );
		$b   = sscanf( $colors[1], '#%02x%02x%02x' );

		$steps = $w + $h;
		for ( $d = 0; $d <= $steps; $d++ ) {
			$t     = $d / $steps;
			$color = imagecolorallocate(
				$img,
				(int) round( $a[0] + ( $b[0] - $a[0] ) * $t ),
				(int) round( $a[1] + ( $b[1] - $a[1] ) * $t ),
				(int) round( $a[2] + ( $b[2] - $a[2] ) * $t )
			);
			imageline( $img, $d, 0, $d - $h, $h, $color );
		}

		imagealphablending( $img, true );
		for ( $i = 0; $i < 6; $i++ ) {
			$size = (int) ( 60 + $rand( 280 ) );
			imagefilledellipse( $img, (int) $rand( $w ), (int) $rand( $h ), $size, $size, imagecolorallocatealpha( $img, 255, 255, 255, (int) ( 105 + $rand( 15 ) ) ) );
		}

		$glyph = imagecolorallocatealpha( $img, 255, 255, 255, 80 );
		imagesetthickness( $img, 6 );
		imagerectangle( $img, 270, 150, 370, 210, $glyph );
		imagefilledellipse( $img, 295, 170, 16, 16, $glyph );
		imagefilledpolygon( $img, array( 276, 204, 306, 178, 324, 192, 342, 172, 364, 204 ), $glyph );

		$out = imagecreatetruecolor( 1280, 720 );
		imagecopyresampled( $out, $img, 0, 0, 0, 0, 1280, 720, $w, $h );

		$file = wp_tempnam( 'sbek-demo' ) . '.jpg';
		imagejpeg( $out, $file, 82 );
		imagedestroy( $img );
		imagedestroy( $out );

		return $file;
	}

	private function image_for( string $palette, int $i ): int {
		$set = $this->images[ $palette ] ?? reset( $this->images ) ?: array( 0 );

		return (int) $set[ $i % count( $set ) ];
	}

	// ─── Text ─────────────────────────────────────────────────────────────

	private static function khmer_digits( int $n ): string {
		return strtr( (string) $n, array_combine( range( 0, 9 ), array( '០', '១', '២', '៣', '៤', '៥', '៦', '៧', '៨', '៩' ) ) );
	}

	/**
	 * Original placeholder Khmer headline of varying length.
	 */
	private static function khmer_title( string $topic, int $i ): string {
		$leads = array( 'ចំណងជើងព័ត៌មានគំរូ', 'អត្ថបទគំរូសម្រាប់ការបង្ហាញ', 'ព័ត៌មានថ្មីៗប្រចាំថ្ងៃ' );
		$tails = array(
			'សម្រាប់បង្ហាញប្លង់ទំព័រដើម និងរចនាបថរបស់គេហទំព័រ',
			'ដែលប្រើជាទិន្នន័យសាកល្បងសម្រាប់ការអភិវឌ្ឍន៍',
			'ខណៈក្រុមការងារកំពុងរៀបចំមាតិកាថ្មីសម្រាប់អ្នកអាន',
			'និងព័ត៌មានលម្អិតបន្ថែមនឹងបង្ហាញនៅពេលក្រោយ',
			'Demo Headline ដែលមានពាក្យអង់គ្លេសលាយឡំ',
		);

		return sprintf( '%s %s ទី%s %s', $topic, $leads[ $i % 3 ], self::khmer_digits( $i + 1 ), $tails[ $i % 5 ] );
	}

	/**
	 * Khmer body as block markup. Words are separated with U+200B (the
	 * "Show word breaks" switch reveals them).
	 */
	private static function khmer_body(): string {
		$words = static fn( string $s ): string => esc_html( str_replace( ' ', self::ZWSP, $s ) );

		$paragraphs = array(
			'នេះ ជា កថាខណ្ឌ គំរូ ទី មួយ ដែល ប្រើ សម្រាប់ បង្ហាញ ប្លង់ ទំព័រ អត្ថបទ លម្អិត។ មាតិកា ពិត ប្រាកដ នឹង ត្រូវ ជំនួស ដោយ អត្ថបទ ពី ប្រព័ន្ធ គ្រប់គ្រង មាតិកា នៅ ពេល ក្រោយ។',
			'កថាខណ្ឌ ទី ពីរ បង្ហាញ ពី របៀប ដែល អត្ថបទ វែង ៗ ត្រូវ បាន បំបែក ជា បន្ទាត់ ច្រើន ដោយ មាន គម្លាត បន្ទាត់ ២៤ ភីកសែល និង ពណ៌ អក្សរ ខ្មៅ ស្រាល ដើម្បី ងាយ ស្រួល អាន។ Demo text ជា ភាសា អង់គ្លេស ក៏ អាច លាយ ឡំ បាន ដែរ។',
			'ក្រុម ការងារ អាច ប្រើ ទំព័រ នេះ ដើម្បី សាកល្បង រូបភាព ចំណង ជើង ព័ត៌មាន ពាក្យ គន្លឹះ និង អត្ថបទ ទាក់ទង ដោយ មិន ចាំបាច់ ភ្ជាប់ ទៅ ម៉ាស៊ីន មេ ពិត ប្រាកដ ឡើយ។',
			'កថាខណ្ឌ ចុង ក្រោយ នេះ បញ្ចប់ អត្ថបទ គំរូ។ សូម អរគុណ ដែល បាន អាន ដល់ ទី បញ្ចប់។',
		);

		$p = static fn( string $text ): string => "<!-- wp:paragraph -->\n<p>{$text}</p>\n<!-- /wp:paragraph -->";

		return implode(
			"\n\n",
			array(
				$p( $words( $paragraphs[0] ) ),
				$p( $words( $paragraphs[1] ) ),
				"<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">" . $words( 'ចំណុច សំខាន់ ៗ' ) . "</h2>\n<!-- /wp:heading -->",
				"<!-- wp:list -->\n<ul class=\"wp-block-list\"><!-- wp:list-item -->\n<li>" . $words( 'ចំណុច ទី មួយ សម្រាប់ សាកល្បង បញ្ជី' ) . "</li>\n<!-- /wp:list-item -->\n\n<!-- wp:list-item -->\n<li>" . $words( 'ចំណុច ទី ពីរ ដែល មាន អត្ថបទ វែង ជាង' ) . "</li>\n<!-- /wp:list-item --></ul>\n<!-- /wp:list -->",
				$p( $words( $paragraphs[2] ) ),
				"<!-- wp:quote -->\n<blockquote class=\"wp-block-quote\"><!-- wp:paragraph -->\n<p>" . $words( 'នេះ ជា សម្រង់ សម្ដី គំរូ សម្រាប់ បង្ហាញ រចនាបថ។' ) . "</p>\n<!-- /wp:paragraph --></blockquote>\n<!-- /wp:quote -->",
				$p( $words( $paragraphs[3] ) ),
			)
		);
	}

	private static function english_body(): string {
		$p = static fn( string $text ): string => "<!-- wp:paragraph -->\n<p>" . esc_html( $text ) . "</p>\n<!-- /wp:paragraph -->";

		return implode(
			"\n\n",
			array(
				$p( 'This is the first placeholder paragraph of a demo article. Real reporting will replace it once the newsroom starts publishing in English.' ),
				$p( 'The second paragraph shows how longer copy wraps across several lines with comfortable line height and a soft text color, so the article page can be reviewed without real content.' ),
				"<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">Key points</h2>\n<!-- /wp:heading -->",
				"<!-- wp:list -->\n<ul class=\"wp-block-list\"><!-- wp:list-item -->\n<li>A first point to test list styling</li>\n<!-- /wp:list-item -->\n\n<!-- wp:list-item -->\n<li>A second, slightly longer point</li>\n<!-- /wp:list-item --></ul>\n<!-- /wp:list -->",
				$p( 'Editors can use this page to check images, headlines, tags and related stories before the site goes live.' ),
				$p( 'This closing paragraph ends the demo article. Thanks for reading to the end.' ),
			)
		);
	}

	// ─── Posts ────────────────────────────────────────────────────────────

	/**
	 * @param array<string,mixed> $data  Post fields + `sbek` extras.
	 */
	private function insert_post( array $data ): int {
		$extras = $data['sbek'] ?? array();
		unset( $data['sbek'] );

		$id = wp_insert_post(
			array_merge(
				array(
					'post_type'   => 'post',
					'post_status'    => 'publish',
					'post_author'    => $this->author,
					'comment_status' => 'closed',
					'ping_status'    => 'closed',
					'meta_input'  => array( '_sbek_demo' => 1 ),
				),
				$data
			),
			true
		);

		if ( is_wp_error( $id ) ) {
			WP_CLI::error( $id );
		}

		$this->manifest['posts'][] = $id;

		wp_set_object_terms( $id, array( $extras['lang'] ?? SBEK_DEFAULT_LANGUAGE ), 'language' );

		if ( ! empty( $extras['categories'] ) ) {
			wp_set_post_categories( $id, $extras['categories'] );
		}
		if ( ! empty( $extras['tags'] ) ) {
			wp_set_object_terms( $id, $extras['tags'], 'post_tag' );
		}
		if ( ! empty( $extras['image'] ) ) {
			set_post_thumbnail( $id, $extras['image'] );
		}
		if ( ! empty( $extras['format'] ) ) {
			set_post_format( $id, $extras['format'] );
		}
		if ( ! empty( $extras['sticky'] ) ) {
			stick_post( $id );
		}

		return $id;
	}

	/**
	 * Khmer stories, newest first across all sections.
	 *
	 * @param array<string,int> $terms Term IDs.
	 * @return array{all:int[],by_category:array<string,int[]>}
	 */
	private function create_khmer_posts( array $terms, int $per_category ): array {
		$topics = array(
			'entertainment' => 'តារាសម្ដែង',
			'technology'    => 'បច្ចេកវិទ្យាថ្មី',
			'life'          => 'សុខភាព',
			'sport'         => 'បាល់ទាត់',
		);

		// Interleave sections so every block has recent stories.
		$queue = array();
		for ( $i = 0; $i < $per_category; $i++ ) {
			foreach ( array_keys( $topics ) as $slug ) {
				$queue[] = array( 'section', $slug, $i );
			}
			if ( $i < 14 ) {
				$queue[] = array( 'autotalk', 'technology', $i );
			}
			if ( $i < 11 ) {
				$queue[] = array( 'video', 'entertainment', $i );
			}
		}

		$result   = array(
			'all'         => array(),
			'by_category' => array(),
		);
		$progress = WP_CLI\Utils\make_progress_bar( 'Creating Khmer posts', count( $queue ) );
		$now      = time();

		foreach ( $queue as $n => list( $kind, $slug, $i ) ) {
			$tags = array( 0 === $n % 3 ? $terms['tag-highlight'] : $terms['tag-news'] );

			switch ( $kind ) {
				case 'autotalk':
					$title   = self::khmer_title( 'រថយន្តម៉ូដែលថ្មី', $i + 3 );
					$palette = 'auto';
					$tags[]  = $terms['autotalk'];
					break;
				case 'video':
					$title   = self::khmer_title( 'វីដេអូ', $i + 4 );
					$palette = 'video';
					break;
				default:
					$title   = self::khmer_title( $topics[ $slug ], $i );
					$palette = $slug;
			}

			$timestamp = $now - $n * 47 * MINUTE_IN_SECONDS - ( $n % 7 ) * 3 * MINUTE_IN_SECONDS;

			$id = $this->insert_post(
				array(
					'post_title'    => $title,
					'post_content'  => self::khmer_body(),
					'post_excerpt'  => 'នេះជាអត្ថបទសង្ខេបគំរូ សម្រាប់បង្ហាញក្នុងប្លង់ទំព័រ និងការសាកល្បងមុខងារផ្សេងៗរបស់គេហទំព័រប៉ុណ្ណោះ។',
					'post_date'     => wp_date( 'Y-m-d H:i:s', $timestamp ),
					'post_date_gmt' => gmdate( 'Y-m-d H:i:s', $timestamp ),
					'meta_input'    => array_filter(
						array(
							'_sbek_demo'           => 1,
							'_sbek_source_name'    => 'Demo Source',
							'_sbek_source_url'     => 'https://example.com/',
							'_sbek_trending_score' => 0 === $n % 4 ? 40 + ( $n * 7 ) % 60 : null,
							'_sbek_is_breaking'    => $n < 4 ? 1 : null,
						)
					),
					'sbek'          => array(
						'lang'       => 'km',
						'categories' => array( $terms[ $slug ] ),
						'tags'       => $tags,
						'image'      => $this->image_for( $palette, $n ),
						'format'     => 'video' === $kind ? 'video' : '',
						'sticky'     => in_array( $n, array( 1, 3, 6, 8 ), true ),
					),
				)
			);

			$result['all'][]                  = $id;
			$result['by_category'][ $slug ][] = $id;
			$progress->tick();
		}

		$progress->finish();

		return $result;
	}

	/**
	 * English stories (a few per section), each linked as the translation of a
	 * recent Khmer story so the language switcher and hreflang have pairs.
	 *
	 * @param array<string,int>                                     $terms    Term IDs.
	 * @param array{all:int[],by_category:array<string,int[]>} $km_posts Khmer posts.
	 */
	private function create_english_posts( array $terms, array $km_posts ): void {
		$n   = 0;
		$now = time();

		foreach ( $this->categories() as $slug => $category ) {
			for ( $i = 0; $i < 5; $i++, $n++ ) {
				$timestamp = $now - $n * 95 * MINUTE_IN_SECONDS;
				$id        = $this->insert_post(
					array(
						'post_title'    => sprintf( '%s demo story %d: placeholder headline for layout testing', $category[1], $i + 1 ),
						'post_content'  => self::english_body(),
						'post_excerpt'  => 'A short placeholder summary used to preview the English edition of the layout.',
						'post_date'     => wp_date( 'Y-m-d H:i:s', $timestamp ),
						'post_date_gmt' => gmdate( 'Y-m-d H:i:s', $timestamp ),
						'meta_input'    => array(
							'_sbek_demo'        => 1,
							'_sbek_source_name' => 'Demo Source',
							'_sbek_source_url'  => 'https://example.com/',
						),
						'sbek'          => array(
							'lang'       => 'en',
							'categories' => array( $terms[ $slug ] ),
							'tags'       => array( $terms['tag-news'] ),
							'image'      => $this->image_for( $slug, $i + 2 ),
							'format'     => $i < 1 ? 'video' : '',
						),
					)
				);

				$km_id = $km_posts['by_category'][ $slug ][ $i ] ?? 0;
				if ( $km_id ) {
					sbek_set_translation( $id, $km_id );
				}
			}
		}

		WP_CLI::log( sprintf( 'Created %d English posts linked to Khmer translations.', $n ) );
	}

	private function create_pages(): void {
		$pages = array(
			'privacy' => array(
				'km' => array( 'គោលការណ៍ភាពឯកជន', 'privacy-policy' ),
				'en' => array( 'Privacy Policy', 'privacy-policy-en' ),
			),
			'about'   => array(
				'km' => array( 'អំពីយើង', 'about' ),
				'en' => array( 'About us', 'about-en' ),
			),
		);

		foreach ( $pages as $key => $languages ) {
			$ids = array();
			foreach ( $languages as $lang => list( $title, $slug ) ) {
				$body = 'km' === $lang
					? '<!-- wp:paragraph --><p>' . esc_html( str_replace( ' ', self::ZWSP, 'នេះ ជា ទំព័រ គំរូ។ សូម ជំនួស មាតិកា នេះ ដោយ ព័ត៌មាន ពិត របស់ អ្នក។' ) ) . '</p><!-- /wp:paragraph -->'
					: '<!-- wp:paragraph --><p>This is a placeholder page. Replace this text with your own content.</p><!-- /wp:paragraph -->';

				$ids[ $lang ] = $this->insert_post(
					array(
						'post_type'      => 'page',
						'post_title'     => $title,
						'post_name'      => $slug,
						'post_content'   => $body,
						'comment_status' => 'closed',
						'sbek'           => array( 'lang' => $lang ),
					)
				);
			}

			sbek_set_translation( $ids['en'], $ids['km'] );

			if ( 'privacy' === $key ) {
				update_option( 'wp_page_for_privacy_policy', $ids['km'] );
			}
		}
	}


	// ─── Menus, widgets, settings ─────────────────────────────────────────

	/**
	 * @param array<string,int> $terms Term IDs.
	 */
	private function create_menus( array $terms ): void {
		$locations = get_theme_mod( 'nav_menu_locations', array() );

		foreach ( array( 'primary' => 'Sbek primary (ខ្មែរ)', 'primary-en' => 'Sbek primary (English)' ) as $location => $name ) {
			$existing = wp_get_nav_menu_object( $name );
			$menu_id  = $existing ? (int) $existing->term_id : wp_create_nav_menu( $name );
			if ( is_wp_error( $menu_id ) ) {
				WP_CLI::warning( $menu_id->get_error_message() );
				continue;
			}
			if ( ! $existing ) {
				$this->manifest['menus'][] = $menu_id;
			}

			wp_update_nav_menu_item(
				$menu_id,
				0,
				array(
					'menu-item-title'  => 'primary' === $location ? 'ទំព័រដើម' : 'Home',
					'menu-item-url'    => home_url( '/' ),
					'menu-item-type'   => 'custom',
					'menu-item-status' => 'publish',
				)
			);

			foreach ( array_keys( $this->categories() ) as $slug ) {
				wp_update_nav_menu_item(
					$menu_id,
					0,
					array(
						'menu-item-object-id' => $terms[ $slug ],
						'menu-item-object'    => 'category',
						'menu-item-type'      => 'taxonomy',
						'menu-item-status'    => 'publish',
					)
				);
			}

			wp_update_nav_menu_item(
				$menu_id,
				0,
				array(
					'menu-item-title'     => 'AUTO TALK',
					'menu-item-object-id' => $terms['autotalk'],
					'menu-item-object'    => 'post_tag',
					'menu-item-type'      => 'taxonomy',
					'menu-item-status'    => 'publish',
				)
			);

			$locations[ $location ] = $menu_id;
		}

		set_theme_mod( 'nav_menu_locations', $locations );
	}

	/**
	 * Placeholder ad boxes in the ad widget areas (Custom HTML blocks).
	 */
	private function create_widgets(): void {
		$instances = (array) get_option( 'widget_block', array() );
		$sidebars  = (array) get_option( 'sidebars_widgets', array() );
		$next      = max( array_merge( array( 1 ), array_filter( array_keys( $instances ), 'is_int' ) ) ) + 1;

		$ads = array(
			'sbek-ad-header' => array( 'sbek-ad-placeholder sbek-ad-placeholder--leaderboard', 'Advertisement · 728×90' ),
			'sbek-ad-hero'   => array( 'sbek-ad-placeholder', 'Advertisement · 300×250' ),
			'sbek-ad-video'  => array( 'sbek-ad-placeholder', 'Advertisement · 300×250' ),
		);

		foreach ( $ads as $sidebar => list( $class, $label ) ) {
			$count = 'sbek-ad-hero' === $sidebar ? 2 : 1;
			for ( $i = 0; $i < $count; $i++ ) {
				$instances[ $next ] = array(
					'content' => "<!-- wp:html -->\n<div class=\"{$class}\" role=\"img\" aria-label=\"{$label}\">{$label}</div>\n<!-- /wp:html -->",
				);
				$sidebars[ $sidebar ]        = array_merge( (array) ( $sidebars[ $sidebar ] ?? array() ), array( 'block-' . $next ) );
				$this->manifest['widgets'][] = $next;
				++$next;
			}
		}

		$instances['_multiwidget'] = 1;
		update_option( 'widget_block', $instances );
		update_option( 'sidebars_widgets', $sidebars );
	}

	private function save_site_settings(): void {
		if ( get_option( 'sbek_site_settings' ) ) {
			WP_CLI::log( 'Theme Option already configured — left unchanged.' );
			return;
		}

		$logo_file = wp_tempnam( 'sbek-logo' ) . '.png';
		copy( SBEK_DIR . '/assets/images/demo-logo.png', $logo_file );
		$logo_id = media_handle_sideload(
			array(
				'name'     => 'sbek-demo-logo.png',
				'tmp_name' => $logo_file,
			),
			0,
			'Sbek demo logo'
		);
		if ( is_wp_error( $logo_id ) ) {
			$logo_id = 0;
		} else {
			$this->manifest['attachments'][] = $logo_id;
		}

		update_option(
			'sbek_site_settings',
			array(
				'name_en'        => 'Sbek News',
				'tagline_en'     => 'Fast, trusted news',
				'logo'           => $logo_id,
				'og_image'       => $this->image_for( 'technology', 0 ),
				'lat'            => '11.5564',
				'lng'            => '104.9282',
				'description'    => 'Sbek News ជាគេហទំព័រគំរូសម្រាប់បង្ហាញប្លង់ និងរចនាបថ។ មិនមែនជាសារព័ត៌មានពិតទេ។',
				'description_en' => 'Sbek News is a demo site for previewing the layout and design. It is not a real news outlet.',
				'address'        => "អាសយដ្ឋានគំរូ\nភ្នំពេញ កម្ពុជា",
				'address_en'     => "Sample address\nPhnom Penh, Cambodia",
				'email'          => 'newsroom@example.com',
				'phones'         => array(
					array(
						'label'  => '',
						'number' => '+855 00 000 000',
					),
				),
				'socials'        => array(
					array( 'platform' => 'Facebook', 'url' => 'https://www.facebook.com/' ),
					array( 'platform' => 'Instagram', 'url' => 'https://www.instagram.com/' ),
					array( 'platform' => 'YouTube', 'url' => 'https://www.youtube.com/' ),
					array( 'platform' => 'TikTok', 'url' => 'https://www.tiktok.com/' ),
					array( 'platform' => 'Telegram', 'url' => 'https://telegram.org/' ),
				),
				'ga4_id'         => '',
				'gtm_id'         => '',
				'header_scripts' => '',
				'footer_scripts' => '',
			)
		);

		if ( $logo_id && ! get_option( 'site_icon' ) ) {
			update_option( 'site_icon', $logo_id );
		}

		$this->manifest['settings'] = array( 1 );
	}
}

WP_CLI::add_command( 'sbek demo', 'Sbek_Demo_Command' );
