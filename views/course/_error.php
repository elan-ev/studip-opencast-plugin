<? if (isset($this->flash['error'])) : ?>
    <? if (is_array($this->flash['error'])) : ?>
        <? foreach ($this->flash['error'] as $msg) : ?>
            <?= MessageBox::error($msg); ?>
        <? endforeach ?>
    <? else : ?>
        <?= MessageBox::error($this->flash['error']); ?>
    <? endif ?>
<? else : ?>
    <?= MessageBox::error($_('Es wurde keine Fehlermeldung gesetzt.')); ?>
<? endif ?>
