<?php

/**
 * Sabre_CalDAV_Exception_InvalidComponentType
 *
 * @package Sabre
 * @subpackage CalDAV
 * @copyright Copyright (C) 2007-2014 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_CalDAV_Exception_InvalidComponentType extends Sabre_DAV_Exception_Forbidden {

    /**
     * Adds in extra information in the xml response.
     *
     * This method adds the {CALDAV:}supported-calendar-component as defined in rfc4791
     *
     * @param Sabre_DAV_Server $server
     * @param DOMElement $errorNode
     * @return void
     */
    public function serialize(Sabre_DAV_Server $server,DOMElement $errorNode) {

        $doc = $errorNode->ownerDocument;

        $np = $doc->createElementNS(Sabre_CalDAV_Plugin::NS_CALDAV,'cal:supported-calendar-component');
        $errorNode->appendChild($np);

    }

}
