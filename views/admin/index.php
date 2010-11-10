<? if (isset($message)): ?>
  <?= MessageBox::success($message) ?>
<? endif ?>

<?
$infobox_content = array(array(
    'kategorie' => _('Hinweise:'),
    'eintrag'   => array(array(
        'icon' => 'icons/16/black/info.png',
        'text' => _('Dies ist das Demo-Plugin.')
    ))
));

$infobox = array('picture' => 'infobox/administration.jpg', 'content' => $infobox_content);
?>

<h1><?=_('Globale Opencast Einstellungen')?></h1>
<h2><?=("Übersicht aller Series aus Opencast Matterhorn")?></h2>
<p><?=sprintf(_("Es sind bislang %s Series im Opencast Matterhorn vorhanden. Es folgt eine Liste aller Series:"), $series->count())?>  </p>
<table>
	<tr>
		
		<th><?=_("Name")?></th>
		<th><?=_("Vortagender")?></th>
		<th><?=_("Einrichtung")?></th>
		<th><?=_("Beschreibung")?></th>
		<th><?=_("Sprache")?></th>
		<th><?=_("Lizenz")?></th>
		<th><?=_("Aktionen")?></th>
	</tr>
	<? foreach ($series as $key => $serie) :?>
	<tr>
		    <? $id =$serie->seriesId[0];
		       $metadataList = $serie->metadataList;
		    ?>
			
			<? if ($metadataList->children()->count() > 2) : ?>
				<?
				$lecturer = $metadataList->metadata[0]->children()->value;
				$institution = $metadataList->metadata[1]->children()->value;
				$description = $metadataList->metadata[2]->children()->value;
				$lang = $metadataList->metadata[3]->children()->value;
				?>
				<td><?=mb_convert_encoding($metadataList->metadata[6]->children()->value, 'ISO-8859-1', 'UTF-8')?></td>
				<td><?=mb_convert_encoding($lecturer, 'ISO-8859-1', 'UTF-8')?></td>
				<td><?=$institution?></td>
				<td><?=mb_convert_encoding($description, 'ISO-8859-1', 'UTF-8')?></td>
				<td><?=$lang?></td>
				<td><?=mb_convert_encoding($metadataList->metadata[4]->children()->value, 'ISO-8859-1', 'UTF-8')?></td>
			<? else : ?>
				<td><?=$metadataList->metadata[1]->children()->value?></td>
				<td>-</td>
				<td>-</td>
				<td>-</td>
				<td>-</td>
				<td>-</td>

			<? endif ?>
			<td><a href="<?=PluginEngine::getLink('opencast/admin/edit_series/'.$id)?>"><?= makebutton("bearbeiten")?></a></td>
	</tr>
	<? endforeach ?>
	
</table>
