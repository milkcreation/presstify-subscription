<?php
/**
 * @var tiFy\Mail\MailView $this
 */
?>
<tr class="rowHeaderContent">
    <td>
        <?php echo partial('tag', [
            'attrs' => [
                'class'  => 'BodyHeader-logo',
                'src'    => $this->get('infos.logo.src'),
                'width'  => $this->get('infos.logo.width', 200),
                'height' => $this->get('infos.logo.height', 40),
                'alt'    => $this->get('infos.logo.alt', __('Logo', 'theme')),
                'border' => 0,
            ],
            'tag'   => 'img',
        ]); ?>
    </td>
</tr>