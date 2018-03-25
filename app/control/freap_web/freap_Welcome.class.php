<?php
/**
 * WelcomeView
 *
 * @version    1.0
 * @package    samples
 * @subpackage tutor
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006-2012 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class freap_Welcome extends TPage
{
    private $html;
    
    /**
     * Class constructor
     * Creates the page
     */
    function __construct()
    {
        parent::__construct();
       
        TPage::include_css('app/resources/styles.css');

        $panel = new TPanelGroup('<h4 style = "color: red;"><center>BEM VINDO AO SITE DO FUNDO DE '  .
                                 'REAPARELHAMENTO DA PMGO</center></h4>');
        
        $image = new TImage('app/templates/theme1/images/bem-vindo.jpg');
        $panel->add('<center>' . $image . '</center>');        
        // add the template to the page
        parent::add($panel);
    }
}

