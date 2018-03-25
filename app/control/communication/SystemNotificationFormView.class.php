<?php
/**
 * SystemNotificationFormView Form
 * @author  <your name here>
 */
class SystemNotificationFormView extends TPage
{
    /**
     * Show data
     */
    public function onView( $param )
    {
        try
        {
            // convert parameter to object
            $data = (object) $param;
            
            // load the html template
            $html = new THtmlRenderer('app/resources/systemnotificationview.html');
            $html->enableTranslation(TRUE);
            
            // load CSS styles
            parent::include_css('app/resources/styles.css');

            TTransaction::open('communication');
            if (isset($data->id))
            {
                // load customer identified in the form
                $sql = "(SELECT system_notification.id FROM g_system.system_user_group," .
                       " g_system.system_group, g_system.system_user, g_message.system_notification " .
                       "WHERE system_user_group.system_user_id = system_user.id AND " .
                       "system_group.id = system_user_group.system_group_id AND " .
                       "system_group.acess > system_notification.acess_id AND " .
                       "system_group.system_id = system_notification.system_id AND " .
                       "system_user.id = " . TSession::getValue('userid') ." AND system_notification.checked = 'N' AND " .
                       "system_notification.id = " . $data->id . ")";
                //var_dump( $sql);
                $retorno = SystemNotification::where('id','IN',$sql)->
                                                        orderBy('id', 'desc')->load();
                $object = SystemNotification::find( $data->id );
                
                if ($object)
                {
                    if ($object->system_user_to_id == TSession::getValue('userid') || !empty($retorno))
                    {
                        // create one array with the customer data
                        $array_object = $object->toArray();
                        $array_object['checked_string'] = ($array_object['checked'] == 'Y' ? _t('Yes') : _t('No'));
                        $array_object['action_encoded'] = base64_encode($array_object['action_url']);
                        
                        TTransaction::open('permission');
                        $user = SystemUser::find($array_object['system_user_id']);
                        if ($user instanceof SystemUser)
                        {
                            $array_object['user'] = $user->name . ' (' . $array_object['system_user_id'] . ')';
                        }
                        TTransaction::close();
                        
                        // replace variables from the main section with the object data
                        $html->enableSection('main',  $array_object);
                        
                        if ($object->checked == 'N')
                        {
                            $html->enableSection('check', $array_object);
                        }
                    }
                    else
                    {
                        throw new Exception(_t('Permission denied') . $sql);
                    }
                }
                else
                {
                    throw new Exception(_t('Object ^1 not found in ^2', $data->id, 'SystemNotification'));
                }
            }
            
            TTransaction::close();
            
            parent::add($html);
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
        }
    }
    
    /**
     * Check message as read
     */
    public function onExecuteAction($param)
    {
        try
        {
            TTransaction::open('communication');
            
            $notification = SystemNotification::find($param['id']);
            $sql = "(SELECT system_notification.id FROM g_system.system_user_group," .
                   " g_system.system_group, g_system.system_user, g_message.system_notification " .
                   "WHERE system_user_group.system_user_id = system_user.id AND " .
                   "system_group.id = system_user_group.system_group_id AND " .
                   "system_group.acess > system_notification.acess_id AND " .
                   "system_group.system_id = system_notification.system_id AND " .
                   "system_user.id = " . TSession::getValue('userid') ." AND system_notification.checked = 'N' AND " .
                   "system_notification.id = " . $param['id'] . ")";
            //var_dump( $sql);
            $retorno = SystemNotification::where('id','IN',$sql)->
                                                    orderBy('id', 'desc')->load();
            if ($notification || !empty($retorno))
            {
                if ($notification->system_user_to_id == TSession::getValue('userid') || !empty($retorno))
                {
                    $notification->checked = 'Y';
                    $notification->store();
            
                    $query_string = $notification->action_url;
                    parse_str($query_string, $query_params);
                    $class  = $query_params['class'];
                    $method = isset($query_params['method']) ? $query_params['method'] : null;
                    unset($query_params['class']);
                    unset($query_params['method']);
                    AdiantiCoreApplication::loadPage( $class, $method, $query_params);
                    TScript::create('update_notifications_menu()');
                }
                else
                {
                    throw new Exception(_t('Permission denied'));
                }
            }
            TTransaction::close();
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
        }
    }
}
