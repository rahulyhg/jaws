<?php
/**
 * Directory Gadget
 *
 * @category    Gadget
 * @package     Directory
 * @author      Mohsen Khahani <mkhahani@gmail.com>
 * @copyright   2013 Jaws Development Group
 * @license     http://www.gnu.org/copyleft/gpl.html
 */
class Directory_Actions_Directory extends Jaws_Gadget_HTML
{
    /**
     * Builds file management UI
     *
     * @access  public
     * @return  string  XHTML UI
     */
    function Directory()
    {
        $GLOBALS['app']->Layout->AddHeadLink('gadgets/Directory/resources/site_style.css');
        $this->AjaxMe('site_script.js');
        $tpl = $this->gadget->loadTemplate('Workspace.html');
        $tpl->SetBlock('workspace');

        $tpl->SetVariable('title', _t('DIRECTORY_NAME'));
        $tpl->SetVariable('lbl_new_dir', _t('DIRECTORY_NEW_DIR'));
        $tpl->SetVariable('lbl_new_file', _t('DIRECTORY_NEW_FILE'));
        $tpl->SetVariable('new_dir', 'gadgets/Directory/images/new-dir.png');
        $tpl->SetVariable('new_file', 'gadgets/Directory/images/new-file.png');

        if ($this->gadget->GetPermission('ShareFile')) {
            $tpl->SetBlock('workspace/share');
            $tpl->SetVariable('lbl_share', _t('DIRECTORY_SHARE'));
            $tpl->ParseBlock('workspace/share');
        }

        $tpl->SetVariable('lbl_edit', _t('GLOBAL_EDIT'));
        $tpl->SetVariable('lbl_delete', _t('GLOBAL_DELETE'));
        $tpl->SetVariable('lbl_move', _t('DIRECTORY_MOVE'));
        $tpl->SetVariable('lbl_props', _t('DIRECTORY_PROPERTIES'));
        $tpl->SetVariable('imgDeleteFile', STOCK_DELETE);
        $user = (int)$GLOBALS['app']->Session->GetAttribute('user');
        $tpl->SetVariable('UID', $user);
        $tpl->SetVariable('data_url', $GLOBALS['app']->getDataURL('directory/'));

        // File template
        $tpl->SetBlock('workspace/fileTemplate');
        $tpl->SetVariable('id', '{id}');
        $tpl->SetVariable('title', '{title}');
        $tpl->SetVariable('description', '{description}');
        $tpl->SetVariable('type', '{type}');
        $tpl->SetVariable('size', '{size}');
        $tpl->SetVariable('username', '{username}');
        $tpl->SetVariable('created', '{created}');
        $tpl->SetVariable('modified', '{modified}');
        $tpl->SetVariable('shared', '{shared}');
        $tpl->SetVariable('foreign', '{foreign}');
        $tpl->ParseBlock('workspace/fileTemplate');

        // Status bar
        $tpl->SetBlock('workspace/statusbar');
        $tpl->SetVariable('title', '{title}');
        $tpl->SetVariable('size', '{size}');
        $tpl->SetVariable('created', '{created}');
        $tpl->SetVariable('modified', '{modified}');
        $tpl->ParseBlock('workspace/statusbar');

        // Display probabley responses
        $response = $GLOBALS['app']->Session->PopResponse('Directory');
        if ($response) {
            $tpl->SetVariable('response', $response['text']);
            $tpl->SetVariable('response_type', $response['type']);
        }

        $tpl->ParseBlock('workspace');
        return $tpl->Get();
    }

    /**
     * Fetches list of files
     *
     * @access  public
     * @return  mixed   Array of files or false on error
     */
    function GetFiles()
    {
        $flags = jaws()->request->fetch(array('parent', 'shared', 'foreign'), 'post');
        $user = (int)$GLOBALS['app']->Session->GetAttribute('user');
        $model = $GLOBALS['app']->LoadGadget('Directory', 'Model', 'Files');
        $files = $model->GetFiles($flags['parent'], $user, $flags['shared'], $flags['foreign']);
        //_log_var_dump($files);
        if (Jaws_Error::IsError($files)){
            return array();
        }
        $objDate = $GLOBALS['app']->loadDate();
        foreach ($files as &$file) {
            $file['created'] = $objDate->Format($file['createtime'], 'n/j/Y g:i a');
            $file['modified'] = $objDate->Format($file['updatetime'], 'n/j/Y g:i a');
        }
        return $files;
    }

    /**
     * Fetches data of a file/directory
     *
     * @access  public
     * @return  mixed   Array of file data or false on error
     */
    function GetFile()
    {
        $id = jaws()->request->fetch('id', 'post');
        $model = $GLOBALS['app']->LoadGadget('Directory', 'Model', 'Files');
        $user = (int)$GLOBALS['app']->Session->GetAttribute('user');
        $access = $model->CheckAccess($id, $user);
        if ($access !== true) {
            return array();
        }
        $file = $model->GetFile($id);
        if (Jaws_Error::IsError($file)) {
            return array();
        }
        $objDate = $GLOBALS['app']->loadDate();
        $file['created'] = $objDate->Format($file['createtime'], 'n/j/Y g:i a');
        $file['modified'] = $objDate->Format($file['updatetime'], 'n/j/Y g:i a');

        // Shared for
        $model = $GLOBALS['app']->LoadGadget('Directory', 'Model', 'Share');
        $users = $model->GetFileUsers($id);
        if (!Jaws_Error::IsError($users)) {
            $uid_set = array();
            foreach ($users as $user) {
                $uid_set[] = $user['username'];
            }
            $file['users'] = implode(', ', $uid_set);
        }

        return $file;
    }

