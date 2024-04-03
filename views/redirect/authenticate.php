<form class="default" action="<?= htmlspecialchars($launch_url) ?>" name="ltiLaunchForm" id="ltiLaunchForm" method="post" encType="application/x-www-form-urlencoded">
    <? foreach ($launch_data as $name => $value) : ?>
        <input type="hidden" name="<?= htmlspecialchars($name) ?>" value="<?= htmlspecialchars($value) ?>">
    <? endforeach ?>
</form>

<script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function() {
        $('#ltiLaunchForm').submit();
    });
</script>