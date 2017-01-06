<?php
/**
 * Menu AJAX API
 *
 * @category    Ajax
 * @package     Menu
 */
class Menu_Actions_Admin_Ajax extends Jaws_Gadget_Action
{
    /**
     * Get all menus and groups data
     *
     * @access  public
     * @return  mixed   Data array or False on error
     */
    function GetMenusTrees()
    {
        $gadget = $this->gadget->action->loadAdmin('Menu');
        $data = $gadget->GetMenusTrees();
        unset($gadget);
        if (Jaws_Error::IsError($data)) {
            return false;
        }
        return $data;
    }

    /**
     * Returns the group form
     *
     * @access  public
     * @return  string  XHTML template of groupForm
     */
    function GetGroupUI()
    {
        $gadget = $this->gadget->action->loadAdmin('Menu');
        return $gadget->GetGroupUI();
    }

    /**
     * Returns the menu form
     *
     * @access  public
     * @return  string  XHTML template of groupForm
     */
    function GetMenuUI()
    {
        $gadget = $this->gadget->action->loadAdmin('Menu');
        return $gadget->GetMenuUI();
    }

    /**
     * Get information of a group
     *
     * @access  public
     * @return  mixed   Group information array or False on error
     */
    function GetGroups()
    {
        @list($gid) = jaws()->request->fetchAll('post');
        $model = $this->gadget->model->load('Group');
        $groupInfo = $model->GetGroups($gid);
        if (Jaws_Error::IsError($groupInfo)) {
            return false; //we need to handle errors on ajax
        }

        return $groupInfo;
    }

    /**
     * Get menu data
     *
     * @access  public
     * @return  mixed   Menu data array or False on error
     */
    function GetMenu()
    {
        @list($mid) = jaws()->request->fetchAll('post');
        $model = $this->gadget->model->load('Menu');
        $menu = $model->GetMenu($mid);
        if (Jaws_Error::IsError($menu)) {
            return false; //we need to handle errors on ajax
        }

        if (!$menu['variables']) {
            $menu['url'] = rawurldecode($menu['url']);
        }

        return $menu;
    }

