<?

use Studip\Button,
    Studip\LinkButton;

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
        'eintrag' => array(array(
                'icon' => 'icons/16/black/info.png',
                'text' => _("Hier können Sie AV-Medien direkt in das angebunde OpenCast Matterhorn laden.")
        ))
        ));
$infobox = array('picture' => 'infobox/administration.jpg', 'content' => $infobox_content);
?>
<!-- TODO: adresse per funktion erhalten -->
<script src="<?= $this->rel_canonical_path ?>/vendor/upload/js/jquery.fileupload.js"></script>
<script src="<?= $this->rel_canonical_path ?>/vendor/upload/js/vendor/jquery.ui.widget.js"></script>
<script src="<?= $this->rel_canonical_path ?>/vendor/upload/js/jquery.iframe-transport.js"></script>
<script language="javascript">
    $(function (){
        $('#video_upload').fileupload({
            maxChunkSize: <?= OC_UPLOAD_CHUNK_SIZE ?>,
            limitMultiFileUploads: 1,
            add: function (e, data) {
                
                 $('#btn_accept').click(function(e) {
                     $('#total_file_size').attr('value', data.files[0].size);
                     $( "#progressbar" ).progressbar({
                        value: 0
                     });
                     data.submit();
                     return false;
                 });
           },
           progressall: function(e, data) {
               var progress = parseInt(data.loaded / data.total * 100, 10);
               $( "#progressbar" ).progressbar( "value", progress);
           },
           done: function(e, data) {
               $( "#progressbar" ).progressbar('destroy');
               $('#video_upload').val('');
           }
        });
    });
</script>
    <h2><?= _("Medienupoad") ?></h2>

    <form id="upload_fom" action="<?= PluginEngine::getLink('opencast/upload/upload_file/') ?>" enctype="multipart/form-data" method="post">
    <div>
        <div>
            <label id="title" for="titleField">
                <?= _('Titel')?>:
                <span style="color: red; font-size: 1.6em">* </span>
            </label><br>
            <input type="text" maxlength="255" name="title" id="titleField">
        </div>
        <div>
            <label id="creatorLabel" for="creator">
                <span><?= _("Vortragende")?></span>:
            </label><br>
            <input type="text" maxlength="255" name="creator" id="creator">
        </div>
        <div>
            <label id="recordingDateLabel" class="scheduler-label" for="recordDate">
                <span><?= _('Aufnahmedatum')?></span>:
                <span style="color: red; font-size: 1.6em">* </span>
            </label><br>
            <input type="text" name="recordDate" value="2012-10-28" id="recordDate" size="10">
        </div>
        <div>
            <label id="startTimeLabel">
                <span><?= _('Startzeit')?></span>:
                <span style="color: red; font-size: 1.6em">* </span><br>
                <select id="startTimeHour" name="startTimeHour">
                    <option value="0">00</option>
                    <option value="1" default>01</option>
                    <option value="2">02</option>
                    <option value="3">03</option>
                    <option value="4">04</option>
                    <option value="5">05</option>
                    <option value="6">06</option>
                    <option value="7">07</option>
                    <option value="8">08</option>
                    <option value="9">09</option>
                    <option value="10">10</option>
                    <option value="11">11</option>
                    <option value="12">12</option>
                    <option value="13">13</option>
                    <option value="14">14</option>
                    <option value="15">15</option>
                    <option value="16">16</option>
                    <option value="17">17</option>
                    <option value="18">18</option>
                    <option value="19">19</option>
                    <option value="20">20</option>
                    <option value="21">21</option>
                    <option value="22">22</option>
                    <option value="23">23</option>
                </select>
                <select id="startTimeMin" name="startTimeMin">
                    <option value="0">00</option>
                    <option value="1">01</option>
                    <option value="2">02</option>
                    <option value="3">03</option>
                    <option value="4">04</option>
                    <option value="5">05</option>
                    <option value="6">06</option>
                    <option value="7">07</option>
                    <option value="8">08</option>
                    <option value="9">09</option>
                    <option value="10">10</option>
                    <option value="11">11</option>
                    <option value="12">12</option>
                    <option value="13">13</option>
                    <option value="14">14</option>
                    <option value="15">15</option>
                    <option value="16">16</option>
                    <option value="17">17</option>
                    <option value="18">18</option>
                    <option value="19">19</option>
                    <option value="20">20</option>
                    <option value="21">21</option>
                    <option value="22">22</option>
                    <option value="23">23</option>
                    <option value="24">24</option>
                    <option value="25">25</option>
                    <option value="26">26</option>
                    <option value="27">27</option>
                    <option value="28">28</option>
                    <option value="29">29</option>
                    <option value="30" default>30</option>
                    <option value="31">31</option>
                    <option value="32">32</option>
                    <option value="33">33</option>
                    <option value="34">34</option>
                    <option value="35">35</option>
                    <option value="36">36</option>
                    <option value="37">37</option>
                    <option value="38">38</option>
                    <option value="39">39</option>
                    <option value="40">40</option>
                    <option value="41">41</option>
                    <option value="42">42</option>
                    <option value="43">43</option>
                    <option value="44">44</option>
                    <option value="45">45</option>
                    <option value="46">46</option>
                    <option value="47">47</option>
                    <option value="48">48</option>
                    <option value="49">49</option>
                    <option value="50">50</option>
                    <option value="51">51</option>
                    <option value="52">52</option>
                    <option value="53">53</option>
                    <option value="54">54</option>
                    <option value="55">55</option>
                    <option value="56">56</option>
                    <option value="57">57</option>
                    <option value="58">58</option>
                    <option value="59">59</option>
                </select>
            </label>
        </div>
        <!-- Weitere Metadaten -->
        <div>
            <label id="contributorLabel" for="contributor">
                <span><?= _('Mitwirkende')?></span>:
            </label><br>
            <input type="text" maxlength="255" id="contributor" name="contributor">
        </div>
        <div>
            <label id="subjectLabel" for="subject">
                <span><?= _('Thema')?></span>:
            </label><br>
            <input type="text" maxlength="255" id="subject" name="subject">
        </div>
        <div>
            <label id="languageLabel" for="language">
                <span><?= _('Sprache')?></span>:
            </label><br>
            <input type="text" maxlength="255" id="language" name="language">
        </div>
        <div>
            <label id="descriptionLabel" for="description">
                <span><?= _('Beschreibung')?></span>:
            </label><br>
            <textarea cols="10" rows="5" id="description" name="description"></textarea>
        </div>
        <div>
            <label for="video_upload">Datei:</label><br>
            <input name="video" type="file" id="video_upload">
        </div>
        <div id="progressbarholder" style="overflow: hidden; padding-top: 5px; height: 30px; width:50%; margin: 0 auto;">
            <div id="progressbar"></div>
        </div>
        <input type="hidden" value="" name="total_file_size" id="total_file_size" />
        <div class="form_submit">
            <?= Button::createAccept(_('Übernehmen'), null, array('id' => 'btn_accept')) ?>
            <?= LinkButton::createCancel(_('Abbrechen'), PluginEngine::getLink('opencast/admin/config/')) ?>
        </div>
    </div>
</form>



