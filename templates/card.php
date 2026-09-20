<?php
/**
 * The card markup. Used by the full-page template and by the shortcode.
 *
 * Override by returning another path from the `dbcp_template_path` filter (name 'card').
 * Available: $dbcp_card (array from DBCP_Template::get_card_data()), unescaped. Escape at output.
 *
 * @package DigitalBusinessCardPosts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$dbcp_style = sprintf(
	'--dbcp-sun:%s;--dbcp-sun-press:%s;--dbcp-sun-ink:%s;',
	$dbcp_card['accent'],
	$dbcp_card['accent_press'],
	$dbcp_card['accent_ink']
);
?>
<article class="dbcp-card" id="dbcp-card-<?php echo esc_attr( (string) $dbcp_card['id'] ); ?>" style="<?php echo esc_attr( $dbcp_style ); ?>">
	<?php if ( ! empty( $dbcp_card['logo'] ) ) : ?>
		<img class="dbcp-logo" src="<?php echo esc_url( $dbcp_card['logo']['url'] ); ?>" alt="<?php echo esc_attr( $dbcp_card['logo']['alt'] ); ?>"<?php echo $dbcp_card['logo']['width'] ? ' width="' . esc_attr( (string) $dbcp_card['logo']['width'] ) . '" height="' . esc_attr( (string) $dbcp_card['logo']['height'] ) . '"' : ''; ?> decoding="async">
	<?php endif; ?>

	<h1 class="dbcp-name"><?php echo esc_html( $dbcp_card['name'] ); ?></h1>
	<?php if ( '' !== $dbcp_card['job_title'] ) : ?>
		<p class="dbcp-role"><?php echo esc_html( $dbcp_card['job_title'] ); ?></p>
	<?php endif; ?>
	<?php if ( '' !== $dbcp_card['company'] ) : ?>
		<p class="dbcp-org"><?php echo esc_html( $dbcp_card['company'] ); ?></p>
	<?php endif; ?>
	<?php if ( '' !== $dbcp_card['tagline'] ) : ?>
		<p class="dbcp-tagline"><?php echo esc_html( $dbcp_card['tagline'] ); ?></p>
	<?php endif; ?>

	<?php if ( '' !== $dbcp_card['vcard_url'] ) : ?>
		<a class="dbcp-save" href="<?php echo esc_url( $dbcp_card['vcard_url'] ); ?>">
			<?php echo DBCP_Template::icon( 'save' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?>
			<?php esc_html_e( 'Save contact', 'digital-business-card-posts' ); ?>
		</a>
	<?php endif; ?>

	<ul class="dbcp-contacts">
		<?php if ( '' !== $dbcp_card['phone_work_href'] ) : ?>
			<li>
				<a href="<?php echo esc_url( $dbcp_card['phone_work_href'] ); ?>">
					<?php echo DBCP_Template::icon( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?>
					<span><?php echo esc_html( $dbcp_card['phone_work'] ); ?><span class="dbcp-label"><?php esc_html_e( 'Work', 'digital-business-card-posts' ); ?></span></span>
				</a>
			</li>
		<?php endif; ?>
		<?php if ( '' !== $dbcp_card['phone_mobile_href'] ) : ?>
			<li>
				<a href="<?php echo esc_url( $dbcp_card['phone_mobile_href'] ); ?>">
					<?php echo DBCP_Template::icon( 'mobile' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?>
					<span><?php echo esc_html( $dbcp_card['phone_mobile'] ); ?><span class="dbcp-label"><?php esc_html_e( 'Mobile', 'digital-business-card-posts' ); ?></span></span>
				</a>
			</li>
		<?php endif; ?>
		<?php if ( '' !== $dbcp_card['email'] ) : ?>
			<li>
				<a href="<?php echo esc_url( 'mailto:' . $dbcp_card['email'] ); ?>">
					<?php echo DBCP_Template::icon( 'email' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?>
					<span><?php echo esc_html( $dbcp_card['email'] ); ?><span class="dbcp-label"><?php esc_html_e( 'Email', 'digital-business-card-posts' ); ?></span></span>
				</a>
			</li>
		<?php endif; ?>
		<?php if ( '' !== $dbcp_card['website'] ) : ?>
			<li>
				<a href="<?php echo esc_url( $dbcp_card['website'] ); ?>" target="_blank" rel="noopener">
					<?php echo DBCP_Template::icon( 'website' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?>
					<span><?php echo esc_html( $dbcp_card['website_display'] ); ?><span class="dbcp-label"><?php esc_html_e( 'Website', 'digital-business-card-posts' ); ?></span></span>
				</a>
			</li>
		<?php endif; ?>
		<?php if ( '' !== $dbcp_card['maps_url'] && ! empty( $dbcp_card['address_lines'] ) ) : ?>
			<li>
				<a href="<?php echo esc_url( $dbcp_card['maps_url'] ); ?>" target="_blank" rel="noopener">
					<?php echo DBCP_Template::icon( 'map' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?>
					<span>
						<?php
						$dbcp_line_count = count( $dbcp_card['address_lines'] );
						foreach ( $dbcp_card['address_lines'] as $dbcp_i => $dbcp_line ) {
							echo esc_html( $dbcp_line );
							if ( $dbcp_i < $dbcp_line_count - 1 ) {
								echo '<br>';
							}
						}
						?>
						<span class="dbcp-label"><?php esc_html_e( 'Directions', 'digital-business-card-posts' ); ?></span>
					</span>
				</a>
			</li>
		<?php endif; ?>
	</ul>
</article>
