<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defdroup    Channels Channels
 * @indroup     UnaModules
 *
 * @{
 */

class BxCnlAlertsResponse extends BxBaseModGroupsAlertsResponse
{
    public function __construct()
    {
        $this->MODULE = 'bx_channels';
        parent::__construct();
    }
}

/** @} */