    /**
     * Insert group
     *
     * @access  public
     * @return  array   Response array (notice or error)
     */
    function InsertGroup()
    {
        $this->gadget->CheckPermission('ManageGroups');
        @list($title, $title_view, $published) = jaws()->request->fetchAll('post');
        $model = $this->gadget->model->loadAdmin('Group');
        $model->InsertGroup($title, $title_view, (bool)$published);

        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Insert menu
     *
     * @access  public
     * @return  array   Response array (notice or error)
     */
    function InsertMenu()
    {
        $this->gadget->CheckPermission('ManageMenus');
        @list($pid, $gid, $type, $acl, $title, $url, $variables, $url_target,
            $order, $status, $image
        ) = jaws()->request->fetchAll('post');

        if (is_null($url)) {
            $url = serialize(jaws()->request->fetch('5:array', 'post'));
        } else {
            $url = implode('/', array_map('rawurlencode', explode('/', $url)));
            // prevent encode comma
            $url = str_replace('%2C', ',', $url);
        }

        if (is_null($variables)) {
            $variables = serialize(jaws()->request->fetch('6:array', 'post'));
        }

        $mData = array(
            'pid'        => $pid,
            'gid'        => $gid,
            'type'       => $type,
            'acl'        => $acl,
            'title'      => $title,
            'url'        => $url,
            'variables'  => $variables,
            'url_target' => $url_target,
            'order'      => $order, 
            'status'     => (int)$status,
            'image'      => $image
        );
        $model = $this->gadget->model->loadAdmin('Menu');
        $model->InsertMenu($mData);

        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Update group
     *
     * @access  public
     * @return  array   Response array (notice or error)
     */
    function UpdateGroup()
    {
        $this->gadget->CheckPermission('ManageGroups');
        @list($gid, $title, $title_view, $published) = jaws()->request->fetchAll('post');
        $model = $this->gadget->model->loadAdmin('Group');
        $model->UpdateGroup($gid, $title, $title_view, (bool)$published);

        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Update menu
     *
     * @access  public
     * @return  array   Response array (notice or error)
     */
    function UpdateMenu()
    {
        $this->gadget->CheckPermission('ManageMenus');
        @list($mid, $pid, $gid, $type, $acl, $title, $url, $variables, $url_target,
            $order, $status, $image
        ) = jaws()->request->fetchAll('post');

        if (is_null($url)) {
            $url = serialize(jaws()->request->fetch('6:array', 'post'));
        } else {
            $url = implode('/', array_map('rawurlencode', explode('/', $url)));
            // prevent encode comma
            $url = str_replace('%2C', ',', $url);
        }

        if (is_null($variables)) {
            $variables = serialize(jaws()->request->fetch('7:array', 'post'));
        }

        $mData = array(
            'pid'        => $pid,
            'gid'        => $gid,
            'type'       => $type,
            'acl'        => $acl,
            'title'      => $title,
            'url'        => $url,
            'variables'  => $variables,
            'url_target' => $url_target,
            'order'      => $order, 
            'status'     => (int)$status,
            'image'      => $image
        );
        $model = $this->gadget->model->loadAdmin('Menu');
        $model->UpdateMenu($mid, $mData);

        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Delete an group
     *
     * @access  public
     * @return  array   Response array (notice or error)
     */
    function DeleteGroup()
    {
        $this->gadget->CheckPermission('ManageGroups');
        @list($gid) = jaws()->request->fetchAll('post');
        $model = $this->gadget->model->loadAdmin('Group');
        $model->DeleteGroup($gid);

        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Delete an menu
     *
     * @access  public
     * @return  array   Response array (notice or error)
     */
    function DeleteMenu()
    {
        $this->gadget->CheckPermission('ManageMenus');
        @list($mid) = jaws()->request->fetchAll('post');
        $model = $this->gadget->model->loadAdmin('Menu');
        $result = $model->DeleteMenu($mid);
        if ($result) {
            $GLOBALS['app']->Session->PushLastResponse(_t('MENU_NOTICE_MENU_DELETED'), RESPONSE_NOTICE);
        }

        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Get menu data
     *
     * @access  public
     * @return  array   Menu data array
     */
    function GetParentMenus()
    {
        @list($gid, $mid) = jaws()->request->fetchAll('post');
        $result[] = array('pid'=> 0,
                          'title'=>'\\');
        $model = $this->gadget->model->loadAdmin('Menu');
        $model->GetParentMenus(0, $gid, $mid, $result);

        return $result;
    }

    /**
     * function for change gid, pid and order of menus
     *
     * @access  public
     * @return  array   Response array (notice or error)
     */
    function MoveMenu()
    {
        $this->gadget->CheckPermission('ManageMenus');
        @list($mid, $new_gid, $old_gid, $new_pid, $old_pid,
            $new_order, $old_order
        ) = jaws()->request->fetchAll('post');
        $model = $this->gadget->model->loadAdmin('Menu');
        $model->MoveMenu($mid, $new_gid, $old_gid, $new_pid, $old_pid, $new_order, $old_order);

        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Get a list of URLs of a gadget
     *
     * @access  public
     * @return  array   URLs array on success or empty array on failure
     */
    function GetPublicURList()
    {
        @list($request) = jaws()->request->fetchAll('post');
        if ($request == 'url') {
            $urls[] = array('url'   => '/',
                            'title' => _t('MENU_REFERENCES_FREE_LINK'));
            $urls[] = array('url'   => '',
                            'title' => _t('MENU_REFERENCES_NO_LINK'));
            return $urls;
        } else {
            if (Jaws_Gadget::IsGadgetUpdated($request)) {
                $objGadget = Jaws_Gadget::getInstance($request);
                if (!Jaws_Error::IsError($objGadget)) {
                    $links = $objGadget->hook->load('Menu')->Execute();
                    if (!Jaws_Error::IsError($links)) {
                        foreach ($links as $key => $link) {
                            if (is_array($link['url'])) {
                                $links[$key]['url'] = serialize($link['url']);
                            } else {
                                $links[$key]['url'] = rawurldecode($link['url']);
                            }

                            if (isset($link['variables'])) {
                                $links[$key]['variables'] = serialize($link['variables']);
                            }
                        }

                        return $links;
                    }
                }
            }
        }

        return array();
    }

    /**
     * Returns ACL keys of the component and user/group
     *
     * @access  public
     * @return  array   Array of default ACLs and the user/group ACLs
     */
    function GetACLKeys()
    {
        $this->gadget->CheckPermission('ManageMenus');

        $comp = jaws()->request->fetch('comp', 'post');
        // fetch default ACLs
        $default_acls = array();
        $result = $GLOBALS['app']->ACL->fetchAll($comp);
        if (!empty($result)) {
            // set ACL keys description
            $info = Jaws_Gadget::getInstance($comp);
            foreach ($result as $key_name => $acl) {
                foreach ($acl as $subkey => $value) {
                    $default_acls[] = array(
                        'key_name'   => $key_name,
                        'key_subkey' => $subkey,
                        'key_value'  => $value,
                        'key_desc'   => $info->acl->description($key_name, $subkey),
                    );
                }
            }
        }

        return $default_acls;
    }

}