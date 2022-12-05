<form class="default" action="<?= $launch_url ?>" name="ltiLaunchForm" id="ltiLaunchForm" method="post" encType="application/x-www-form-urlencoded">
    <div class="oc--loading-spinner oc--loading-redirect">
        <div class="oc--spinner"></div>
    </div>
    <? foreach ($launch_data as $name => $value) : ?>
        <input type="hidden" name="<?= htmlspecialchars($name) ?>" value="<?= htmlspecialchars($value) ?>">
    <? endforeach ?>
</form>

<script type="text/javascript">
    jQuery(function ($) {
        $('#ltiLaunchForm').submit();
    });
</script>