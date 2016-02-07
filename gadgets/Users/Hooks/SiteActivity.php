<?php
/**
 * Users - SiteActivity hook
 *
 * @category    GadgetHook
 * @package     Users
 * @author      Mojtaba Ebrahimi <ebrahimi@zehneziba.ir>
 * @copyright   2008-2016 Jaws Development Group
 * @license     http://www.gnu.org/copyleft/lesser.html
 */
class Users_Hooks_SiteActivity extends Jaws_Gadget_Hook
{
    /**
     * Defines translate statements of Site activity
     *
     * @access  public
     * @return  void
     */
    function Execute()
    {
        $items = array();
        $items['AddUser'] = _t('USERS_SITEACTIVITY_ACTION_ADDUSER');

        return $items;
    }

}