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
<script src="/trunk/plugins_packages/elan-ev/OpenCast/vendor/upload/js/jquery.fileupload.js"></script>
<script src="/trunk/plugins_packages/elan-ev/OpenCast/vendor/upload/js/vendor/jquery.ui.widget.js"></script>
<script src="/trunk/plugins_packages/elan-ev/OpenCast/vendor/upload/js/jquery.iframe-transport.js"></script>
<script language="javascript">
    $(function (){
        $('#video_upload').fileupload({
            maxChunkSize: <?= OC_UPLOAD_CHUNK_SIZE ?>,
            multipart: false
        });
    });
</script>
<h2><?= _("Medienupoad") ?></h2>

<form action="<?= PluginEngine::getLink('opencast/upload/upload_file/') ?>" enctype="multipart/form-data" method="post">
    

    <div>                      
        <ul>              
            <li>                
                <label id="title" for="titleField">
                    <span>* </span>
                    Title:
                </label>                
                <input type="text" maxlength="255" name="title" id="titleField">              
            </li>              
            <li>                
                <label id="creatorLabel" for="creator">
                    <span>Presenter</span>:
                </label>                
                <input type="text" maxlength="255" name="creator" id="creator">              
            </li>              
                     
            <li>                
                <label id="recordingDateLabel" class="scheduler-label">
                    <span>* </span>
                    <span id="i18n_date_label">Recording Date</span>:
                </label>                
                <input type="text" name="recordDate" id="recordDate" size="10">
            </li>               
            <li>                
                <label id="startTimeLabel">
                    <span>* </span>
                    <span id="i18n_starttime_label">Start Time</span>:
                </label>                
                <select id="startTimeHour" name="startTimeHour">                  
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
                    <option value="30">30</option>                  
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
            </li>            
        </ul>                  
    </div>
    
    <div>          
        <ul>              
            <li class="additionalMeta">                
                <label id="contributorLabel" for="contributor">
                    <span id="i18n_dept_label">Contributor</span>:
                </label>                
                <input type="text" maxlength="255" id="contributor" name="contributor">              
            </li>              
            <li>                
                <label id="subjectLabel" for="subject">
                    <span id="i18n_sub_label">Subject</span>:
                </label>                
                <input type="text" maxlength="255" id="subject" name="subject">              
            </li>              
            <li>                
                <label id="languageLabel" for="language">
                    <span id="i18n_lang_label">Language</span>:
                </label>                
                <input type="text" maxlength="255" id="language" name="language">              
            </li>              
            <li>                
                <label id="descriptionLabel" for="description">
                    <span id="i18n_desc_label">Description</span>:
                </label>                
                <textarea cols="10" rows="5" id="description" name="description"></textarea>              
            </li>            
        </ul>          
    </div>

    <label for="video_upload">Datei:</label>
    <input name="video" type="file" id="video_upload">
    <div class="form_submit">
<?= Button::createAccept(_('Übernehmen')) ?>
<?= LinkButton::createCancel(_('Abbrechen'), PluginEngine::getLink('opencast/admin/config/')) ?>
    </div>
</form>



