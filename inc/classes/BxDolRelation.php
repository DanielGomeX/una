<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    UnaCore UNA Core
 * @{
 */

class BxDolRelation extends BxDolConnection
{
    protected function __construct($aObject)
    {
        parent::__construct($aObject);

        $this->_oQuery = new BxDolRelationQuery($aObject);
    }

    /**
     * Add new relation.
     * @param $mixedContent content to make relation with or an array with content and relation type
     * @return array
     */
    public function actionAdd($mixedContent = 0, $iInitiator = false)
    {
        if(empty($mixedContent))
            $mixedContent = bx_process_input($_POST['id'], BX_DATA_INT);

        $iContent = 0;
        $iRelation = 0;
        if(is_array($mixedContent)) {
            $iContent = (int)$mixedContent['content'];
            $iRelation = (int)$mixedContent['relation'];
        }
        else
            $iContent = (int)$mixedContent;

        $iInitiator = $iInitiator ? (int)$iInitiator : (int)bx_get_logged_profile_id();

        $aResult = parent::actionAdd($iContent, $iInitiator);
        if(empty($iRelation) || (isset($aResult['err']) && $aResult['err'] !== false))
            return $aResult;

        $this->_oQuery->updateConnection($iInitiator, $iContent, array(
            'relation' => $iRelation
        ));

        return $aResult;
    }
    
    /**
     * Confirm relation request without creation of retroactive relation.
     * @param $iContent content to make relation with
     * @return array
     */
    public function actionConfirm($iContent = 0, $iInitiator = false)
    {
        if(!$iContent)
            $iContent = bx_process_input($_POST['id'], BX_DATA_INT);

        return $this->_action($iContent, $iInitiator ? $iInitiator : bx_get_logged_profile_id(), 'confirmConnection', '_sys_conn_err_connection_does_not_exists');
    }

    /**
     * Remove relation without removing a retroactive relation. This method is wrapper for @see removeConnection to be called from @see conn.php upon AJAX request to this file.
     * @param $iContent content to make connection to, in most cases some content id, or other profile id in case of friends
     * @return array
     */
    public function actionRemove($iContent = 0, $iInitiator = false)
    {
        if(!$iContent)
            $iContent = bx_process_input($_POST['id'], BX_DATA_INT);

        return $this->_action($iInitiator ? $iInitiator : bx_get_logged_profile_id(), $iContent, 'removeConnection', '_sys_conn_err_connection_does_not_exists');
    }

    /**
     * Confirm relation request without creation of retroactive relation.
     * @param $iInitiator initiator of the connection, in most cases some profile id
     * @param $iContent content to make connection to, in most cases some content id, or other profile id in case of friends
     * @return true - if connection was added, false - if connection already exists or error occured
     */
    public function confirmConnection($iInitiator, $iContent)
    {
        $iMutual = 1;
        if(!$this->_oQuery->updateConnectionMutual((int)$iInitiator, (int)$iContent, $iMutual))
            return false;

        bx_alert($this->_sObject, 'connection_confirmed', 0, bx_get_logged_profile_id(), array(
            'initiator' => (int)$iInitiator,
            'content' => (int)$iContent,
            'mutual' => (int)$iMutual,
            'object' => $this,
        ));

        return true;
    }

    /**
     * Compound function, which calls getCommonContentExt, getConnectedContentExt or getConnectedInitiatorsExt depending on $sContentType
     * @param $sContentType content type to get BX_CONNECTIONS_CONTENT_TYPE_CONTENT, BX_CONNECTIONS_CONTENT_TYPE_INITIATORS or BX_CONNECTIONS_CONTENT_TYPE_COMMON
     * @param $iId1 one content or initiator
     * @param $iId2 second content or initiator only in case of BX_CONNECTIONS_CONTENT_TYPE_COMMON content type
     * @param $isMutual get mutual connections only
     * @return array of available connections
     */
    public function getConnectionsAsArrayExt($sContentType, $iId1, $iId2, $isMutual = false, $iStart = 0, $iLimit = BX_CONNECTIONS_LIST_LIMIT, $iOrder = BX_CONNECTIONS_ORDER_NONE)
    {
        if (BX_CONNECTIONS_CONTENT_TYPE_COMMON == $sContentType)
            return $this->getCommonContentExt($iId1, $iId2, $isMutual, $iStart, $iLimit, $iOrder);

        if (BX_CONNECTIONS_CONTENT_TYPE_INITIATORS == $sContentType)
            $sMethod = 'getConnectedInitiatorsExt';
        else
            $sMethod = 'getConnectedContentExt';

        return $this->$sMethod($iId1, $isMutual, $iStart, $iLimit, $iOrder);
    }

    /**
     * Get common content (full info) between two initiators
     * @param $iInitiator1 one initiator
     * @param $iInitiator2 second initiator
     * @param $isMutual get mutual connections only
     * @return array of available connections
     */
    public function getCommonContentExt($iInitiator1, $iInitiator2, $isMutual = false, $iStart = 0, $iLimit = BX_CONNECTIONS_LIST_LIMIT, $iOrder = BX_CONNECTIONS_ORDER_NONE)
    {
        return $this->_oQuery->getCommonContentExt($iInitiator1, $iInitiator2, $isMutual, $iStart, $iLimit, $iOrder);
    }

    /**
     * Get connected initiators (full info)
     * @param $iContent content of the connection
     * @param $isMutual get mutual connections only
     * @return array of available connections
     */
    public function getConnectedInitiatorsExt($iContent, $isMutual = false, $iStart = 0, $iLimit = BX_CONNECTIONS_LIST_LIMIT, $iOrder = BX_CONNECTIONS_ORDER_NONE)
    {
        return $this->_oQuery->getConnectedInitiatorsExt($iContent, $isMutual, $iStart, $iLimit, $iOrder);
    }

    /**
     * Get connected content (full info)
     * @param $iInitiator initiator of the connection
     * @param $isMutual get mutual connections only
     * @return array of available connections
     */
    public function getConnectedContentExt($iInitiator, $isMutual = false, $iStart = 0, $iLimit = BX_CONNECTIONS_LIST_LIMIT, $iOrder = BX_CONNECTIONS_ORDER_NONE)
    {
        return $this->_oQuery->getConnectedContentExt($iInitiator, $isMutual, $iStart, $iLimit, $iOrder);
    }

    /**
     * Check whether connection between Initiator and Content can be established.
     */
    public function checkAllowedConnect($iInitiator, $iContent, $isPerformAction = false, $isMutual = false, $isInvertResult = false, $isSwap = false)
    {
        if(!BxDolConnection::getObjectInstance('sys_profiles_friends')->isConnected($iInitiator, $iContent, true))
            return _t('_sys_txt_access_denied');

        return parent::checkAllowedConnect($iInitiator, $iContent, $isPerformAction, $isMutual, $isInvertResult, $isSwap);
    }

    public function getRelation($iInitiator, $iContent)
    {
        $aConnection = $this->_oQuery->getConnection ($iInitiator, $iContent);
        if(empty($aConnection) || !is_array($aConnection))
            return 0;

        return (int)$aConnection['relation'];
    }
}