<?php

namespace Opencast\Helpers\CronjobUtils;

use Opencast\Models\REST\Config as OCConfig;

trait OpencastConnectionCheckerTrait
{
    /**
     * @var string $not_reachable_message Message to display when Opencast is not reachable.
     */
    private $not_reachable_message = 'Opencast is currently unreachable. Process postponed.';

    /**
     * Checks if the Opencast instance is reachable for the given configuration ID.
     *
     * This method attempts to connect to the Opencast API using the provided configuration ID.
     * If the connection is successful, it returns true. If not, it prints a message and returns false.
     * Use this method in cronjob classes before performing any operations that require a working Opencast connection.
     *
     * @param int $config_id The ID of the Opencast configuration to check.
     * @return bool True if Opencast is reachable, false otherwise.
     */
    protected function isOpencastReachable(int $config_id): bool {
        $is_opencast_reachable = false;
        $message = $this->not_reachable_message;
        try {
            $is_opencast_reachable = OCConfig::checkOpencastAPIConnectivity($config_id);
        } catch (\Throwable $th) {
            $message .= ': ' . $th->getMessage();
        }
        if (!$is_opencast_reachable) {
            echo $message;
        }
        return $is_opencast_reachable;
    }
}
