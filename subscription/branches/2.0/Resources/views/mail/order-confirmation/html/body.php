<?php
/**
 * @var tiFy\Mail\MailView $this
 */
?>
<tr class="rowBodyContent">
    <td>
        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
            <tr class="rowBodyContent-section rowBodyContent-section--header">
                <td>
                    <h1 class="Title--1">
                        <?php printf(__('Facture de la commande : %s', 'tify'), $this->get(
                                'order.number', $this->get('order.id')
                        )); ?>
                    </h1>
                </td>
            </tr>

            <tr class="rowBodyContent-section rowBodyContent-section--body">
                <td>
                    <p>
                        <?php printf(__('Bonjour, %s', 'tify'), $this->get('billing.display_name')); ?>
                    </p>
                    <p>
                        <?php printf(
                            __('Voici le détail de votre commande passée le %s', 'tify'),
                            $this->get('order.payment_date')
                        ); ?> :
                    </p>
                    <br>
                </td>
            </tr>

            <tr class="rowBodyContent-section rowBodyContent-section--body">
                <td style="padding-bottom: 30px;">
                    <table class="OrderTable" role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                        <thead>
                        <tr>
                            <th style="text-align:left;font-weight:bold;">
                                <?php _e('Réf.', 'tify'); ?>
                            </th>
                            <th style="text-align:center;font-weight:bold;">
                                <?php _e('Désign.', 'tify'); ?>
                            </th>
                            <th style="text-align:center;font-weight:bold;">
                                <?php _e('Qté.', 'tify'); ?>
                            </th>
                            <th style="text-align:center;font-weight:bold;">
                                <?php _e('P.U HT', 'tify'); ?>
                            </th>
                            <th style="text-align:right;font-weight:bold;">
                                <?php _e('P.U Net', 'tify'); ?>
                            </th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($this->get('items', []) as $item) : ?>
                            <tr>
                                <td style="text-align:left;">
                                    <?php echo $item['sku'] ?: '--'; ?>
                                </td>
                                <td style="text-align:center;">
                                    <?php echo $item['label'] ?: '--' ?>
                                </td>
                                <td style="text-align:center;">
                                    <?php echo $item['quantity'] ?: 1; ?>
                                </td>
                                <td style="text-align:center;">
                                    <?php echo ($item['subtotal'] - $item['subtotal_tax']) ?: 0.0; ?>
                                </td>
                                <td style="text-align:right;">
                                    <?php echo $item['subtotal'] ?: 0.0; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </td>
            </tr>

            <tr class="rowBodyContent-section rowBodyContent-section--body">
                <td style="padding-bottom: 30px;font-size:1.4em;">
                    <table class="TotalTable" align="right" role="presentation" cellspacing="0" cellpadding="0"
                           border="0" style="width:30%;">
                        <?php if ($this->get('order.taxable')) : ?>
                            <tr>
                                <th style="text-align:left;font-weight:bold;">
                                    <?php _e('Total HT', 'tify'); ?>
                                </th>
                                <td style="text-align:right;">
                                    <?php echo $this->get('order.total_without_tax'); ?>
                                </td>
                            </tr>
                            <tr>
                                <th style="text-align:left;font-weight:bold;">
                                    <?php _e('Total TVA', 'tify'); ?>
                                </th>
                                <td style="text-align:right;">
                                    <?php echo $this->get('order.tax'); ?>
                                </td>
                            </tr>
                            <tr>
                                <th style="text-align:left;font-weight:bold;">
                                    <?php _e('Total TTC', 'tify'); ?>
                                </th>
                                <td style="text-align:right;">
                                    <?php echo $this->get('order.total'); ?>
                                </td>
                            </tr>
                        <?php else : ?>
                            <tr>
                                <th style="text-align:left;font-weight:bold;">
                                    <?php _e('Total', 'tify'); ?>
                                </th>
                                <td style="text-align:right;">
                                    <?php echo $this->get('order.total'); ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </table>
                </td>
            </tr>

            <tr class="rowBodyContent-section rowBodyContent-section--body">
                <td style="padding-bottom:30px;">
                    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                        <tr>
                            <td style="vertical-align: top;">
                                <h2><?php _e('Facturation', 'tify'); ?></h2>
                                <?php if ($b_company = $this->get('billing.company')) : ?>
                                    <div><?php echo $b_company; ?></div>
                                <?php endif; ?>
                                <?php if ($b_disname = $this->get('billing.display_name')) : ?>
                                    <div><?php echo $b_disname; ?></div>
                                <?php endif; ?>
                                <?php if ($b_ad1 = $this->get('billing.address1')) : ?>
                                    <div><?php echo $b_ad1; ?></div>
                                <?php endif; ?>
                                <?php if ($b_ad2 = $this->get('billing.address2')) : ?>
                                    <div><?php echo $b_ad2; ?></div>
                                <?php endif; ?>
                                <?php if ($this->get('billing.postcode') || $this->get('billing.city')) : ?>
                                    <div>
                                        <?php echo join(' ', array_filter([
                                            $this->get('billing.postcode'),
                                            strtoupper($this->get('billing.city')),
                                        ])); ?>
                                    </div>
                                <?php endif; ?>
                                <?php if ($b_phone = $this->get('billing.phone')) : ?>
                                    <div class="unstyle-auto-detected-links"><?php echo $b_phone; ?></div>
                                <?php endif; ?>
                                <?php if ($b_email = $this->get('billing.email')) : ?>
                                    <div class="unstyle-auto-detected-links"><?php echo $b_email; ?></div>
                                <?php endif; ?>
                            </td>
                            <td style="vertical-align: top;">
                                <h2><?php _e('Livraison', 'tify'); ?></h2>
                                <?php if ($b_company = $this->get('shipping.company')) : ?>
                                    <div><?php echo $b_company; ?></div>
                                <?php endif; ?>
                                <?php if ($b_disname = $this->get('shipping.display_name')) : ?>
                                    <div><?php echo $b_disname; ?></div>
                                <?php endif; ?>
                                <?php if ($b_ad1 = $this->get('shipping.address1')) : ?>
                                    <div><?php echo $b_ad1; ?></div>
                                <?php endif; ?>
                                <?php if ($b_ad2 = $this->get('shipping.address2')) : ?>
                                    <div><?php echo $b_ad2; ?></div>
                                <?php endif; ?>
                                <?php if ($this->get('shipping.postcode') || $this->get('shipping.city')) : ?>
                                    <div>
                                        <?php echo join(' ', array_filter([
                                            $this->get('shipping.postcode'),
                                            strtoupper($this->get('shipping.city')),
                                        ])); ?>
                                    </div>
                                <?php endif; ?>
                                <?php if ($b_phone = $this->get('shipping.phone')) : ?>
                                    <div class="unstyle-auto-detected-links"><?php echo $b_phone; ?></div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>

            <?php if ($pdf_url = $this->get('url.pdf')) :?>
            <tr class="rowBodyContent-section rowBodyContent-section--footer">
                <td>
                    <table align="center" role="presentation" cellspacing="0" cellpadding="0" border="0"
                           style="margin: auto;">
                        <tr>
                            <td class="ButtonTd--1">
                                <?php echo partial('tag', [
                                    'attrs'   => [
                                        'class'         => 'Button--1',
                                        'clicktracking' => 'off',
                                        'href'          => $this->get('url.pdf'),
                                        'target'        => '_blank',
                                        'title'         => __('Afficher la version PDF de la facture', 'tify'),
                                    ],
                                    'content' => __('Afficher la version PDF', 'tify'),
                                    'tag'     => 'a',
                                ]); ?>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>

            <tr class="rowBodyContent-section rowBodyContent-section--subfooter">
                <td>
                    <p>
                        <i><?php _e(
                                'L\'accès au téléchargement PDF et à la version en ligne nécessite d\'être connecté ' .
                                'à votre compte.',
                                'tify'
                            ); ?></i>
                    </p>
                </td>
            </tr>
            <?php endif; ?>
        </table>
    </td>
</tr>
