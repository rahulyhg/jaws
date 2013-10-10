<?php
/**
 * Users Core Gadget
 *
 * @category    Gadget
 * @package     Users
 * @author      Mojtaba Ebrahimi <ebrahimi@zehneziba.ir>
 * @copyright   2013 Jaws Development Group
 * @license     http://www.gnu.org/copyleft/lesser.html
 */
class Users_Actions_Groups extends Users_HTML
{
    /**
     * Prepares a form for manage user's groups
     *
     * @access  public
     * @return  string  XHTML template of a form
     */
    function Groups()
    {
        if (!$GLOBALS['app']->Session->Logged()) {
            Jaws_Header::Location(
                $this->gadget->urlMap(
                    'LoginBox',
                    array('referrer'  => bin2hex(Jaws_Utils::getRequestURL(true)))
                )
            );
        }

        $this->gadget->CheckPermission('ManageUserGroups');
        $this->AjaxMe('index.js');
        $response = $GLOBALS['app']->Session->PopResponse('Users.Groups');
        $user = $GLOBALS['app']->Session->GetAttribute('user');
        require_once JAWS_PATH . 'include/Jaws/User.php';
        $jUser = new Jaws_User;
        $groups = $jUser->GetGroups($user);

        // Load the template
        $tpl = $this->gadget->loadTemplate('Groups.html');
        $tpl->SetBlock('groups');

        if (!empty($response)) {
            $tpl->SetVariable('type', $response['type']);
            $tpl->SetVariable('text', $response['text']);
        }

        $tpl->SetVariable('title', _t('USERS_GROUPS'));
        $tpl->SetVariable('menubar', $this->MenuBar('AddGroup'));
        $tpl->SetVariable('base_script', BASE_SCRIPT);

        $tpl->SetVariable('lbl_name', _t('GLOBAL_NAME'));
        $tpl->SetVariable('lbl_title', _t('GLOBAL_TITLE'));
        $tpl->SetVariable('lbl_enabled', _t('GLOBAL_ENABLED'));

        foreach ($groups as $group) {
            $tpl->SetBlock('groups/group');
            $tpl->SetVariable('id', $group['id']);
            $tpl->SetVariable('url', $this->gadget->urlMap('ManageGroup', array('id' => $group['id'])));
            $tpl->SetVariable('name', $group['name']);
            $tpl->SetVariable('title', $group['title']);
            $enabled = ($group['enabled'] == true) ? _t('GLOBAL_YES') : _t('GLOBAL_NO');
            $tpl->SetVariable('enabled', $enabled);
            $tpl->ParseBlock('groups/group');
        }

        $tpl->SetVariable('lbl_actions', _t('GLOBAL_ACTIONS'));
        $tpl->SetVariable('lbl_no_action', _t('GLOBAL_NO_ACTION'));

        $tpl->SetVariable('lbl_delete', _t('GLOBAL_DELETE'));
        $tpl->SetVariable('lbl_disable', _t('GLOBAL_DISABLE'));
        $tpl->SetVariable('lbl_enable', _t('GLOBAL_ENABLE'));
        $tpl->SetVariable('icon_filter', STOCK_SEARCH);
        $tpl->SetVariable('icon_ok', STOCK_OK);

        $tpl->ParseBlock('groups');
        return $tpl->Get();
    }

    /**
     * Add an new group UI
     *
     * @access  public
     * @return  string  XHTML template of a form
     */
    function AddGroupUI()
    {
        if (!$GLOBALS['app']->Session->Logged()) {
            Jaws_Header::Location(
                $this->gadget->urlMap(
                    'LoginBox',
                    array('referrer'  => bin2hex(Jaws_Utils::getRequestURL(true)))
                )
            );
        }

        $this->gadget->CheckPermission('ManageUserGroups');
        $this->AjaxMe('index.js');

        // Load the template
        $tpl = $this->gadget->loadTemplate('Groups.html');
        $tpl->SetBlock('add_group');

        $tpl->SetVariable('title', _t('USERS_ADD_GROUP'));
        $tpl->SetVariable('menubar', $this->MenuBar('AddGroup'));
        $tpl->SetVariable('base_script', BASE_SCRIPT);

        $tpl->SetVariable('lbl_name', _t('GLOBAL_NAME'));
        $tpl->SetVariable('lbl_title', _t('GLOBAL_TITLE'));
        $tpl->SetVariable('lbl_description', _t('GLOBAL_DESCRIPTION'));
        $tpl->SetVariable('lbl_enabled', _t('GLOBAL_ENABLED'));
        $tpl->SetVariable('lbl_yes', _t('GLOBAL_YES'));
        $tpl->SetVariable('lbl_no', _t('GLOBAL_NO'));
        $tpl->SetVariable('save', _t('GLOBAL_SAVE'));

        $tpl->ParseBlock('add_group');
        return $tpl->Get();
    }

