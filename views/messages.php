<? if (!empty($flash['messages'])) foreach ($flash['messages'] as $type => $message): ?>
    <?= MessageBox::$type($message) ?>
<? endforeach ?>