    /**
     * Fetches path of a file/directory
     *
     * @access  public
     * @return  array   Directory hierarchy
     */
    function GetPath()
    {
        $id = jaws()->request->fetch('id', 'post');
        $path = array();
        $model = $GLOBALS['app']->LoadGadget('Directory', 'Model', 'Files');
        $model->GetPath($id, $path);
        return $path;
    }

    /**
     * Builds a (sub)tree of directories
     *
     * @access  public
     * @return  string   XHTML tree
     */
    function GetTree()
    {
        $tree = '';
        $data = jaws()->request->fetch(array('root', 'exclude'), 'post');
        if ($data['root'] !== null) {
            $this->BuildTree((int)$data['root'], $data['exclude'], $tree);
        }

        $tpl = $this->gadget->loadTemplate('Move.html');
        $tpl->SetBlock('tree');
        $tpl->SetVariable('lbl_submit', _t('GLOBAL_SUBMIT'));
        $tpl->SetVariable('lbl_cancel', _t('GLOBAL_CANCEL'));
        $tpl->SetVariable('tree', $tree);
        $tpl->ParseBlock('tree');
        return $tpl->Get();
    }

    /**
     * Builds a (sub)tree of directories
     *
     * @access  public
     * @param   int     $root       File ID as tree root
     * @param   int     $exclude    File ID to be excluded in tree
     * @param   string  $tree       XHTML tree
     * @return  void
     */
    function BuildTree($root = 0, $exclude = null, &$tree)
    {
        $model = $GLOBALS['app']->LoadGadget('Directory', 'Model', 'Files');
        $user = (int)$GLOBALS['app']->Session->GetAttribute('user');
        $dirs = $model->GetFiles($root, $user, null, null, true);
        if (Jaws_Error::IsError($dirs)) {
            return;
        }
        if (!empty($dirs)) {
            $tree .= '<ul>';
            foreach ($dirs as $dir) {
                if ($dir['id'] == $exclude) {
                    continue;
                }
                $tree .= "<li><a id='node_{$dir['id']}'>{$dir['title']}</a>";
                $this->BuildTree($dir['id'], $exclude, $tree);
                $tree .= "</li>";
            }
            $tree .= '</ul>';
        }
    }

    /**
     * Moves file/directory to the given target directory
     *
     * @access  public
     * @return  mixed   Response array or Jaws_Error on error
     */
    function Move()
    {
        try {
            $data = jaws()->request->fetch(array('id', 'target'));
            if ($data['id'] === null || $data['target'] === null) {
                throw new Exception(_t('DIRECTORY_ERROR_MOVE'));
            }

            $id = (int)$data['id'];
            $target = (int)$data['target'];
            $model = $GLOBALS['app']->LoadGadget('Directory', 'Model', 'Files');

            // Validate source/target
            $file = $model->GetFile($id);
            if (Jaws_Error::IsError($file)) {
                throw new Exception($file->getMessage());
            }
            if ($target !== 0) {
                $dir = $model->GetFile($target);
                if (Jaws_Error::IsError($dir)) {
                    throw new Exception($dir->getMessage());
                }
                if (!$dir['is_dir']) {
                    throw new Exception(_t('DIRECTORY_ERROR_MOVE'));
                }
            }

            // Stop moving to itself, it's parent or it's children
            if ($target == $id || $target == $file['parent']) {
                throw new Exception(_t('DIRECTORY_ERROR_MOVE'));
            }
            $path = array();
            $id_set = array();
            $model->GetPath($target, $path);
            foreach ($path as $d) {
                $id_set[] = $d['id'];
            }
            if (in_array($id, $id_set)) {
                throw new Exception(_t('DIRECTORY_ERROR_MOVE'));
            }

            // Validate user
            $user = (int)$GLOBALS['app']->Session->GetAttribute('user');
            // FIXME: we should be able to move into a shared directory
            // if ($file['user'] != $user || $dir['user'] != $user) {
            if ($file['user'] != $user) {
                throw new Exception(_t('DIRECTORY_ERROR_MOVE'));
            }

            // Let's perform move
            $res = $model->Move($id, $target);
            if (Jaws_Error::IsError($res)) {
                throw new Exception($res->getMessage());
            }
        } catch (Exception $e) {
            return $GLOBALS['app']->Session->GetResponse($e->getMessage(), RESPONSE_ERROR);
        }

        return $GLOBALS['app']->Session->GetResponse(_t('DIRECTORY_NOTICE_MOVE'), RESPONSE_NOTICE);
    }
}