<?

use Studip\Button;
use Studip\LinkButton;
use Opencast\LTI\OpencastLTI;
use Opencast\LTI\LtiLink;

?>

<?
$vis = !is_null(CourseConfig::get($course_id)->COURSE_HIDE_EPISODES)
    ? boolval(CourseConfig::get($course_id)->COURSE_HIDE_EPISODES)
    : \Config::get()->OPENCAST_HIDE_EPISODES;
?>

<form id="upload_form" action="#" enctype="multipart/form-data" method="post" class="default">

    <input type="hidden" name="series_id" value="<?= $series_id ?>">

    <?
    $oc_acl = '';
    if (OCPerm::editAllowed($course_id)) {
        $oc_acl          = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Policy PolicyId="mediapackage-1"
  RuleCombiningAlgId="urn:oasis:names:tc:xacml:1.0:rule-combining-algorithm:permit-overrides"
  Version="2.0"
  xmlns="urn:oasis:names:tc:xacml:2.0:policy:schema:os">
  <Rule RuleId="user_read_Permit" Effect="Permit">
    <Target>
      <Actions>
        <Action>
          <ActionMatch MatchId="urn:oasis:names:tc:xacml:1.0:function:string-equal">
            <AttributeValue DataType="http://www.w3.org/2001/XMLSchema#string">read</AttributeValue>
            <ActionAttributeDesignator AttributeId="urn:oasis:names:tc:xacml:1.0:action:action-id"
              DataType="http://www.w3.org/2001/XMLSchema#string"/>
          </ActionMatch>
        </Action>
      </Actions>
    </Target>
    <Condition>
      <Apply FunctionId="urn:oasis:names:tc:xacml:1.0:function:string-is-in">
        <AttributeValue DataType="http://www.w3.org/2001/XMLSchema#string">ROLE_USER_LTI_Instructor</AttributeValue>
        <SubjectAttributeDesignator AttributeId="urn:oasis:names:tc:xacml:2.0:subject:role"
          DataType="http://www.w3.org/2001/XMLSchema#string"/>
      </Apply>
    </Condition>
  </Rule>';

if($vis == false){
  $oc_acl.='<Rule RuleId="user_read_Permit" Effect="Permit">
    <Target>
      <Actions>
        <Action>
          <ActionMatch MatchId="urn:oasis:names:tc:xacml:1.0:function:string-equal">
            <AttributeValue DataType="http://www.w3.org/2001/XMLSchema#string">read</AttributeValue>
            <ActionAttributeDesignator AttributeId="urn:oasis:names:tc:xacml:1.0:action:action-id"
              DataType="http://www.w3.org/2001/XMLSchema#string"/>
          </ActionMatch>
        </Action>
      </Actions>
    </Target>
    <Condition>
      <Apply FunctionId="urn:oasis:names:tc:xacml:1.0:function:string-is-in">
        <AttributeValue DataType="http://www.w3.org/2001/XMLSchema#string">ROLE_USER_LTI_Learner</AttributeValue>
        <SubjectAttributeDesignator AttributeId="urn:oasis:names:tc:xacml:2.0:subject:role"
          DataType="http://www.w3.org/2001/XMLSchema#string"/>
      </Apply>
    </Condition>
  </Rule>';
}
  $oc_acl.='
  <Rule RuleId="user_write_Permit" Effect="Permit">
    <Target>
      <Actions>
        <Action>
          <ActionMatch MatchId="urn:oasis:names:tc:xacml:1.0:function:string-equal">
            <AttributeValue DataType="http://www.w3.org/2001/XMLSchema#string">write</AttributeValue>
            <ActionAttributeDesignator AttributeId="urn:oasis:names:tc:xacml:1.0:action:action-id"
              DataType="http://www.w3.org/2001/XMLSchema#string"/>
          </ActionMatch>
        </Action>
      </Actions>
    </Target>
    <Condition>
      <Apply FunctionId="urn:oasis:names:tc:xacml:1.0:function:string-is-in">
        <AttributeValue DataType="http://www.w3.org/2001/XMLSchema#string">ROLE_USER_LTI_Instructor</AttributeValue>
        <SubjectAttributeDesignator AttributeId="urn:oasis:names:tc:xacml:2.0:subject:role"
          DataType="http://www.w3.org/2001/XMLSchema#string"/>
      </Apply>
    </Condition>
  </Rule>
  <Rule RuleId="ROLE_ADMIN_read_Permit" Effect="Permit">
    <Target>
      <Actions>
        <Action>
          <ActionMatch MatchId="urn:oasis:names:tc:xacml:1.0:function:string-equal">
            <AttributeValue DataType="http://www.w3.org/2001/XMLSchema#string">read</AttributeValue>
            <ActionAttributeDesignator AttributeId="urn:oasis:names:tc:xacml:1.0:action:action-id" DataType="http://www.w3.org/2001/XMLSchema#string"/>
          </ActionMatch>
        </Action>
      </Actions>
    </Target>
    <Condition>
      <Apply FunctionId="urn:oasis:names:tc:xacml:1.0:function:string-is-in">
        <AttributeValue DataType="http://www.w3.org/2001/XMLSchema#string">ROLE_ADMIN</AttributeValue>
        <SubjectAttributeDesignator AttributeId="urn:oasis:names:tc:xacml:2.0:subject:role" DataType="http://www.w3.org/2001/XMLSchema#string"/>
      </Apply>
    </Condition>
  </Rule>
  <Rule RuleId="ROLE_ADMIN_write_Permit" Effect="Permit">
    <Target>
      <Actions>
        <Action>
          <ActionMatch MatchId="urn:oasis:names:tc:xacml:1.0:function:string-equal">
            <AttributeValue DataType="http://www.w3.org/2001/XMLSchema#string">write</AttributeValue>
            <ActionAttributeDesignator AttributeId="urn:oasis:names:tc:xacml:1.0:action:action-id" DataType="http://www.w3.org/2001/XMLSchema#string"/>
          </ActionMatch>
        </Action>
      </Actions>
    </Target>
    <Condition>
      <Apply FunctionId="urn:oasis:names:tc:xacml:1.0:function:string-is-in">
        <AttributeValue DataType="http://www.w3.org/2001/XMLSchema#string">ROLE_ADMIN</AttributeValue>
        <SubjectAttributeDesignator AttributeId="urn:oasis:names:tc:xacml:2.0:subject:role" DataType="http://www.w3.org/2001/XMLSchema#string"/>
      </Apply>
    </Condition>
  </Rule>
</Policy>
';
        $instructor_role = $course_id . '_Instructor';
        $learner_role    = $course_id . '_Learner';
        $oc_acl          = str_replace('ROLE_USER_LTI_Instructor', $instructor_role, $oc_acl);
        if ($vis == false) {
            $oc_acl      = str_replace('ROLE_USER_LTI_Learner', $learner_role, $oc_acl);
        }
        $oc_acl          = str_replace(["\r", "\n"], '', $oc_acl);
        $oc_acl          = urlencode($oc_acl);
    }

    ?>
    <input type="hidden" name="oc_acl" value="<?= $oc_acl ?>">

    <label>
        <span class="required">
            <?= $_('Titel') ?>
        </span>

        <input class="oc_input" type="text" maxlength="255" name="title" id="titleField" required>
    </label>


    <label>
        <span class="required">
            <?= $_("Vortragende") ?>
        </span>

        <input class="oc_input" type="text" maxlength="255" name="creator" id="creator"
               value="<?= get_fullname_from_uname(
                   $GLOBALS['user']->username
               ) ?>" required>
    </label>


    <label>
        <span class="required">
            <?= $_('Aufnahmezeitpunkt') ?>
        </span>

        <input class="oc_input" type="text" name="recordDate" value="<?= date(
            'd.m.Y H:i'
        ) ?>" id="recordDate" maxlength="10" required data-datetime-picker>
    </label>

    <label>
        <?= $_('Mitwirkende') ?>
        <input type="text" maxlength="255" id="contributor" name="contributor"
               value="<?= get_fullname_from_uname(
                   $GLOBALS['auth']->auth['uname']
               ) ?>">
    </label>

    <label>
        <?= $_('Thema') ?>
        <input type="text" maxlength="255" id="subject"
               name="subject" value="Medienupload, Stud.IP">
    </label>

    <label style="display:none">
        <?= $_('Sprache') ?>
        <input type="text" maxlength="255" id="language" name="language" value="<?= 'de' ?>">
    </label>

    <label>
        <?= $_('Beschreibung') ?>
        <textarea class="oc_input" cols="50" rows="5" id="description" name="description"></textarea>
    </label>

    <section class="hgroup">
        <label>
            <?= $_('Eingestellter Workflow') ?>

            <? if (!$workflow) : ?>
                <p style="color:red; max-width: 48em;">
                    <?= $_(
                        'Es wurde noch kein Standardworkflow eingestellt. Das Hochladen ist erst möglich nach Einstellung eines Standard- oder eines Kursspezifischen Workflows!'
                    ) ?>
                </p>
            <? else : ?>
                <p><?= $workflow_text ?: $workflow['workflow_id'] ?></p>
            <? endif ?>
        </label>
    </section>

    <label for="video_upload">
        <span class="required">
            <?= $_('Datei(en)') ?>
        </span>
        <p class="help">
            <?= $_("Mindestens ein Video wird benötigt. Unterstützte Formate sind .mkv, .avi, .mp4, .mpeg, .webm, .mov, .ogv, .ogg, .flv, .f4v, .wmv, .asf, .mpg, .mpeg, .ts, .3gp und .3g2.") ?>
        </p>
    </label>

    <ul class="oc-media-upload-info">
    </ul>

    <div>
        <?= LinkButton::createAdd($_('Screencast hinzufügen'), null, ['class' => 'oc-media-upload-add', 'data-flavor' => 'presentation/source']) ?>
        <input type="file" class="video_upload" data-flavor="presentation/source"
               accept=".avi,.mkv,.mp4,.webm,.mov,.ogg,.ogv,video/mp4,video/x-m4v,video/webm,video/ogg,video/mpeg,video/*">
        <div style="display:none" class="invalid_media_type_warning">
          <?= MessageBox::error(
              $_('Die gewählte Datei kann von Opencast nicht verarbeitet werden.'),
              [
                  $_('Unterstützte Formate sind .mkv, .avi, .mp4, .mpeg, .webm, .mov, .ogv, .ogg, .flv, .f4v, .wmv, .asf, .mpg, .mpeg, .ts, .3gp und .3g2.')
              ]
           ) ?>
         </div>
         <p><?= $_('Screencasts bestehend überwiegend aus Präsentationsfolien oder statischen Bildinhalten. Folienwechsel und Text sollen erkannt werden.') ?></p>

         <?= LinkButton::createAdd($_('Kamera-Aufzeichung hinzufügen'), null, ['class' => 'oc-media-upload-add', 'data-flavor' => 'presenter/source']) ?>
         <input type="file" class="video_upload" data-flavor="presenter/source"
                accept=".avi,.mkv,.mp4,.webm,.mov,.ogg,.ogv,video/mp4,video/x-m4v,video/webm,video/ogg,video/mpeg,video/*">
         <div style="display:none" class="invalid_media_type_warning">
           <?= MessageBox::error(
               $_('Die gewählte Datei kann von Opencast nicht verarbeitet werden.'),
               [
                   $_('Unterstützte Formate sind .mkv, .avi, .mp4, .mpeg, .webm, .mov, .ogv, .ogg, .flv, .f4v, .wmv, .asf, .mpg, .mpeg, .ts, .3gp und .3g2.')
               ]
           ) ?>
         </div>
         <p><?= $_('Kamera-Video, oder Video das allein oder parallel zum Screencast wiedergegeben werden soll und überwiegend nicht-textliche Bild-Inhalte enthält.') ?></p>
    </div>

    <?= MessageBox::info(
        formatReady(Config::get()->OPENCAST_UPLOAD_INFO_TEXT_HEADING),
        array_map('formatReady', explode("\n-", preg_replace('/^- *?/', '', Config::get()->OPENCAST_UPLOAD_INFO_TEXT_BODY)))
    ) ?>

    <footer>
        <? if ($workflow): ?>
            <?= Button::createAccept($_('Medien hochladen'), null, [
                'id' => 'btn_accept'
            ]) ?>
        <? endif ?>

        <?= LinkButton::createCancel(
            $_('Abbrechen'),
            $controller->url_for('course/index')
        ) ?>
    </footer>
