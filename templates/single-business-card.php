<?php
/**
 * Full-page template for a single business card. A standalone document (see DECISIONS.md D21).
 *
 * Override by returning another path from the `dbcp_template_path` filter (name 'single').
 * Available: $dbcp_card (array from DBCP_Template::get_card_data()), unescaped. Escape at output.
 *
 * @package DigitalBusinessCardPosts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$dbcp_card = DBCP_Template::get_card_data( get_queried_object_id() );
wp_enqueue_style( DBCP_Template::STYLE_HANDLE );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo esc_html( $dbcp_card['document_title'] ); ?></title>
<meta name="description" content="<?php echo esc_attr( $dbcp_card['description'] ); ?>">
<?php if ( ! empty( $dbcp_card['noindex'] ) ) : ?>
<meta name="robots" content="noindex, nofollow">
<?php endif; ?>
<meta name="theme-color" content="<?php echo esc_attr( $dbcp_card['accent'] ); ?>">
<link rel="canonical" href="<?php echo esc_url( $dbcp_card['permalink'] ); ?>">
<?php
wp_site_icon();
wp_print_styles( DBCP_Template::STYLE_HANDLE );

/**
 * Fires inside <head> on the card page. Use it to add fonts, analytics or extra styles.
 *
 * @param array<string, mixed> $dbcp_card Card data.
 */
do_action( 'dbcp_head', $dbcp_card );
?>
</head>
<body class="dbcp-card-page">
<main class="dbcp-page">
	<?php
	// Markup from templates/card.php, escaped there.
	echo DBCP_Template::render_card( (int) $dbcp_card['id'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	?>
</main>
<?php
/**
 * Fires before </body> on the card page.
 *
 * @param array<string, mixed> $dbcp_card Card data.
 */
do_action( 'dbcp_footer', $dbcp_card );
?>
</body>
</html>
