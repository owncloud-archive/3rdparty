<?php

/**
 * CurrentUserPrivilegeSet
 *
 * This class represents the current-user-privilege-set property. When
 * requested, it contain all the privileges a user has on a specific node.
 *
 * @package Sabre
 * @subpackage DAVACL
 * @copyright Copyright (C) 2007-2014 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_DAVACL_Property_CurrentUserPrivilegeSet extends Sabre_DAV_Property {

    /**
     * List of privileges
     *
     * @var array
     */
    private $privileges;

    /**
     * Creates the object
     *
     * Pass the privileges in clark-notation
     *
     * @param array $privileges
     */
    public function __construct(array $privileges) {

        $this->privileges = $privileges;

    }

    /**
     * Serializes the property in the DOM
     *
     * @param Sabre_DAV_Server $server
     * @param DOMElement $node
     * @return void
     */
    public function serialize(Sabre_DAV_Server $server,DOMElement $node) {

        $doc = $node->ownerDocument;
        foreach($this->privileges as $privName) {

            $this->serializePriv($doc,$node,$privName);

        }

    }

    /**
     * Returns true or false, whether the specified principal appears in the
     * list.
     *
     * @return bool
     */
    public function has($privilegeName) {

        return in_array($privilegeName, $this->privileges);

    }

    /**
     * Serializes one privilege
     *
     * @param DOMDocument $doc
     * @param DOMElement $node
     * @param string $privName
     * @return void
     */
    protected function serializePriv($doc,$node,$privName) {

        $xp  = $doc->createElementNS('DAV:','d:privilege');
        $node->appendChild($xp);

        $privParts = null;
        preg_match('/^{([^}]*)}(.*)$/',$privName,$privParts);

        $xp->appendChild($doc->createElementNS($privParts[1],'d:'.$privParts[2]));

    }

    /**
     * Unserializes the {DAV:}current-user-privilege-set element.
     *
     * @param DOMElement $node
     * @return CurrentUserPrivilegeSet
     */
    static public function unserialize(DOMElement $node) {

        $result = array();

        $xprivs = $node->getElementsByTagNameNS('urn:DAV','privilege');

        for($jj=0; $jj<$xprivs->length; $jj++) {

            $xpriv = $xprivs->item($jj);

            $privilegeName = null;

            for ($kk=0;$kk<$xpriv->childNodes->length;$kk++) {

                $childNode = $xpriv->childNodes->item($kk);
                if ($t = Sabre_DAV_XMLUtil::toClarkNotation($childNode)) {
                    $privilegeName = $t;
                    break;
                }
            }

            $result[] = $privilegeName;

        }

        return new self($result);

    }

}
