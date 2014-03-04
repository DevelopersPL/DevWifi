<?php
/**
 * DevWifi
 *
 * A simple web application to register Wifi access point users
 *
 *
 * LICENSE: Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or
 * sell copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 * The above copyright notice and this permission notice shall be included
 * in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
 * PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE
 * FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE,
 * ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 * @package    DevWifi
 * @author     Daniel Speichert <daniel@speichert.pl>
 * @author     Wojciech Guziak <wojciech@guziak.net>
 * @copyright  2014 Developers.pl
 * @license    http://opensource.org/licenses/MIT MIT
 * @version    master
 * @link       https://github.com/DevelopersPL/DevWifi
 */

namespace DevWifi;


class Entry {
    protected $mac;

    protected $firstName;

    protected $lastName;

    protected $grade;

    protected $device;

    protected $date;

    protected $key;

    public function __construct($raw)
    {
        if($raw)
            $this->fromRaw($raw);
        else
        {
            $this->date = new \DateTime();

            // generate WEP key
            $alphabet = "abcdef0123456789";
            for ($i = 0; $i < 10; $i++) {
                $n = rand(0, strlen($alphabet)-1);
                $this->key .= $alphabet[$n];
            }
        }
    }

    public function __get( $property )
    {
        if( ! is_callable( array($this,'get'.ucfirst((string)$property)) ) )
            throw new BadPropertyException($this, (string)$property);

        return call_user_func( array($this,'get'.ucfirst((string)$property)));
    }

    public function __set( $property, $value )
    {
        if( ! is_callable( array($this,'set'.ucfirst((string)$property)) ) )
            throw new BadPropertyException($this, (string)$property);

        call_user_func( array($this,'set'.ucfirst((string)$property)), $value );
    }

    public function toRaw()
    {
        if(!$this->mac || !$this->firstName || !$this->lastName || $this->grade || !$this->device || !$this->date || $this->key)
            throw new InputErrorException('Entry is not valid, cannot serialize to raw.', 503);

        return '192.168.99. 300/64 auto 100 '.$this->mac.' 0 0 WAN1 '.$this->firstName.'.'.$this->lastName
                .'.'.$this->grade.'.'.$this->device.'.'.$this->date->format('d.m.Y').'.'.$this->key.' 0 0 0 0';
    }

    public function fromRaw($raw)
    {
        $rawA = explode(' ', $raw);
        $this->mac = $rawA[4];
        $combined = explode('.', $rawA[8]);
        $this->firstName = $combined[0];
        $this->lastName = $combined[1];
        $this->grade = $combined[2];
        $this->device = $combined[3];
        $this->date = \DateTime::createFromFormat('d-m-Y', $combined[4].'-'.$combined[5].'-'.$combined[6]);
        $this->key = $combined[7];
    }

    public function setMac($mac)
    {
        if( !filter_var($mac, FILTER_VALIDATE_REGEXP,
            array('options' => array('regexp' => '/([a-fA-F0-9]{2}[:|\-]?){6}/'))) )
            throw new InputErrorException('MAC address is not valid.', 400);

        $this->mac = strtolower($mac);
    }

    public function getMac()
    {
        return $this->mac;
    }

    public function setFirstName($v)
    {
        if( !filter_var($v, FILTER_VALIDATE_REGEXP,
            array('options' => array('regexp' => '/([a-zA-Z]{1-15}/'))) )
            throw new InputErrorException('First name is not valid.', 400);

        $this->firstName = $v;
    }

    public function getFirstName()
    {
        return $this->firstName;
    }

    public function setLastName($v)
    {
        if( !filter_var($v, FILTER_VALIDATE_REGEXP,
            array('options' => array('regexp' => '/([a-zA-Z]{1-20}/'))) )
            throw new InputErrorException('Last name is not valid.', 400);

        $this->lastName = $v;
    }

    public function getLastName()
    {
        return $this->lastName;
    }

    public function setGrade($v)
    {
        if( !filter_var($v, FILTER_VALIDATE_REGEXP,
            array('options' => array('regexp' => '/([a-zA-Z0-9]{1-4}/'))) )
            throw new InputErrorException('Grade is not valid.', 400);

        $this->grade = $v;
    }

    public function getGrade()
    {
        return $this->grade;
    }

    public function setDevice($v)
    {
        if( !filter_var($v, FILTER_VALIDATE_REGEXP,
            array('options' => array('regexp' => '/([a-zA-Z]{1-10}/'))) )
            throw new InputErrorException('Device name is not valid.', 400);

        $this->device = $v;
    }

    public function getDevice()
    {
        return $this->device;
    }

    public function getDate()
    {
        return $this->date;
    }

    public function getKey()
    {
        return $this->key;
    }
}
