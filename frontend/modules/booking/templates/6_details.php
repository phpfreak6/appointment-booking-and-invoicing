<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
    /** @var \Bookly\Lib\UserBookingData $userData */
    echo $progress_tracker;
?>

<div class="bookly-box"><?php echo $info_text ?></div>
<?php if ( $info_text_guest ) : ?>
    <div class="bookly-box bookly-guest-desc"><?php echo $info_text_guest ?></div>
<?php endif ?>

<div class="bookly-details-step">
    <div class="bookly-box bookly-table">
        <div class="bookly-form-group">
            <label><?php echo \Bookly\Lib\Utils\Common::getTranslatedOption( 'bookly_l10n_label_name' ) ?></label>
            <div>
                <input class="bookly-js-full-name" type="text" value="<?php echo esc_attr( $userData->get( 'name' ) ) ?>"/>
            </div>
            <div class="bookly-js-full-name-error bookly-label-error"></div>
        </div>
        <div class="bookly-form-group">
            <label><?php echo \Bookly\Lib\Utils\Common::getTranslatedOption( 'bookly_l10n_label_phone' ) ?></label>
            <div>
                <input class="bookly-js-user-phone-input<?php if ( get_option( 'bookly_cst_phone_default_country' ) != 'disabled' ) : ?> bookly-user-phone<?php endif ?>" value="<?php echo esc_attr( $userData->get( 'phone' ) ) ?>" type="text" />
            </div>
            <div class="bookly-js-user-phone-error bookly-label-error"></div>
        </div>
        <div class="bookly-form-group">
            <label><?php echo \Bookly\Lib\Utils\Common::getTranslatedOption( 'bookly_l10n_label_email' ) ?></label>
            <div>
                <input class="bookly-js-user-email" maxlength="255" type="text" value="<?php echo esc_attr( $userData->get( 'email' ) ) ?>"/>
            </div>
            <div class="bookly-js-user-email-error bookly-label-error"></div>
        </div>
    </div>
    <?php foreach ( $cf_data as $cart_key => $cf_item ) : ?>
        <div class="bookly-custom-fields-container" data-cart_key="<?php echo $cart_key ?>">
        <?php if ( $show_service_title && ! empty ( $cf_item['custom_fields'] ) ) : ?>
            <div class="bookly-box"><b><?php echo $cf_item['service_title'] ?></b></div>
        <?php endif ?>
        <?php foreach ( $cf_item['custom_fields'] as $custom_field ) : ?>
            <div class="bookly-box bookly-custom-field-row" data-id="<?php echo $custom_field->id ?>" data-type="<?php echo $custom_field->type ?>">
                <div class="bookly-form-group">
                    <?php if ( $custom_field->type != 'text-content' ) : ?>
                        <label><?php echo $custom_field->label ?></label>
                    <?php endif ?>
                    <div>
                        <?php if ( $custom_field->type == 'text-field' ) : ?>
                            <input type="text" class="bookly-custom-field" value="<?php echo esc_attr( @$cf_item['data'][ $custom_field->id ] ) ?>"/>
                        <?php elseif ( $custom_field->type == 'textarea' ) : ?>
                            <textarea rows="3" class="bookly-custom-field"><?php echo esc_html( @$cf_item['data'][ $custom_field->id ] ) ?></textarea>
                        <?php elseif ( $custom_field->type == 'text-content' ) : ?>
                            <?php echo nl2br( $custom_field->label ) ?>
                        <?php elseif ( $custom_field->type == 'checkboxes' ) : ?>
                            <?php foreach ( $custom_field->items as $item ) : ?>
                                <label>
                                    <input type="checkbox" class="bookly-custom-field" value="<?php echo esc_attr( $item['value'] ) ?>" <?php checked( @in_array( $item['value'], @$cf_item['data'][ $custom_field->id ] ), true, true ) ?> />
                                    <span><?php echo $item['label'] ?></span>
                                </label><br/>
                            <?php endforeach ?>
                        <?php elseif ( $custom_field->type == 'radio-buttons' ) : ?>
                            <?php foreach ( $custom_field->items as $item ) : ?>
                                <label>
                                    <input type="radio" class="bookly-custom-field" name="bookly-custom-field-<?php echo $custom_field->id ?>"
                                           value="<?php echo esc_attr( $item['value'] ) ?>" <?php checked( $item['value'], @$cf_item['data'][ $custom_field->id ], true ) ?> />
                                    <span><?php echo $item['label'] ?></span>
                                </label><br/>
                            <?php endforeach ?>
                        <?php elseif ( $custom_field->type == 'drop-down' ) : ?>
                            <select class="bookly-custom-field">
                                <option value=""></option>
                                <?php foreach ( $custom_field->items as $item ) : ?>
                                    <option value="<?php echo esc_attr( $item['value'] ) ?>" <?php selected( $item['value'], @$cf_item['data'][ $custom_field->id ], true ) ?>><?php echo esc_html( $item['label'] ) ?></option>
                                <?php endforeach ?>
                            </select>
                        <?php elseif ( $custom_field->type == 'captcha' ) : ?>
                            <img class="bookly-js-captcha-img" src="<?php echo esc_url( $captcha_url ) ?>" alt="<?php esc_attr_e( 'Captcha', 'bookly' ) ?>" height="75" width="160" style="width:160px;height:75px;"/>
                            <img class="bookly-js-captcha-refresh" width="16" height="16" title="<?php esc_attr_e( 'Another code', 'bookly' ) ?>" alt="<?php esc_attr_e( 'Another code', 'bookly' ) ?>"
                                 src="<?php echo plugins_url( 'frontend/resources/images/refresh.png', \Bookly\Lib\Plugin::getMainFile() ) ?>" style="cursor: pointer"/>
                            <input type="text" class="bookly-custom-field bookly-captcha" value="<?php echo esc_attr( @$cf_item['data'][ $custom_field->id ] ) ?>"/>
                        <?php endif ?>
                    </div>
                    <?php if ( $custom_field->type != 'text-content' ) : ?>
                        <div class="bookly-label-error bookly-custom-field-error"></div>
                    <?php endif ?>
                </div>
            </div>
        <?php endforeach ?>
        </div>
    <?php endforeach ?>
</div>

<?php $this->render( '_info_block', compact( 'info_message' ) ) ?>

<div class="bookly-box bookly-nav-steps">
    <button class="bookly-back-step bookly-js-back-step bookly-btn ladda-button" data-style="zoom-in" data-spinner-size="40">
        <span class="ladda-label"><?php echo \Bookly\Lib\Utils\Common::getTranslatedOption( 'bookly_l10n_button_back' ) ?></span>
    </button>
    <button class="bookly-next-step bookly-js-next-step bookly-btn ladda-button" data-style="zoom-in" data-spinner-size="40">
        <span class="ladda-label"><?php echo \Bookly\Lib\Utils\Common::getTranslatedOption( 'bookly_l10n_step_details_button_next' ) ?></span>
    </button>
</div>