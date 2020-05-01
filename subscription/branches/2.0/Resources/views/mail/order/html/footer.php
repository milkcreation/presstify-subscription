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
                    'title'         => __('Consultation de la commande en ligne', 'theme'),
                    'target'        => '_blank',
                ],
                'content' => __('Consulter la version en ligne', 'theme'),
                'tag'     => 'a',
            ]); ?>
            <br>
            <br>
        <?php endif; ?>

        <b><?php echo strtoupper($this->get('infos.company_name')); ?></b>
        <br>
        <span class="unstyle-auto-detected-links">
            <?php echo join(' ', array_filter([
                $this->get('infos.contact_address1'),
                $this->get('infos.contact_address2'),
                $this->get('infos.contact_address3'),
                $this->get('infos.contact_postcode'),
                strtoupper($this->get('contact_city'))
            ])); ?>
            <br>
            <?php echo join(' - ', array_filter([
                ($phone = $this->get('infos.contact_phone')) ? sprintf(__('Tél : %s', 'theme'), $phone) : null,
                ($fax = $this->get('infos.contact_fax')) ? sprintf(__('Fax : %s', 'theme'), $fax) : null
            ])); ?>
        </span>
    </td>
</tr>