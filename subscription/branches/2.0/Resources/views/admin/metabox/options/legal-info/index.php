<?php
/**
 * @var tiFy\Contracts\Metabox\MetaboxView $this
 * @var tiFy\Plugins\Subscription\SubscriptionSettings $settings
 */
?>
<table class="Form-table">
    <tr>
        <th><?php _e('Conditions générales de vente', 'tify'); ?></th>
        <td>
            <?php wp_dropdown_pages([
                'name'             => $this->name() . '[terms_of_use]',
                'post_type'        => 'page',
                'selected'         => $settings->getTermsOfUsePageId(),
                'sort_order'       => 'ASC',
                'sort_column'      => 'menu_order,post_title',
                'show_option_none' => __('Choix de la page de conditions générales de vente', 'tify'),
            ]);
            ?>
        </td>
    </tr>
    <tr>
        <th><?php _e('Politique de confidentialité', 'tify'); ?></th>
        <td>
            <?php wp_dropdown_pages([
                'name'             => $this->name() . '[privacy_policy]',
                'post_type'        => 'page',
                'selected'         => $settings->getPrivacyPolicyPageId(),
                'sort_order'       => 'ASC',
                'sort_column'      => 'menu_order,post_title',
                'show_option_none' => __('Choix de la page de politique de confidentialité', 'tify'),
            ]);
            ?>
        </td>
    </tr>
</table>