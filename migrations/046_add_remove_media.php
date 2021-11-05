<?php
class AddLogEpisode extends Migration
{

  static $log_actions = [
      [
          'name'        => 'OC_REMOVE_MEDIA',
          'description' => 'Opencast: Episode geloescht',
          'template'    => '%user loeschte Episode %info in %sem(%affected)',
          'active'      => 1
      ],
  ];


  function up()
  {
      $db = DBManager::get();
      $query = $db->prepare("INSERT INTO log_actions (action_id, name, description, info_template, active) VALUES (?, ?, ?, ?, ?)");

      foreach (self::$log_actions as $action) {
          $query->execute(array(md5($action['name']), $action['name'], $action['description'], $action['template'], $action['active']));
      }
  }

  function down()
  {
      $db = DBManager::get();
      $query = $db->prepare("DELETE FROM log_actions WHERE action_id = ?");
      $query2 = $db->prepare("DELETE FROM log_events WHERE action_id = ?");

      foreach (self::$log_actions as $action) {
          $query->execute(array(md5($action['name'])));
          $query2->execute(array(md5($action['name'])));
      }
  }
}
