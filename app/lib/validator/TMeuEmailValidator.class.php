<?php
/**
 * Email validation
 *
 * @version    2.0
 * @package    validator
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006-2014 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TMeuEmailValidator extends TFieldValidator {
    /**
     * Validate a given value
     * @param $label Identifies the value to be validated in case of exception
     * @param $value Value to be validated
     * @param $parameters aditional parameters for validation
     */
    public function validate($label, $value, $parameters = NULL) {
    	if (!ereg('^([a-zA-Z0-9.-_])*([@])([a-z0-9]).([a-z]{2,3})',$value)){
    		throw new Exception(AdiantiCoreTranslator::translate('The field ^1 contains an invalid e-mail', $label));
        }
        else{
    		//Valida o dominio
    		$dominio=explode('@',$value);
    		if(!checkdnsrr($dominio[1],'A')){
    			throw new Exception(AdiantiCoreTranslator::translate('The field ^1 contains an invalid e-mail', $label));
    	    }
    	}


    }
}

/*if (!preg_match("/^(\w+((-\w+)|(\w.\w+))*)\@(\w+((\.|-)\w+)*\.\w+$)/",trim($value)))
        {
            throw new Exception(AdiantiCoreTranslator::translate('The field ^1 contains an invalid e-mail', $label));
        }*/
