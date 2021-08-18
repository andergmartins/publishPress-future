<div class="post-expire-col" data-id="<?php echo esc_attr( $id ); ?>" data-expire-attributes="<?php echo esc_attr( json_encode( $attributes ) ); ?>">
<?php
	$display = __( 'Never', 'post-expirator' );
	$ed = get_post_meta( $id, '_expiration-date', true );
if ( $ed ) {
	$display = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $ed + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) );
}

	$defaults = get_option( 'expirationdateDefaults' . ucfirst( $post_type ) );
	$expireType = 'draft';
if ( isset( $defaults['expireType'] ) ) {
	$expireType = $defaults['expireType'];
}


	$year = date( 'Y' );
	$month = date( 'm' );
	$day = date( 'd' );
	$hour = date( 'H' );
	$minute = date( 'i' );
	$enabled = 'false';

	// Values for Quick Edit
if ( $ed ) {
	$enabled = 'true';
	$date = gmdate( 'Y-m-d H:i:s', $ed );
	$year = get_date_from_gmt( $date, 'Y' );
	$month = get_date_from_gmt( $date, 'm' );
	$day = get_date_from_gmt( $date, 'd' );
	$hour = get_date_from_gmt( $date, 'H' );
	$minute = get_date_from_gmt( $date, 'i' );
	if ( isset( $attributes['expireType'] ) ) {
		$expireType = $attributes['expireType'];
	}
}
?>
	<?php echo esc_html( $display ); ?>
	<span id="expirationdate_year-<?php echo $id; ?>" style="display: none;"><?php echo $year; ?></span>
	<span id="expirationdate_month-<?php echo $id; ?>" style="display: none;"><?php echo $month; ?></span>
	<span id="expirationdate_day-<?php echo $id; ?>" style="display: none;"><?php echo $day; ?></span>
	<span id="expirationdate_hour-<?php echo $id; ?>" style="display: none;"><?php echo $hour; ?></span>
	<span id="expirationdate_minute-<?php echo $id; ?>" style="display: none;"><?php echo $minute; ?></span>
	<span id="expirationdate_enabled-<?php echo $id; ?>" style="display: none;"><?php echo $enabled; ?></span>
	<span id="expirationdate_expireType-<?php echo $id; ?>" style="display: none;"><?php echo $expireType; ?></span>
</div>
