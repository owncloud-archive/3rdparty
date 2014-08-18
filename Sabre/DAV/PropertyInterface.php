<?php

/**
 * PropertyInterface
 *
 * Implement this interface to create new complex properties
 *
 * @package Sabre
 * @subpackage DAV
 * @copyright Copyright (C) 2007-2014 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
interface Sabre_DAV_PropertyInterface {

    public function serialize(Sabre_DAV_Server $server, DOMElement $prop);

    static function unserialize(DOMElement $prop); 

}

