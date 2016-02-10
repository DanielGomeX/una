<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) BoonEx Pty Limited - http://www.boonex.com/
 * CC-BY License - http://creativecommons.org/licenses/by/3.0/
 *
 * @defgroup    Payment Payment
 * @ingroup     TridentModules
 *
 * @{
 */

bx_import('BxDolForm');

class BxPaymentDetailsFormCheckerHelper extends BxDolFormCheckerHelper
{
	function checkHttps ($s)
    {
        return empty($s) || substr(BX_DOL_URL_ROOT, 0, 5) == 'https';
    }
}

class BxPaymentDetails extends BxDol
{
    protected $MODULE;
	protected $_oModule;

	protected $_sLangsPrefix;
    protected $_bCollapseFirst;

    function __construct()
    {
    	$this->MODULE = 'bx_payment';

    	parent::__construct();

    	$this->_oModule = BxDolModule::getInstance($this->MODULE);

    	$this->_sLangsPrefix = $this->_oModule->_oConfig->getPrefix('langs');
        $this->_bCollapseFirst = true;
    }

    public function serviceGetBlockDetails($iUserId = BX_PAYMENT_EMPTY_ID)
    {
        if(!$this->_oModule->isLogged())
			return MsgBox(_t($this->_sLangsPrefix . 'err_required_login'));

        $iUserId = $iUserId != BX_PAYMENT_EMPTY_ID ? $iUserId : $this->_oModule->getProfileId();

		$sContent = $this->getForm($iUserId);
		if(empty($sContent))
			$sContent = MsgBox(_t($this->_sLangsPrefix . 'msg_no_results'));

        return array(
        	'content' => $sContent,
        	'menu' => $this->_oModule->_oConfig->getObject('menu_orders_submenu')
        );
    }

    public function getForm($iUserId)
    {
        $aInputs = $this->_oModule->_oDb->getForm();
        if(empty($aInputs))
            return '';

		$aForm = array(
            'form_attrs' => array(
                'id' => 'pmt_details',
                'name' => 'pmt_details',
                'action' => BX_DOL_URL_ROOT . BxDolPermalinks::getInstance()->permalink('page.php?i=payment-details'),
                'method' => 'post',
                'enctype' => 'multipart/form-data'
            ),
            'params' => array(
                'db' => array(
                    'table' => '',
                    'key' => 'id',
                    'uri' => '',
                    'uri_title' => '',
                    'submit_name' => 'submit'
                ),
                'checker_helper' => 'BxPaymentDetailsFormCheckerHelper'
            ),
            'inputs' => array (
            )
        );

        $bCollapsed = $this->_bCollapseFirst;
        $iProviderId = 0;
        $sProviderName = "";
        $aUserValues = $this->_oModule->_oDb->getFormData($iUserId);
        foreach($aInputs as $aInput) {
            $sReturnDataUrl = $this->_oModule->_oConfig->getUrl('return_data') . $sProviderName . '/' . $iUserId;

            if($iProviderId != $aInput['provider_id']) {
                if(!empty($iProviderId))
                    $aForm['inputs']['provider_' . $iProviderId . '_end'] = array(
                        'type' => 'block_end'
                    );

                $aForm['inputs']['provider_' . $aInput['provider_id'] . '_begin'] = array(
                    'type' => 'block_header',
                    'caption' => _t($aInput['provider_caption']),
                    'collapsable' => true,
                    'collapsed' => $bCollapsed
                );

                $iProviderId = $aInput['provider_id'];
                $sProviderName = $aInput['provider_name'];
                $bCollapsed = true;
            }

            $aForm['inputs'][$aInput['name']] = array(
				'type' => $aInput['type'],
                'name' => $aInput['name'],
                'caption' => _t($aInput['caption']),
                'value' => isset($aUserValues[$aInput['id']]['value']) ? $aUserValues[$aInput['id']]['value'] : '',
                'info' => _t($aInput['description']),
            	'attrs' => array(
            		'bx-data-provider' => $iProviderId
            	),
                'checker' => array (
                    'func' => $aInput['check_type'],
                    'params' => $aInput['check_params'],
                    'error' => _t($aInput['check_error']),
                )
            );

            //--- Make some field dependent actions ---//
            switch($aInput['type']) {
                case 'select':
                    if(empty($aInput['extra']))
                       break;

                    $aAddon = array('values' => array());

                    $aPairs = explode(',', $aInput['extra']);
                    foreach($aPairs as $sPair) {
                        $aPair = explode('|', $sPair);
                        $aAddon['values'][] = array('key' => $aPair[0], 'value' => _t($aPair[1]));
                    }
                    break;

                case 'checkbox':
                    $aForm['inputs'][$aInput['name']]['value'] = 'on';
                    $aAddon = array('checked' => isset($aUserValues[$aInput['id']]['value']) && $aUserValues[$aInput['id']]['value'] == 'on' ? true : false);
                    break;

				 case 'value':
				 	if(str_replace($aInput['provider_option_prefix'], '', $aInput['name']) == 'return_url')
				 		$aForm['inputs'][$aInput['name']]['value'] = $sReturnDataUrl;
				 	break;
            }

            if(!empty($aAddon) && is_array($aAddon))
                $aForm['inputs'][$aInput['name']] = array_merge($aForm['inputs'][$aInput['name']], $aAddon);
        }

        $aForm['inputs']['provider_' . $iProviderId . '_end'] = array(
            'type' => 'block_end'
        );
        $aForm['inputs']['submit'] = array(
            'type' => 'submit',
            'name' => 'submit',
            'value' => _t($this->_sLangsPrefix . 'form_details_input_do_submit'),
        );

        bx_import('BxTemplFormView');
        $oForm = new BxTemplFormView($aForm);
        $oForm->initChecker();

        if($oForm->isSubmittedAndValid()) {
            $aOptions = $this->_oModule->_oDb->getOptions();
            foreach($aOptions as $aOption) {
            	$sValue = bx_get($aOption['name']) !== false ? bx_get($aOption['name']) : '';
                $this->_oModule->_oDb->updateOption($iUserId, $aOption['id'], bx_process_input($sValue, BX_TAGS_STRIP));
            }

            header('Location: ' . $oForm->aFormAttrs['action']);
        }
        else {
        	foreach($oForm->aInputs as $aInput)
        		if(!empty($aInput['error'])) {
        			$iProviderId = (int)$aInput['attrs']['bx-data-provider'];
        			$oForm->aInputs['provider_' . $iProviderId . '_begin']['collapsed'] = false;
        		}

			return $oForm->getCode();
        }
    }
}

/** @} */
