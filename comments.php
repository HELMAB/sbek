<?php
/**
 * Comments under articles and pages: the threaded list, its pagination and
 * the reply form (styles: `.comments` in assets/css/src/input.css).
 *
 * @package sbek
 */

defined( 'ABSPATH' ) || exit;

if ( post_password_required() ) {
	return;
}

$sbek_comment_count = (int) get_comments_number();
?>
<section id="comments" class="comments article__section">
	<?php if ( have_comments() ) : ?>
		<h2 class="rule-title comments__title">
			<?php
			echo esc_html(
				sprintf(
					/* translators: %s: number of comments. */
					_n( '%s comment', '%s comments', $sbek_comment_count, 'sbek' ),
					number_format_i18n( $sbek_comment_count )
				)
			);
			?>
		</h2>

		<ol class="comment-list">
			<?php
			wp_list_comments(
				array(
					'style'       => 'ol',
					'short_ping'  => true,
					'avatar_size' => 40,
				)
			);
			?>
		</ol>

		<?php
		the_comments_pagination(
			array(
				'prev_text' => esc_html__( 'Older comments', 'sbek' ),
				'next_text' => esc_html__( 'Newer comments', 'sbek' ),
			)
		);
		?>

		<?php if ( ! comments_open() ) : ?>
			<p class="comments__closed"><?php esc_html_e( 'Comments are closed.', 'sbek' ); ?></p>
		<?php endif; ?>
	<?php endif; ?>

	<?php
	comment_form(
		array(
			'class_container'    => 'comment-respond comments__form',
			'title_reply_before' => '<h2 id="reply-title" class="rule-title comment-reply-title">',
			'title_reply_after'  => '</h2>',
			'class_submit'       => 'btn',
		)
	);
	?>
</section>
