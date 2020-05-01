<?php
/**
 * @var tiFy\Contracts\Metabox\MetaboxView $this
 * @var tiFy\Plugins\Subscription\SubscriptionSettings $settings
 */
?>
<h3 class="Form-title"><?php _e('Affichage du prix', 'theme'); ?></h3>
<table class="Form-table">
    <tr>
        <th><?php _e('Devise', 'theme'); ?></th>
        <td>
            <?php echo field('select-js', [
                'choices'  => [
                    'EUR' => __('Euros (€)', 'theme'),
                ],
                'disabled' => true,
                'name'     => $this->name() . '[currency]',
                'value'    => $settings->getCurrency(),
            ]); ?>
        </td>
    </tr>
    <tr>
        <th><?php _e('Position de la devise', 'theme'); ?></th>
        <td>
            <?php echo field('select-js', [
                'choices' => [
                    'left'        => __('Gauche', 'theme'),
                    'right'       => __('Droite', 'theme'),
                    'left_space'  => __('Gauche avec espace', 'theme'),
                    'right_space' => __('Droite avec espace', 'theme'),
                ],
                'name'    => $this->name() . '[currency_pos]',
                'value'   => $settings->getCurrencyPos(),
            ]); ?>
        </td>
    </tr>
    <tr>
        <th><?php _e('Séparateur des milliers', 'theme'); ?></th>
        <td>
            <?php echo field('text', [
                'name'  => $this->name() . '[thousand_sep]',
                'value' => $settings->getPriceThousandSeparator() ?: '',
            ]); ?>
        </td>
    </tr>
    <tr>
        <th><?php _e('Séparateur des décimales', 'theme'); ?></th>
        <td>
            <?php echo field('text', [
                'name'  => $this->name() . '[decimal_sep]',
                'value' => $settings->getPriceDecimalSeparator(),
            ]); ?>
        </td>
    </tr>
    <tr>
        <th><?php _e('Nombre de décimales', 'theme'); ?></th>
        <td>
            <?php echo field('number', [
                'name'  => $this->name() . '[num_decimals]',
                'value' => $settings->getPriceDecimals(),
            ]); ?>
        </td>
    </tr>
</table>

<h3 class="Form-title"><?php _e('Gestion de la TVA', 'theme'); ?></h3>
<table class="Form-table">
    <tr>
        <th><?php _e('Activer le calcul et le coût de la TVA', 'theme'); ?></th>
        <td>
            <?php echo field('toggle-switch', [
                'name'  => $this->name() . '[calc_taxes]',
                'value' => $settings->isTaxEnabled() ? 'on' : 'off',
            ]); ?>
        </td>
    </tr>
    <tr>
        <th><?php _e('La saisie des prix se fait en', 'theme'); ?></th>
        <td>
            <?php echo field('toggle-switch', [
                'label_on'  => __('TTC', 'theme'),
                'label_off' => __('HT', 'theme'),
                'name'      => $this->name() . '[include_tax]',
                'value'     => $settings->isPricesIncludeTax() ? 'on' : 'off',
            ]); ?>
        </td>
    </tr>
    <tr>
        <th><?php _e('Affichage des prix', 'theme'); ?></th>
        <td>
            <?php echo field('select-js', [
                'choices' => [
                    'incl' => __('TTC', 'theme'),
                    'excl' => __('HT', 'theme'),
                ],
                'name'    => $this->name() . '[tax_display]',
                'value'   => $settings->getTaxDisplay(),
            ]); ?>
        </td>
    </tr>
    <tr>
        <th><?php _e('Suffixe d\'affichage du prix', 'theme'); ?></th>
        <td>
            <?php echo field('select-js', [
                'attrs'   => [
                    'id'          => 'priceDisplaySuffix-switcher',
                    'data-target' => '#priceDisplaySuffix-customizer',
                ],
                'choices' => [
                    ''         => __('Aucun', 'theme'),
                    'auto'     => __('Indicatif basé sur l\'affichage des prix (si taxe active)', 'theme'),
                    'auto_tax' => __(
                        'Indicatif et prix associé (avec ou sans taxe) basé sur l\'affichage du prix (si taxe active)',
                        'theme'
                    ),
                    'incl'     => __('Forcer l\'affichage du prix TTC (non recommandé)', 'theme'),
                    'excl'     => __('Forcer l\'affichage du prix HT (non recommandé)', 'theme'),
                    'custom'   => __('Personnalisé', 'theme'),
                ],
                'name'    => $this->name() . '[display_suffix]',
                'value'   => $settings->getPriceDisplaySuffix(),
            ]); ?>
            <br>
            <?php echo field('text', [
                'attrs' => [
                    'id'          => 'priceDisplaySuffix-customizer',
                    'class'       => 'widefat',
                    'placeholder' => __('Personnalisation du suffixe d\'affichage', 'theme'),
                ],
                'name'  => $this->name() . '[custom_display_suffix]',
                'value' => $settings->param('price.custom_display_suffix', ''),
            ]); ?>
        </td>
    </tr>
</table>