    /**
     * Add an new group
     *
     * @access  public
     * @return  void
     */
    function AddGroup()
    {
        $this->gadget->CheckPermission('ManageUserGroups');

        $post = jaws()->request->fetch(array('name', 'title', 'description', 'enabled'), 'post');
        $user = $GLOBALS['app']->Session->GetAttribute('user');
        $post['enabled'] = (bool) $post['enabled'];

        require_once JAWS_PATH . 'include/Jaws/User.php';
        $jUser = new Jaws_User;
        $res = $jUser->AddGroup($post, $user);

        if (Jaws_Error::isError($res)) {
            $GLOBALS['app']->Session->PushResponse(
                $res->getMessage(),
                'Users.Groups',
                RESPONSE_ERROR
            );
        } elseif ($res == true) {
            $GLOBALS['app']->Session->PushResponse(
                _t('USERS_GROUPS_CREATED', $post['title']),
                'Users.Groups',
                RESPONSE_NOTICE
            );
        }
        Jaws_Header::Location($this->gadget->urlMap('Groups'));
    }

    /**
     * Delete user's group(s)
     *
     * @access  public
     * @return  void
     */
    function DeleteGroups()
    {
        $this->gadget->CheckPermission('ManageUserGroups');

        $ids = jaws()->request->fetch('group_checkbox:array', 'post');
        $user = $GLOBALS['app']->Session->GetAttribute('user');

        require_once JAWS_PATH . 'include/Jaws/User.php';
        $jUser = new Jaws_User;
        foreach($ids as $id) {
            // TODO: improve performance
            $res= $jUser->DeleteGroup($id, $user);
        }

        if ($res == true) {
            $GLOBALS['app']->Session->PushResponse(
                _t('USERS_GROUP_DELETED'),
                'Users.Groups',
                RESPONSE_NOTICE
            );
        } else {
            $GLOBALS['app']->Session->PushResponse(
                _t('USERS_GROUPS_CANT_DELETE'),
                'Users.Groups',
                RESPONSE_ERROR
            );
        }
        Jaws_Header::Location($this->gadget->urlMap('Groups'));
    }

    /**
     * Enable or Disable user's group(s)
     *
     * @access  public
     * @return  void
     */
    function EnableGroups()
    {
        $this->gadget->CheckPermission('ManageUserGroups');

        $post = jaws()->request->fetch(array('group_checkbox:array', 'status'), 'post');
        $ids = $post['group_checkbox'];
        $status = $post['status'];
        $enabled = false;
        if ($status == 'yes') {
            $enabled = true;
        }

        $user = $GLOBALS['app']->Session->GetAttribute('user');

        require_once JAWS_PATH . 'include/Jaws/User.php';
        $jUser = new Jaws_User;
        foreach ($ids as $id) {
            // TODO: improve performance
            $res = $jUser->UpdateGroup($id, array('enabled' => $enabled), $user);
        }

        if ($res == true) {
            $GLOBALS['app']->Session->PushResponse(
                _t('USERS_GROUP_STATUS_CHANGED'),
                'Users.Groups',
                RESPONSE_NOTICE
            );
        } else {
            $GLOBALS['app']->Session->PushResponse(
                _t('USERS_CANT_CHANGE_GROUPS_STATUS'),
                'Users.Groups',
                RESPONSE_ERROR
            );
        }
        Jaws_Header::Location($this->gadget->urlMap('Groups'));
    }

