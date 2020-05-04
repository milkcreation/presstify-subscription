<?php
/**
 * @var tiFy\Contracts\Metabox\MetaboxView $this
 * @var WP_Post $wp_post
 * @var tiFy\Plugins\Subscription\QuerySubscription $subscription
 */
?>
<h3 class="Form-title"><?php _e('Client', 'tify'); ?></h3>
<table class="Form-table">
    <tr>
        <th><?php _e('Identification', 'tify'); ?></th>
        <td>
            <?php if ($user = $subscription->getCustomer()->getUser()) : ?>
                <?php echo field('text', [
                    'attrs' => [
                        'class' => 'widefat',
                        'readonly',
                    ],
                    'value' => '#' . $user->getId() . ' - ' . $user->getEmail(),
                ]); ?>
                <div>
                    <br>
                    <?php echo partial('tag', [
                        'attrs'   => [
                            'class' => 'button-secondary',
                            'href'  => 'mailto:' . $user->getEmail(),
                            'title' => sprintf(__('Envoyer un mail à %s', 'tify'), $user->getDisplayName()),
                        ],
                        'content' => __('Contacter par mail', 'tify'),
                        'tag'     => 'a',
                    ]); ?>&nbsp;<?php echo partial('tag', [
                        'attrs'   => [
                            'class' => 'button-secondary',
                            'href'  => $user->getEditUrl(),
                            'title' => sprintf(__('Editer l\'utilisateur %s', 'tify'), $user->getDisplayName()),
                        ],
                        'content' => __('Éditer', 'tify'),
                        'tag'     => 'a',
                    ]); ?>
                </div>
            <?php elseif ($email = $subscription->getCustomerEmail()) : ?>
                <?php echo partial('tag', [
                    'attrs'   => [
                        'class' => 'button-secondary',
                        'href'  => 'mailto:' . $email,
                        'title' => sprintf(__('Envoyer un mail à %s', 'tify'), $email),
                    ],
                    'content' => $email,
                    'tag'     => 'a',
                ]); ?>
            <?php endif; ?>
        </td>
    </tr>
</table>

<h3 class="Form-title"><?php _e('Identification', 'tify'); ?></h3>
<table class="Form-table">
    <tr>
        <th><?php _e('Intitulé', 'tify'); ?></th>
        <td>
            <?php echo field('text', [
                'attrs' => [
                    'class' => 'widefat',
                ],
                'name'  => '_product_label',
                'value' => $subscription->getLabel(),
            ]); ?>
        </td>
    </tr>
    <?php if ($offer = $subscription->getOffer()) : ?>
        <tr>
            <th><?php _e('Offre associée', 'tify'); ?></th>
            <td>
                <?php echo partial('tag', [
                    'attrs'   => [
                        'class' => 'button-secondary',
                        'href'  => $offer->getEditUrl(),
                    ],
                    'content' => $offer->getTitle(),
                    'tag'     => 'a',
                ]); ?>
            </td>
        </tr>
    <?php endif; ?>
</table>

<h3 class="Form-title"><?php _e('Engagement', 'tify'); ?></h3>
<table class="Form-table">
    <tr>
        <th><?php _e('Activation', 'tify'); ?></th>
        <td>
            <?php echo field('toggle-switch', [
                'name'  => '_limited',
                'value' => $subscription->isLimitedEnabled() ? 'on' : 'off',
            ]); ?>
        </td>
    </tr>
</table>
<table class="Form-table LimitedEnabled<?php echo $subscription->isLimitedEnabled() ? '' : ' hidden'; ?>">
    <tr>
        <th><?php _e('Durée de l\'abonnement', 'tify'); ?></th>
        <td>
            <?php echo field('number', [
                'attrs' => [
                    'min' => 0,
                    'readonly',
                ],
                'name'  => '_limited_length',
                'value' => $subscription->getLimitedLength(),
            ]); ?>
            <?php echo field('select-js', [
                'choices'  => [
                    'year'  => __('Année(s)', 'tify'),
                    'month' => __('Mois', 'tify'),
                    'day'   => __('Jour(s)', 'tify'),
                ],
                'disabled' => true,
                'name'     => '_limited_unity',
                'value'    => $subscription->getLimitedUnity(),
            ]); ?>
        </td>
    </tr>
    <tr>
        <th><?php _e('Début de l\'abonnement', 'tify'); ?></th>
        <td>
            <?php echo field('datepicker', [
                'attrs' => [
                    'readonly',
                ],
                'name'  => '_start_date',
                'value' => ($date = $subscription->getStartDate()) ? $date->format('d/m/Y') : '',
            ]); ?>
        </td>
    </tr>
    <tr>
        <th><?php _e('Fin de l\'abonnement', 'tify'); ?></th>
        <td>
            <?php echo field('datepicker', [
                'attrs' => [
                    'readonly',
                ],
                'name'  => '_end_date',
                'value' => ($date = $subscription->getEndDate()) ? $date->format('d/m/Y') : '',
            ]); ?>
        </td>
    </tr>
</table>

<h3 class="Form-title"><?php _e('Ré-engagement', 'tify'); ?></h3>
<table class="Form-table">
    <tr>
        <th><?php _e('Activation', 'tify'); ?></th>
        <td>
            <?php echo field('toggle-switch', [
                'name'  => '_renewable',
                'value' => $subscription->isRenewEnabled() ? 'on' : 'off',
            ]); ?>
        </td>
    </tr>
</table>
<table class="Form-table RenewEnabled<?php echo $subscription->isRenewEnabled() ? '' : ' hidden'; ?>">
    <tr>
        <th><?php _e('Possible à partir de', 'tify'); ?></th>
        <td>
            <?php printf(__('%s jours avant la fin de l\'abonnement en cours.', 'tify'), field('number', [
                'attrs' => [
                    'min' => 0,
                ],
                'name'  => '_renew_days',
                'value' => $subscription->getRenewDays(),
            ])); ?>
        </td>
    </tr>
    <tr>
        <th><?php _e('Envoyer une notification par mail à l\'abonné', 'tify'); ?></th>
        <td>
            <?php echo field('toggle-switch', [
                'name'  => '_renew_notify',
                'value' => $subscription->isRenewNotify() ? 'on' : 'off',
            ]); ?>
        </td>
    </tr>
</table>