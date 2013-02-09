<?php

/**
 * This object represents a CalDAV calendar that can be shared with other
 * users.
 *
 * @package Sabre
 * @subpackage CalDAV
 * @copyright Copyright (C) 2007-2013 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_CalDAV_ShareableCalendar extends Sabre_CalDAV_Calendar implements Sabre_CalDAV_IShareableCalendar {

    /**
     * Updates the list of shares.
     *
     * The first array is a list of people that are to be added to the
     * calendar.
     *
     * Every element in the add array has the following properties:
     *   * href - A url. Usually a mailto: address
     *   * commonName - Usually a first and last name, or false
     *   * summary - A description of the share, can also be false
     *   * readOnly - A boolean value
     *
     * Every element in the remove array is just the address string.
     *
     * @param array $add
     * @param array $remove
     * @return void
     */
    public function updateShares(array $add, array $remove) {

        $this->caldavBackend->updateShares($this->calendarInfo['id'], $add, $remove);

    }

    /**
     * Returns the list of people whom this calendar is shared with.
     *
     * Every element in this array should have the following properties:
     *   * href - Often a mailto: address
     *   * commonName - Optional, for example a first + last name
     *   * status - See the Sabre_CalDAV_SharingPlugin::STATUS_ constants.
     *   * readOnly - boolean
     *   * summary - Optional, a description for the share
     *
     * @return array
     */
    public function getShares() {

        return $this->caldavBackend->getShares($this->calendarInfo['id']);

    }

    /**
     * Marks this calendar as published.
     *
     * Publishing a calendar should automatically create a read-only, public,
     * subscribable calendar.
     *
     * @param bool $value
     * @return void
     */
    public function setPublishStatus($value) {

        $this->caldavBackend->setPublishStatus($this->calendarInfo['id'], $value);

    }

}
