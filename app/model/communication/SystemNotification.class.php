<?php
/**
 * SystemNotification Active Record
 * @author  <your-name-here>
 */
class SystemNotification extends TRecord
{
    const TABLENAME = 'g_message.system_notification';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max'; // {max, serial}
    
    
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('system_user_id');
        parent::addAttribute('system_user_to_id');
        parent::addAttribute('subject');
        parent::addAttribute('message');
        parent::addAttribute('dt_message');
        parent::addAttribute('action_url');
        parent::addAttribute('action_label');
        parent::addAttribute('icon');
        parent::addAttribute('checked');
        parent::addAttribute('system_id');        //2017-10-01
        parent::addAttribute('acess_id');         //2017-10-01
    }

    /**
     * Register notification
     */
    public static function register( $user_to, $subject, $message, $action, $label, $icon = null,$system_id = null,$acess_id = null)
    {
        TTransaction::open('communication');
        $object = new self;
        $object->system_user_id    = TSession::getValue('userid');
        $object->system_user_to_id = $user_to;
        $object->subject           = $subject;
        $object->message           = $message;
        $object->dt_message        = date("Y-m-d H:i:s");
        $object->action_url        = $action;
        $object->action_label      = $label;
        $object->icon              = $icon;
        $object->checked           = 'N';
        $object->system_id         = $system_id;
        $object->acess_id          = $acess_id;
        $object->store();
        TTransaction::close();
    }
}
