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
    <div>
        <span class="topic"> <?= $resource['name'] ?> </span>
        <div>
            <select>
                <option>Capture Agent 1</option>
            </select>
        </div>
    </div>

<? endforeach; ?>





<?// var_dump($resources) ?>