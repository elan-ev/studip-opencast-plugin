<?
    if ($success = $flash['success']) {
        echo MessageBox::success($success);
    }
    if ($error = $flash['error']) {
        echo MessageBox::error($error);
    }
    if ($flash['question']) {
        echo $flash['question'];
    }


    $infobox_content = array(array(
        'kategorie' => _('Hinweise:'),
        'eintrag'   => array(array(
            'icon' => 'icons/16/black/info.png',
            'text' => _("Hier können die entsprechenden Stud.IP Ressourcen mit den Capture Agents aus dem Opencast Matterhorn System verknüpfen.")
        ))
    ));
    $infobox = array('picture' => 'infobox/administration.jpg', 'content' => $infobox_content);
?>
<script language="JavaScript">
OC.initAdmin();
</script>

<h3> <?= _("Zuweisung der Capture Agents") ?> </h3>


<? foreach ($resources as $resource) :?>
    <div class="resources">
        <div class="topic resource"> <?= $resource['name'] ?> </div>
        <div class="resource rcontent">
            <select>
                <? foreach ($agents as $agent) : ?>
                <option> <?= $agent->agent->name ?> </option>
                <? endforeach; ?>
            </select>
        </div>
    </div>

<? endforeach; ?>



