<?php

namespace Com\PaulDevelop\Library\Application;

use Com\PaulDevelop\Library\Common\Base;
use Com\PaulDevelop\Library\Common\ITemplate;
use com\pauldevelop\template\PeerHelper;
use com\pauldevelop\template\SessionConstants;

/**
 * ApiMapping
 *
 * @package  Com\PaulDevelop\Library\Application
 * @category Application
 * @author   Rüdiger Scheumann <code@pauldevelop.com>
 * @license  http://opensource.org/licenses/MIT MIT
 * @property string $Pattern
 * @property bool   $SupportParseParameter
 * @property string $Table
 * @property string $Field
 * @property string $Value
 * @property string $Template
 * @property string $Class
 */
class ApiMapping extends Base implements IMapping
{
    // region member
    /**
     * @var string
     */
    private $pattern;

    /**
     * @var bool
     */
    private $supportParseParameter;

    /**
     * @var string
     */
    private $url;

    /**
     * @var string
     */
    private $key;

    /**
     * @var string
     */
    private $value;

    /**
     * @var string
     */
    private $template;

    /**
     * @var string
     */
    private $object;
    // endregion

    // region constructor
    /**
     * @param string      $pattern
     * @param bool        $supportParseParameter
     * @param string      $url
     * @param string      $key
     * @param string      $value
     * @param string      $template
     * @param IController $object
     */
    public function __construct(
        $pattern = '',
        $supportParseParameter = false,
        $url = '',
        $key = '',
        $value = '',
        $template = '',
        IController $object = null
    ) {
        $this->pattern = $pattern;
        $this->supportParseParameter = $supportParseParameter;
        $this->url = $url;
        $this->key = $key;
        $this->value = $value;
        $this->template = $template;
        $className = get_class($object);
        $this->object = new $className($this);
    }
    // endregion

    // region methods
//    /**
//     * @param Request   $request
//     * @param ITemplate $template
//     *
//     * @return string
//     */
//    public function process(Request $request = null, ITemplate $template = null)
//    {
//        // init
//        $result = '';
//
//        //$path = $request->StrippedPath;
//        $path = $this->getCleanPath($request, $this->getSupportParseParameter());
//        $methodName = 'get'.ucfirst($this->table).'Peer';
//
//        // search page in database
//        ///** @var Page $page */
//        $dbObj = PeerHelper::$methodName()->querySinglePath(''.$this->table.'[@'.$this->field.'='.$path.']#');
//        if ($dbObj != null) {
////        $template->setTemplateFileName(APP_FS_TEMPLATE.'frontend'.DIRECTORY_SEPARATOR.$this->template);
//            $template->setTemplateFileName($this->template);
//            $template->bindVariable('page', $dbObj->getStdClass());
//
//            /** @var IController $object */
//            $object = $this->object;
//            $result = $object->process($request, $template);
//        }
//
//        // return
//        return $result;
//    }
    /**
     * @param Request   $request
     * @param ITemplate $template
     *
     * @return string
     */
    public function process(Request $request = null, ITemplate $template = null)
    {
        // init
        $result = '';

//        // field-value pairs
//        $fieldValueList = '';
//        if (is_array($this->field)) {
//            for ($i = 0; $i < count($this->field); $i++) {
//                $field = $this->field[$i];
//                $value = $this->value[$i];
//                $fieldValueList .= ($fieldValueList != '' ? ',' : '')
//                    .'@'.$field.'='.$this->evaluateValue($request, $value);
//            }
//        } else {
//            $fieldValueList = '@'.$this->field.'='.$this->evaluateValue($request, $this->value);
//        }
//
////        $path = $this->getCleanPath($request, $this->getSupportParseParameter());
//        $methodName = 'get'.ucfirst($this->table).'Peer';
//
//        // search page in database
//        ///** @var Page $page */
//        $dbObj = PeerHelper::$methodName()->querySinglePath(''.$this->table.'['.$fieldValueList.']#');
//        if ($dbObj != null) {
//            $template->setTemplateFileName($this->template);
//            $template->bindVariable('page', $dbObj->getStdClass());
//
//            /** @var IController $object */
//            $object = $this->object;
//            $result = $object->process($request, $template);
//        }

        // action
        $getParameter = array(
            $this->key => $this->evaluateValue($request, $this->value)
        );
        $getParameter = http_build_query($getParameter);

        // url
//        $url = $this->baseUrl.'/categories?'.$getParameter;
        $url = $this->url.'?'.$getParameter;

        // setup curl
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Accept: application/json'
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $response = curl_exec($ch);
        curl_close($ch);

        $responseStdClass = json_decode($response);

        if ($response != null) {
            $template->setTemplateFileName($this->template);
            $template->bindVariable('apiResponse', $responseStdClass);

            /** @var IController $object */
            $object = $this->object;
            $result = $object->process($request, $template);
        }

        // return
        return $result;
    }

    private function evaluateValue(Request $request = null, $value = '')
    {
        // init
        $result = '';

        // action
        if (preg_match('/%(.*?)%/', $value, $matches)) {
            $variableName = $matches[1];
            if ($variableName == 'request.path') {
                $result = $this->getCleanPath($request, $this->getSupportParseParameter());
            } else if ($variableName == 'session.language') {
                $language = PeerHelper::getLanguagePeer()->querySinglePath('language[@code='.$_SESSION[SessionConstants::LANGUAGE].']#');
                $result = $language->Id;
            }
        } else {
            $result = $value;
        }

        // return
        return $result;
    }

    private function getCleanPath(Request $request, $supportParseParameter = false)
    {
        // init
        $result = '';

        // action
        if ($supportParseParameter == true) {
            if ($request->StrippedPath != '') {
                $result .= $request->StrippedPath;
            }
        } else {
            if ($request->Input->Path != '') {
                $result .= $request->Input->Path;
            }
        }

        $result = trim($result, "\t\n\r\0\x0B/");

        // return
        return $result;
    }
    // endregion

    // region properties
    /**
     * @return string
     */
    public function getPattern()
    {
        return $this->pattern;
    }

    /**
     * @return boolean
     */
    public function getSupportParseParameter()
    {
        return $this->supportParseParameter;
    }

    /**
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * @return string
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return string
     */
    public function getTemplate()
    {
        return $this->template;
    }

    protected function getObject()
    {
        return $this->object;
    }
    // endregion
}