</form>

<div id="oc-media-upload-dialog" style="display: none;">
    <div class="oc-media-upload-dialog-content">
        <h1 class="hide-in-dialog"><?= $_("Medien hochladen") ?></h1>
        <p><?= $_("Ihre Medien werden gerade hochgeladen.") ?></p>
        <div>
            <ul class="files">
            </ul>
        </div>
    </div>
</div>

<script type="text/javascript">
    jQuery(function () {
        OC.initUpload(<?= json_encode($config['service_url']) ?>);
    });
</script>

<?
if ($this->connectedSeries[0]['series_id']) :
    $current_user_id = $GLOBALS['auth']->auth['uid'];


    if (OCPerm::editAllowed($course_id)) {
        $upload_lti_link = new LtiLink(
            $config['service_url'] . '/lti',
            $config['lti_consumerkey'],
            $config['lti_consumersecret']
        );

        $upload_lti_link->addCustomParameter('tool', '/ltitools');

        $upload_lti_link->setUser($current_user_id, 'Instructor');
        $upload_lti_link->setCourse($course_id);
        $upload_lti_link->setResource(
            $this->connectedSeries[0]['series_id'],
            'series'
        );

        $upload_launch_data = $upload_lti_link->getBasicLaunchData();
        $upload_signature   = $upload_lti_link->getLaunchSignature($upload_launch_data);

        $upload_launch_data['oauth_signature'] = $upload_signature;
    }
    ?>

    <script>
            <? if ($upload_lti_link): ?>
            OC.ltiCall('<?= $upload_lti_link->getLaunchURL() ?>', <?= json_encode($upload_launch_data) ?>, function () {
            });
            <? endif ?>
    </script>
<? endif ?>
