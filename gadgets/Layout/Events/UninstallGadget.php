<?php
/**
 * Layout UninstallGadget event
 *
 * @category   Gadget
 * @package    Layout
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2013 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
class Layout_Events_UninstallGadget extends Jaws_Gadget
{
    /**
     * Event execute method
     *
     */
    function Execute($gadget)
    {
        $lModel = $GLOBALS['app']->loadGadget('Layout', 'AdminModel');
        $res = $lModel->DeleteGadgetElements($gadget);
        return $res;
    }

}