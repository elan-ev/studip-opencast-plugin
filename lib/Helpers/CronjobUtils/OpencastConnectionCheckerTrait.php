<?php

namespace Opencast\Helpers\CronjobUtils;

use Opencast\Models\REST\Config as OCConfig;
use Opencast\Models\Config as ConfigModel;

trait OpencastConnectionCheckerTrait
{
    /**
     * @var string $not_reachable_message Message to display when Opencast is not reachable.
     */
    private $not_reachable_message = 'Opencast is currently unreachable. Process postponed.';

    /**
     * @var string $not_reachable_message Message to display when Opencast is not reachable.
     */
    private $maintenance_message = 'Opencast is currently under maintenance. Process postponed.';

    /**
     * Checks if the Opencast instance is reachable for the given configuration ID.
     *
     * This method attempts to connect to the Opencast API using the provided configuration ID.
     * If the connection is successful, it returns true. If not, it prints a message and returns false.
     * Use this method in cronjob classes before performing any operations that require a working Opencast connection.
     *
     * @param int $config_id The ID of the Opencast configuration to check.
     * @param bool $check_engage_node A flag to determine whether the check should be performed against engage node (e.g play or search endpoint)
     * @return bool True if Opencast is reachable, false otherwise.
     */
    protected function isOpencastReachable(int $config_id, bool $check_engage_node = false): bool {
        $is_opencast_reachable = false;
        $message = $this->not_reachable_message;
        try {
            $is_opencast_reachable = OCConfig::checkOpencastAPIConnectivity($config_id, $check_engage_node);
        } catch (\Throwable $th) {
            $message .= ': ' . $th->getMessage();
        }
        if (!$is_opencast_reachable) {
            echo $message . "\n";
        }
        return $is_opencast_reachable;
    }

    /**
     * Checks if the Opencast instance is under maintenance for the given configuration ID.
     *
     * This method checks if the Opencast instance is currently under maintenance.
     * If it is, it prints a message and returns true. Otherwise, it returns false.
     *
     * @param int $config_id The ID of the Opencast configuration to check.
     * @return bool True if Opencast is under maintenance, false otherwise.
     */
    protected function isOpencastUnderMaintenance(int $config_id)
    {
        $oc_config = ConfigModel::findOneBySql("id = ?", [$config_id]);
        if ($oc_config) {
            list($maintenance_active, $read_only) = $oc_config->isUnderMaintenance();
            if ($maintenance_active) {
                echo $this->maintenance_message . "\n";
                return true;
            }
        }
        return false;
    }
}
