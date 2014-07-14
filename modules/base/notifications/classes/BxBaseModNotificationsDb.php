<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) BoonEx Pty Limited - http://www.boonex.com/
 * CC-BY License - http://creativecommons.org/licenses/by/3.0/
 *
 * @defgroup    BaseNotifications Base classes for Notifications like modules
 * @ingroup     DolphinModules
 *
 * @{
 */

bx_import('BxDolModuleDb');

/*
 * Module database queries
 */
class BxBaseModNotificationsDb extends BxDolModuleDb
{
    protected $_oConfig;

    protected $_sTable;
    protected $_sTableHandlers;

    public function __construct(&$oConfig)
    {
        parent::__construct($oConfig);

        $this->_oConfig = $oConfig;

		$this->_sTable = $this->_sPrefix . 'events';
		$this->_sTableHandlers = $this->_sPrefix . 'handlers';
    }

    public function getAlertHandlerId()
    {
        $sQuery = $this->prepare("SELECT `id` FROM `sys_alerts_handlers` WHERE `name`=? LIMIT 1", $this->_oConfig->getSystemName('alert'));
        return (int)$this->getOne($sQuery);
    }

    public function insertData($aData)
    {
    	$aHandlerDescriptor = $this->_oConfig->getHandlerDescriptor();

    	//--- Update Timeline Handlers ---//
        foreach($aData['handlers'] as $aHandler) {
            $sContent = '';
            if($aHandler['type'] == BX_BASE_MOD_NTFS_HANDLER_TYPE_INSERT) {
            	if(empty($aHandler['module_class']))
            		$aHandler['module_class'] = 'Module';

            	$sContent = serialize(array_intersect_key($aHandler, $aHandlerDescriptor));
            }

            $sQuery = $this->prepare("INSERT INTO
                    `{$this->_sTableHandlers}`
                SET
                    `type`=?,
                    `alert_unit`=?,
                    `alert_action`=?,
                    `content`=?", $aHandler['type'], $aHandler['alert_unit'], $aHandler['alert_action'], $sContent);

            $this->query($sQuery);
        }

        //--- Update System Alerts ---//
        $iHandlerId = $this->getAlertHandlerId();
        foreach($aData['alerts'] as $aAlert) {
            $sQuery = $this->prepare("INSERT INTO
                    `sys_alerts`
                SET
                    `unit`=?,
                    `action`=?,
                    `handler_id`=?", $aAlert['unit'], $aAlert['action'], $iHandlerId);

            $this->query($sQuery);
        }
    }

    public function deleteData($aData)
    {
    	//--- Update Timeline Handlers ---//
        foreach($aData['handlers'] as $aHandler) {
            $sQuery = $this->prepare("DELETE FROM
                    `{$this->_sTableHandlers}`
                WHERE
                    `alert_unit`=? AND
                    `alert_action`=?
                LIMIT 1", $aHandler['alert_unit'], $aHandler['alert_action']);

            $this->query($sQuery);
        }

        //--- Update System Alerts ---//
        $iHandlerId = $this->getAlertHandlerId();
        foreach($aData['alerts'] as $aAlert) {
            $sQuery = $this->prepare("DELETE FROM
                    `sys_alerts`
                WHERE
                    `unit`=? AND
                    `action`=? AND
                    `handler_id`=?
                LIMIT 1", $aAlert['unit'], $aAlert['action'], $iHandlerId);

            $this->query($sQuery);
        }
    }

    public function deleteModuleEvents($aData)
    {
    	foreach($aData['handlers'] as $aHandler) {
    		//Delete system events.
            $this->deleteEvent(array('type' => $aHandler['alert_unit'], 'action' => $aHandler['alert_action']));

            //Delete shared events.
    		$aEvents = $this->getEvents(array('browse' => 'shared_by_descriptor', 'type' => $aHandler['alert_unit'], 'action' => $aHandler['alert_action']));
			foreach($aEvents as $aEvent) {
				$aContent = unserialize($aEvent['content']);
				if(isset($aContent['type']) && $aContent['type'] == $aHandler['alert_unit'] && isset($aContent['action']) && $aContent['action'] == $aHandler['alert_action'])
					$this->deleteEvent(array('id' => (int)$aEvent['id']));
			}
    	}
    }

	public function activateModuleEvents($aData, $bActivate = true)
    {
    	$iActivate = $bActivate ? 1 : 0;

    	foreach($aData['handlers'] as $aHandler) {
    		//Activate (deactivate) system events.
            $this->updateEvent(array('active' => $iActivate), array('type' => $aHandler['alert_unit'], 'action' => $aHandler['alert_action']));

			//Activate (deactivate) shared events.
			$aEvents = $this->getEvents(array('browse' => 'shared_by_descriptor', 'type' => $aHandler['alert_unit'], 'action' => $aHandler['alert_action']));
			foreach($aEvents as $aEvent) {
				$aContent = unserialize($aEvent['content']);
				if(isset($aContent['type']) && $aContent['type'] == $aHandler['alert_unit'] && isset($aContent['action']) && $aContent['action'] == $aHandler['alert_action'])
					$this->updateEvent(array('active' => $iActivate), array('id' => (int)$aEvent['id']));
			}
    	}
    }

    public function getHandlers($aParams = array())
    {
        $sMethod = 'getAll';
        $sWhereClause = '';

        if(!empty($aParams))
            switch($aParams['type']) {}

        $sSql = "SELECT
                `id` AS `id`,
                `type` AS `type`,
                `alert_unit` AS `alert_unit`,
                `alert_action` AS `alert_action`,
                `content` AS `content`
            FROM `{$this->_sTableHandlers}`
            WHERE 1 " . $sWhereClause;

        return $this->$sMethod($sSql);
    }

    public function insertEvent($aParamsSet)
    {
        if(empty($aParamsSet))
            return 0;

        $aSet = array();
        foreach($aParamsSet as $sKey => $sValue)
           $aSet[] = $this->prepare("`" . $sKey . "`=?", $sValue);

        if((int)$this->query("INSERT INTO `{$this->_sTable}` SET " . implode(", ", $aSet) . ", `date`=UNIX_TIMESTAMP()") <= 0)
            return 0;

        return (int)$this->lastId();
    }

    public function updateEvent($aParamsSet, $aParamsWhere)
    {
        if(empty($aParamsSet) || empty($aParamsWhere))
            return false;

        $sSql = "UPDATE `{$this->_sTable}` SET " . $this->arrayToSQL($aParamsSet) . " WHERE " . $this->arrayToSQL($aParamsWhere, " AND ");
        return $this->query($sSql);
    }

    public function deleteEvent($aParams, $sWhereAddon = "")
    {
        $sSql = "DELETE FROM `{$this->_sTable}` WHERE " . $this->arrayToSQL($aParams, " AND ") . $sWhereAddon;
        return $this->query($sSql);
    }
    
}

/** @} */
