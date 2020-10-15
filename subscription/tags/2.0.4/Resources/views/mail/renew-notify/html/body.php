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
                        <?php printf(__('Bonjour %s,', 'tify'), $this->get('display_name')); ?>
                    </h1>
                    <p><?php printf(
                            __('Votre abonnement arrive à expiration expiration le <b>%s</b>', 'tify'),
                            $this->get('expiration-date')
                        ); ?></p>
                </td>
            </tr>

            <tr class="rowBodyContent-section rowBodyContent-section--body">
                <td>
                    <?php if($renew = $this->get('renew-link')) : ?>
                    <p>
                        <?php echo $renew; ?>
                        <br>
                        <br>
                    </p>
                    <?php endif; ?>

                    <?php if ($privacy = $this->get('privacy-policy', '')) : ?>
                        <p>
                            <?php printf(__('En savoir plus sur la %s.', 'tify'), $privacy); ?>
                            <br>
                            <br>
                        </p>
                    <?php endif; ?>

                    <?php if ($terms = $this->get('terms-of-use', '')) : ?>
                        <p>
                            <?php printf(__('Consulter en ligne les %s.', 'tify'), $terms); ?>
                            <br>
                            <br>
                        </p>
                    <?php endif; ?>
                </td>
            </tr>

            <tr class="rowBodyContent-section rowBodyContent-section--footer">
                <td>&nbsp;
                    <p><?php _e('Cordialement,', 'tify'); ?></p>
                    <p>
                        <?php echo partial('tag', [
                            'attrs'   => [
                                'clicktracking' => 'off',
                                'href'          => 'mailto:' . get_option('admin_email'),
                                'target'        => '_blank',
                                'title'         => sprintf(
                                    __('Contacter %s', 'tify'),
                                    get_bloginfo('blogname')
                                ),
                            ],
                            'content' => get_option('admin_email'),
                            'tag'     => 'a',
                        ]); ?>
                    </p>
                </td>
            </tr>
        </table>
    </td>
</tr>