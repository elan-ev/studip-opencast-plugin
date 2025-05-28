<?php
require_once __DIR__.'/../bootstrap.php';
require_once __DIR__.'/../vendor/autoload.php';

use Opencast\Models\PlaylistVideosAuditLog;

class OpencastPlaylistVideosAuditCleanup extends CronJob
{
    public static function getName()
    {
        return _('Opencast - Playlist Videos Audit Logs Cleanup');
    }

    public static function getDescription()
    {
        return _('Opencast: Dieser Cronjob stellt sicher, dass Audit-Log-Einträge zu Playlist-Videos standardmäßig einmal im Monat bereinigt werden. Ziel ist es, zu verhindern, dass die Audit-Log-Tabelle übermäßig wächst und sich mit der Zeit negativ auf Leistung oder Speicher auswirkt.');
    }

    public function execute($last_result, $parameters = array())
    {
        echo "Deleting old entries in the PlaylistVideosAuditLog table...\n";

        $combination_entries = PlaylistVideosAuditLog::findAllCombinationEntries();

        if (empty($combination_entries)) {
            echo "No combination entries found, nothing to delete.\n";
            return;
        }

        foreach ($combination_entries as $entry) {
            $playlist_id = $entry['playlist_id'];
            $video_id = $entry['video_id'];
            $latest = PlaylistVideosAuditLog::findLatestAction($playlist_id, $video_id);
            if (!empty($latest)) {
                echo "  - Trying to delete old entries for playlist ID: " . $playlist_id . " and video ID: " . $video_id . "\n";
                $deleted_nums = PlaylistVideosAuditLog::performOlderEntriesCleanup(
                    $playlist_id,
                    $video_id,
                    $latest->id
                );
                if ($deleted_nums) {
                    $entry_text = $deleted_nums > 1 ? 'entries have' : 'entry has';
                    echo "      ({$deleted_nums}) {$entry_text} been deleted!\n";
                } else {
                    echo "      No entries found!\n";
                }
            }
        }
    }
}