    /**
     * Manage group
     *
     * @access  public
     * @return  string  XHTML template of a form
     */
    function ManageGroup()
    {
        if (!$GLOBALS['app']->Session->Logged()) {
            Jaws_Header::Location(
                $this->gadget->urlMap(
                    'LoginBox',
                    array('referrer'  => bin2hex(Jaws_Utils::getRequestURL(true)))
                )
            );
        }

        $this->gadget->CheckPermission('ManageUserGroups');
        $this->AjaxMe('index.js');

        $gid = (int) jaws()->request->fetch('id', 'get');
        $user = $GLOBALS['app']->Session->GetAttribute('user');

        // Load the template
        $tpl = $this->gadget->loadTemplate('Groups.html');
        $tpl->SetBlock('manage_group');

        $tpl->SetVariable('title', _t('USERS_MANAGE_GROUPS'));
        $tpl->SetVariable('menubar', $this->MenuBar('Groups'));
        $tpl->SetVariable('base_script', BASE_SCRIPT);
        $tpl->SetVariable('gid', $gid);

        $tpl->SetVariable('lbl_name', _t('GLOBAL_NAME'));
        $tpl->SetVariable('lbl_title', _t('GLOBAL_TITLE'));
        $tpl->SetVariable('lbl_description', _t('GLOBAL_DESCRIPTION'));
        $tpl->SetVariable('lbl_enabled', _t('GLOBAL_ENABLED'));
        $tpl->SetVariable('lbl_yes', _t('GLOBAL_YES'));
        $tpl->SetVariable('lbl_no', _t('GLOBAL_NO'));
        $tpl->SetVariable('update', _t('GLOBAL_UPDATE'));

        require_once JAWS_PATH . 'include/Jaws/User.php';
        $jUser = new Jaws_User;
        $group = $jUser->GetGroup($gid, $user);

        $tpl->SetVariable('name', $group['name']);
        $tpl->SetVariable('title', $group['title']);
        $tpl->SetVariable('description', $group['description']);
        $tpl->SetVariable('selected_enabled_' . (int) $group['enabled'], 'selected');

        $allUsers = $jUser->GetUsers();
        $tpl->SetVariable('lbl_group_member', _t('USERS_MANAGE_GROUPS_MEMBERS'));
        $tpl->SetVariable('lbl_users', _t('USERS_USERS'));
        $tpl->SetVariable('lbl_add_user_to_group', _t('USERS_GROUPS_ADD_USER'));
        foreach($allUsers as $user) {
            $tpl->SetBlock('manage_group/user');
            $tpl->SetVariable('user', $user['id']);
            $tpl->SetVariable('username', $user['username']);
            $tpl->SetVariable('nickname', $user['nickname']);
            $tpl->ParseBlock('manage_group/user');
        }

        $members = $jUser->GetUsers($gid);
        $tpl->SetVariable('lbl_members', _t('USERS_GROUPS_MEMBERS'));
        $tpl->SetVariable('lbl_remove_user_from_member', _t('USERS_GROUPS_REMOVE_USER'));
        foreach($members as $user) {
            $tpl->SetBlock('manage_group/member');
            $tpl->SetVariable('user', $user['id']);
            $tpl->SetVariable('username', $user['username']);
            $tpl->SetVariable('nickname', $user['nickname']);
            $tpl->ParseBlock('manage_group/member');
        }

        $tpl->ParseBlock('manage_group');
        return $tpl->Get();
    }


    /**
     * Update a user's group
     *
     * @access  public
     * @return  void
     */
    function UpdateGroup()
    {
        $this->gadget->CheckPermission('ManageUserGroups');

        $post = jaws()->request->fetch(array('gid', 'name', 'title', 'description', 'enabled'), 'post');
        $selected_members = jaws()->request->fetch('members:array', 'post');
        $user = $GLOBALS['app']->Session->GetAttribute('user');
        $post['enabled'] = (bool) $post['enabled'];

        require_once JAWS_PATH . 'include/Jaws/User.php';
        $jUser = new Jaws_User;
        $res = $jUser->UpdateGroup($post['gid'], $post, $user);

        $current_members_info = $jUser->GetUsers($post['gid']);
        $current_members = array();
        foreach($current_members_info as $member_info) {
            $current_members[] = $member_info['id'];
        }
        $new_member = array_diff($selected_members, $current_members);
        if (!Jaws_Error::isError($res) && count($new_member) > 0) {
            // TODO: improve performance
            foreach ($new_member as $member) {
                $res = $jUser->AddUserToGroup($member, $post['gid'], $user);
            }
        }

        $removed_member = array_diff($current_members, $selected_members);
        if (!Jaws_Error::isError($res) && count($removed_member) > 0) {
            // TODO: improve performance
            foreach ($removed_member as $member) {
                $res = $jUser->DeleteUserFromGroup($member, $post['gid'], $user);
            }
        }

        if (Jaws_Error::isError($res)) {
            $GLOBALS['app']->Session->PushResponse(
                $res->getMessage(),
                'Users.Groups',
                RESPONSE_ERROR
            );
        } elseif ($res == true) {
            $GLOBALS['app']->Session->PushResponse(
                _t('USERS_GROUPS_UPDATED', $post['title']),
                'Users.Groups',
                RESPONSE_NOTICE
            );
        }
        Jaws_Header::Location($this->gadget->urlMap('Groups'));
    }

}