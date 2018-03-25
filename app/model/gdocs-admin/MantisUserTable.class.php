<?php
/**
 * MantisUserTable Active Record
 * @author  <your-name-here>
 */
class MantisUserTable extends TRecord
{
    const TABLENAME = 'gdoc.mantis_user_table';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'serial'; // {max, serial}
    
    
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('username');
        parent::addAttribute('realname');
        parent::addAttribute('email');
        parent::addAttribute('password');
        parent::addAttribute('enabled');
        parent::addAttribute('protected');
        parent::addAttribute('access_level');
        parent::addAttribute('login_count');
        parent::addAttribute('lost_password_request_count');
        parent::addAttribute('failed_login_count');
        parent::addAttribute('cookie_string');
        parent::addAttribute('last_visit');
        parent::addAttribute('date_created');
    }


}
