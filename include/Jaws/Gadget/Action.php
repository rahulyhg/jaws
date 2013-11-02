<?php
/**
 * Jaws Gadgets : HTML part
 *
 * @category   Gadget
 * @package    Core
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2005-2013 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
class Jaws_Gadget_Action
{
    /**
     * Jaws_Gadget object
     *
     * @var     object
     * @access  protected
     */
    var $gadget = null;

    /**
     * A list of actions that the gadget has
     *
     * @var     array
     * @access  protected
     * @see AddAction()
     */
    var $_ValidAction = array();

    /**
     * Constructor
     *
     * @access  public
     * @param   object $gadget Jaws_Gadget object
     * @return  void
     */
    function Jaws_Gadget_Action($gadget)
    {
        $this->gadget = $gadget;
        $this->LoadActions();
        // Add ShowGadgetInfo action
        $this->StandaloneAction('ShowGadgetInfo','');

        // Add Ajax actions.
        $this->StandaloneAction('Ajax', '');
        $this->StandaloneAdminAction('Ajax', '');
    }

    /**
     * Load the gadget's action stuff files
     *
     * @access  public
     */
    function LoadActions()
    {
        if (empty($this->_ValidAction)) {
            $this->_ValidAction = $GLOBALS['app']->GetGadgetActions($this->gadget->name);
            if (!isset($this->_ValidAction['index']['DefaultAction'])) {
                $this->_ValidAction['index']['DefaultAction'] = array(
                    'name' => 'DefaultAction',
                    'normal' => true,
                    'desc' => '',
                    'file' => null
                );
            }

            if (!isset($this->_ValidAction['admin']['Admin'])) {
                $this->_ValidAction['admin']['Admin'] = array(
                    'name' => 'Admin',
                    'normal' => true,
                    'desc' => '',
                    'file' => null
                );
            }
        }
    }

    /**
     * Loads the gadget action file in question, makes a instance and
     * stores it globally for later use so we do not have duplicates
     * of the same instance around in our code.
     *
     * @access  public
     * @param   bool    $backend    Admin Action?
     * @param   string  $filename   Action class file name
     * @return  mixed   Action class object on successful, Jaws_Error otherwise
     */
    function &load($backend, $filename = '')
    {
        // filter non validate character
        $filename = preg_replace('/[^[:alnum:]_]/', '', $filename);
        if (empty($filename)) {
            return $this->gadget->extensions['Action'];
        }

        $filetype = $backend? 'AdminActions' : 'Actions';
        if (!isset($this->gadget->actions[$filetype][$filename])) {
            switch ($filetype) {
                case 'Actions':
                    $classname = $this->gadget->name. "_Actions_$filename";
                    $file = JAWS_PATH. 'gadgets/'. $this->gadget->name. "/Actions/$filename.php";
                    break;

                case 'AdminActions':
                    $classname = $this->gadget->name. "_Actions_Admin_$filename";
                    $file = JAWS_PATH. 'gadgets/'. $this->gadget->name. "/Actions/Admin/$filename.php";
                    break;
            }

            if (!file_exists($file)) {
                return Jaws_Error::raiseError("File [$file] not exists!", __FUNCTION__);
            }

            include_once($file);
            if (!Jaws::classExists($classname)) {
                return Jaws_Error::raiseError("Class [$classname] not exists!", __FUNCTION__);
            }

            $this->gadget->actions[$filetype][$filename] = new $classname($this->gadget);
            $GLOBALS['log']->Log(JAWS_LOG_DEBUG, "Loaded gadget action class: [$classname]");
        }

        return $this->gadget->actions[$filetype][$filename];
    }

    /**
     * Ajax Admin stuff
     *
     * @access  public
     * @return  string  JSON encoded string
     */
    function Ajax()
    {
        if (JAWS_SCRIPT == 'admin') {
            $objAjax = $GLOBALS['app']->LoadGadget($this->gadget->name, 'AdminAjax');
        } else {
            $objAjax = $GLOBALS['app']->LoadGadget($this->gadget->name, 'Ajax');
        }

        $output = '';
        $method = Jaws_Gadget_Action::filter(jaws()->request->fetch('method', 'get'));
        if (method_exists($objAjax, $method)) {
            $output = $objAjax->$method();
        } else {
            $GLOBALS['log']->Log(JAWS_LOG_ERROR, "Action $method in {$this->gadget->name}'s Ajax dosn't exist.");
        }

        // Set Headers
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');

        return Jaws_UTF8::json_encode($output);
    }

    /**
     * Ajax the gadget adding the basic script links to build the interface
     *
     * @access  protected
     * @param   string  $file       Optional The gadget can require a special JS file,
     *                              it should be located under gadgets/$gadget/Resources/$file
     * @param   string  $version    Optional File version
     */
    function AjaxMe($file = '', $version = '')
    {
        $GLOBALS['app']->Layout->AddScriptLink('libraries/mootools/core.js');
        $GLOBALS['app']->Layout->AddScriptLink('include/Jaws/Resources/Ajax.js');
        if (!empty($file)) {
            $GLOBALS['app']->Layout->AddScriptLink(
                'gadgets/'.
                $this->gadget->name.
                '/Resources/'.
                $file.
                (empty($version)? '' : "?$version")
            );
        }

        $config = array(
            'DATAGRID_PAGER_FIRSTACTION' => 'javascript: firstValues(); return false;',
            'DATAGRID_PAGER_PREVACTION'  => 'javascript: previousValues(); return false;',
            'DATAGRID_PAGER_NEXTACTION'  => 'javascript: nextValues(); return false;',
            'DATAGRID_PAGER_LASTACTION'  => 'javascript: lastValues(); return false;',
            'DATAGRID_DATA_ONLOADING'    => 'showWorkingNotification;',
            'DATAGRID_DATA_ONLOADED'     => 'hideWorkingNotification;',
        );
        Piwi::addExtraConf($config);
    }

    /**
     * Sets the browser's title (<title></title>)
     *
     * @access  public
     * @param   string  $title  Browser's title
     */
    function SetTitle($title)
    {
        //Set title in case we are no running on standalone..
        if (isset($GLOBALS['app']->Layout)) {
            $GLOBALS['app']->Layout->SetTitle($title);
        }
    }

    /**
     * Sets the browser's title (<title></title>)
     *
     * @access  public
     * @param   string  $title  Browser's title
     */
    function SetDescription($desc)
    {
        //Set description in case we are no running on standalone..
        if (isset($GLOBALS['app']->Layout)) {
            $GLOBALS['app']->Layout->SetDescription($desc);
        }
    }

    /**
     * Add keywords to meta keywords tag
     *
     * @access  public
     * @param   string  $keywords
     */
    function AddToMetaKeywords($keywords)
    {
        //Add keywords in case we are no running on standalone..
        if (isset($GLOBALS['app']->Layout)) {
            $GLOBALS['app']->Layout->AddToMetaKeywords($keywords);
        }
    }

    /**
     * Add a language to meta language tag
     *
     * @access  public
     * @param   string  $language  Language
     */
    function AddToMetaLanguages($language)
    {
        //Add language in case we are no running on standalone..
        if (isset($GLOBALS['app']->Layout)) {
            $GLOBALS['app']->Layout->AddToMetaLanguages($language);
        }
    }

    /**
     * Execute the action
     *
     * @access  public
     */
    function Execute($action)
    {
        if (!$this->IsValidAction($action)) {
            return Jaws_Error::raiseError(
                'Invalid action '.$this->gadget->name.'::'.$action,
                __FUNCTION__
            );
        }

        if (isset($GLOBALS['app']->Layout)) {
            $title = strtoupper($this->gadget->name.'_ACTIONS_'.$action.'_TITLE');
            $description = strtoupper($this->gadget->name.'_ACTIONS_'.$action.'_DESC');
            $title = (_t($title) == $title)? '' : _t($title);
            $description = (_t($description) == $description)? '' : _t($description);
            $GLOBALS['app']->Layout->SetTitle($title);
            $GLOBALS['app']->Layout->SetDescription($description);
        }

        $file = $this->_ValidAction[JAWS_SCRIPT][$action]['file'];
        if (!empty($file)) {
            if (JAWS_SCRIPT == 'index') {
                $objAction = Jaws_Gadget::getInstance($this->gadget->name)->loadAction($file);
            } else {
                $objAction = Jaws_Gadget::getInstance($this->gadget->name)->loadAdminAction($file);
            }

            if (Jaws_Error::isError($objAction)) {
                return $objAction;
            }

            return $objAction->$action();
        }

        return $this->$action();
    }

    /**
     * Adds a new Action
     *
     * @access  protected
     * @param   string  $name   Action name
     * @param   string  $script Action script
     * @param   string  $mode   Action mode
     * @param   string  $description Action's description
     */
    function AddAction($action, $script, $mode, $description, $file = null)
    {
        $this->_ValidAction[$script][$action] = array(
            'name' => $action,
            $mode => true,
            'desc' => $description,
            'file' => $file
        );
    }

    /**
     * Set a Action mode
     *
     * @access  protected
     * @param   string  $name       Action's name
     * @param   string  $new_mode   Action's new mode
     * @param   string  $old_mode   Action's old mode
     * @param   string  $desc       Action's description
     */
    function SetActionMode($action, $new_mode, $old_mode, $desc = null, $file = null)
    {
        $this->_ValidAction[JAWS_SCRIPT][$action] = array(
            'name' => $action,
            $new_mode => true,
            $old_mode => false,
            'desc' => $desc,
            'file' => $file
        );
    }

    /**
     * Adds a normal action
     *
     * @access  protected
     * @param   string  $action Action
     * @param   string  $name Action's name
     * @param   string  $description Action's description
     */
    function NormalAction($action, $name = null, $description = null)
    {
        $this->AddAction($action, 'index', 'normal', $description);
    }

    /**
     * Adds an admin action
     *
     * @access  protected
     * @param   string  $action Action
     * @param   string  $name Action's name
     * @param   string  $description Action's description
     */
    function AdminAction($action, $name = null, $description = null)
    {
        $this->AddAction($action, 'admin', 'normal', $description);
    }

    /**
     * Verifies if the action is for admin users(for controlpanel)
     *
     * @access  public
     * @param   string  $action to Verify
     * @return  bool    True if action is for admin users, if not, returns false
     */
    function IsAdmin($action)
    {
        if ($this->IsValidAction($action, 'admin')) {
            return (isset($this->_ValidAction['admin'][$action]['normal']) &&
                    $this->_ValidAction['admin'][$action]['normal']) ||
                   (isset($this->_ValidAction['admin'][$action]['standalone']) &&
                    $this->_ValidAction['admin'][$action]['standalone']);
        }

        return false;
    }

    /**
     * Verifies if action is normal
     *
     * @access  public
     * @param   string  $action to Verify
     * @return  bool    True if action is normal, if not, returns false
     */
    function IsNormal($action)
    {
        if (empty($action)) {
            $action = 'DefaultAction';
        }

        if ($this->IsValidAction($action, 'index')) {
            return (isset($this->_ValidAction['index'][$action]['normal']) &&
                    $this->_ValidAction['index'][$action]['normal']) ||
                   (isset($this->_ValidAction['index'][$action]['standalone']) &&
                    $this->_ValidAction['index'][$action]['standalone']);
        }

        return false;
    }

    /**
     * Adds a standalone action
     *
     * @access  protected
     * @param   string  $action Action
     * @param   string  $name Action's name
     * @param   string  $description Action's description
     */
    function StandaloneAction($action, $name = null, $description = null)
    {
        $this->AddAction($action, 'index', 'standalone', $name, $description);
    }

    /**
     * Adds a standalone/admin action
     *
     * @access  protected
     * @param   string  $action Action
     * @param   string  $name Action's name
     * @param   string  $description Action's description
     */
    function StandaloneAdminAction($action, $name = null, $description = null)
    {
        $this->AddAction($action, 'admin', 'standalone', $name, $description);
    }

    /**
     * Verifies if action is a standalone
     *
     * @access  public
     * @param   string  $action to Verify
     * @return  bool    True if action is standalone, if not, returns false
     */
    function IsStandAlone($action)
    {
        if ($this->IsValidAction($action, 'index')) {
            return (isset($this->_ValidAction['index'][$action]['standalone']) &&
                    $this->_ValidAction['index'][$action]['standalone']);
        }
        return false;
    }

    /**
     * Verifies if action is a standalone of controlpanel
     *
     * @access  public
     * @param   string  $action to Verify
     * @return  bool    True if action is standalone of the controlpanel if not, returns false
     */
    function IsStandAloneAdmin($action)
    {
        if ($this->IsValidAction($action, 'admin')) {
            return (isset($this->_ValidAction['admin'][$action]['standalone']) &&
                    $this->_ValidAction['admin'][$action]['standalone']);
        }
        return false;
    }

    /**
     * Uses the admin of the gadget(in controlpanel)
     *
     * @access  public
     * @return  string  The text to show
     */
    function Admin()
    {
        $str = _t('GLOBAL_JG_NOADMIN');
        return $str;
    }

    /**
     * Validates if an action is valid
     *
     * @access  public
     * @param   string  $action Action to validate
     * @return  mixed   Action mode if action is valid, otherwise false
     */
    function IsValidAction($action, $script = JAWS_SCRIPT)
    {
        return isset($this->_ValidAction[$script][$action]);
    }

    /**
     * Filter non validate character
     *
     * @access  public
     * @param   string  $action Action name
     * @return  string  Filtered action name
     */
    public static function filter($action)
    {
        return preg_replace('/[^[:alnum:]_]/', '', @(string)$action);
    }

}