<?php
/**
 * @var tiFy\Contracts\Field\FieldView $this
 * @var string $name
 * @var string $index
 * @var string $value
 */
?>
<?php $index = !is_numeric($index) ? $index : uniqid(); ?>
<table class="form-table">
    <tbody>
    <tr>
        <th scope="row"><?php _e('Email (requis)', 'tify'); ?></th>
        <td>
            <div class="ThemeInput--email">
                <?php echo field('text', [
                    'name'  => "{$this->getName()}[{$index}][email]",
                    'value' => $this->get("value.email", ''),
                    'attrs' => [
                        'size'         => 40,
                        'autocomplete' => 'off'
                    ]
                ]); ?>
            </div>
        </td>
    </tr>
    <tr>
        <th scope="row"><?php _e('Nom (optionnel)', 'tify'); ?></th>
        <td>
            <div class="ThemeInput--user">
                <?php echo field('text', [
                    'name'  => "{$this->getName()}[{$index}][name]",
                    'value' => $this->get("value.name", ''),
                    'attrs' => [
                        'size'         => 40,
                        'autocomplete' => 'off'
                    ]
                ]); ?>
            </div>
        </td>
    </tr>
    </tbody>
</table>