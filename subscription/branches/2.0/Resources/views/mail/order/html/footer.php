<?php
/**
 * @var tiFy\Mail\MailView $this
 */
?>
<tr class="rowFooterContent">
    <td>
        <?php if ($order_url = $this->get('url.order')) : ?>
            <?php echo partial('tag', [
                'attrs'   => [
                    'clicktracking' => 'off',
                    'href'          =>  $order_url,
                    'style'         => 'font-weight:bold;',
                    'title'         => __('Consultation de la commande en ligne', 'tify'),
                    'target'        => '_blank',
                ],
                'content' => __('Consulter la version en ligne', 'tify'),
                'tag'     => 'a',
            ]); ?>
            <br>
            <br>
        <?php endif; ?>

        <?php if ($infos = $this->get('infos')) : ?>
            <?php if ($company = $infos['company_name'] ?? null) :?>
                <b><?php echo strtoupper($this->get('infos.company_name')); ?></b>
                <br>
            <?php endif; ?>

            <?php if (
                    !empty($infos['contact_address1']) ||
                    !empty($infos['contact_address2']) ||
                    !empty($infos['contact_address3']) ||
                    !empty($infos['contact_popstcode']) ||
                    !empty($infos['contact_city'])
            ): ?>
                <span class="unstyle-auto-detected-links">
                    <?php echo join(' ', array_filter([
                        $infos['contact_address1'] ?? '',
                        $infos['contact_address2'] ?? '',
                        $infos['contact_address3'] ?? '',
                        $infos['contact_postcode'] ?? '',
                        strtoupper($infos['contact_city'] ?? '')
                    ])); ?>
                </span>
                <br>
            <?php endif; ?>

            <?php if (!empty($infos['contact_phone']) || !empty($infos['contact_fax'])) : ?>
                <span class="unstyle-auto-detected-links">
                    <?php echo join(' - ', array_filter([
                        ($phone = $infos['contact_phone'] ?? '') ? sprintf(__('Tél : %s', 'tify'), $phone) : '',
                        ($fax = $infos['contact_fax'] ?? '') ? sprintf(__('Fax : %s', 'tify'), $fax) : ''
                    ])); ?>
                </span>
            <?php endif; ?>
        <?php endif; ?>
    </td>
</tr>