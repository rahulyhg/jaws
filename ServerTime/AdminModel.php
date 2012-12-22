<?php
/**
 * ServerTime Gadget
 *
 * @category   GadgetModel
 * @package    ServerTime
 * @author     Jonathan Hernandez <ion@suavizado.com>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2004-2012 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
class ServerTimeAdminModel extends Jaws_Gadget_Model
{
    /**
     * Updates the properties of ServerTime
     *
     * @access  public
     * @param   string  $format    The format of date and time being displayed
     * @return  mixed   True on success and Jaws_Error on failure
     */
    function UpdateProperties($format)
    {
        $res = $this->gadget->SetRegistry('date_format', $format);
        if ($res) {
            $GLOBALS['app']->Session->PushLastResponse(_t('SERVERTIME_PROPERTIES_UPDATED'), RESPONSE_NOTICE);
            return true;
        }

        $GLOBALS['app']->Session->PushLastResponse(_t('SERVERTIME_ERROR_PROPERTIES_NOT_UPDATED'), RESPONSE_ERROR);
        return new Jaws_Error(_t('SERVERTIME_ERROR_PROPERTIES_NOT_UPDATED'), _t('SERVERTIME_NAME'));
    }

}