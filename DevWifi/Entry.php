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

    protected $type;

    protected $grade;

    protected $device;

    protected $email = null;

    protected $date;

    protected $key;

    public function __construct($raw = null)
    {
        if($raw)
            $this->fromRaw($raw);
        else
        {
            $this->date = new \DateTime();
        }
    }

    public function __get( $property )
    {
        if( ! is_callable( array($this,'get'.ucfirst((string)$property)) ) )
            throw new \Exception((string)$property);

        return call_user_func( array($this,'get'.ucfirst((string)$property)));
    }

    public function __set( $property, $value )
    {
        if( ! is_callable( array($this,'set'.ucfirst((string)$property)) ) )
            throw new \Exception((string)$property);

        call_user_func( array($this,'set'.ucfirst((string)$property)), $value );
    }

    public function generateKey($static_wep = false)
    {
        $this->key = '';

        if ($static_wep)
            // use static WEP key
            $this->key = 'static';
        else {
            // generate WEP key
            $alphabet = "abcdef0123456789";
            for ($i = 0; $i < 10; $i++) {
                $n = rand(0, strlen($alphabet)-1);
                $this->key .= $alphabet[$n];
            }
        }
    }

    public function isValid()
    {
        return ($this->mac && $this->firstName && $this->lastName && $this->type && ($this->type == 'u' && $this->grade || $this->type != 'u') && $this->device && $this->date && $this->key);
    }

    public function toArray()
    {
        $array = array();
        foreach($this as $key => $value) {
            if ($key == 'email')
                $value = base64_decode($value);

            $array[$key] = $value;
        }
        return $array;
    }

    public function toRaw()
    {
        if(!$this->isValid())
            throw new \InputErrorException('Wpis nie jest prawidłowy. Nie można zapisać do pliku.', 503);

        return '192.168.99. 300/64 auto 100 '.$this->mac.' 0 0 WAN1 '.$this->replacePolChars($this->firstName).'.'.$this->replacePolChars($this->lastName)
                .'.'.(($this->type == 'u') ? $this->grade : $this->type).'.'.$this->device.'.'.$this->date->format('d.m.Y').'.'.$this->key . (($this->email) ? '.'.$this->email : '') . ' 0 0 0 0';
    }

    public function fromRaw($raw)
    {
        $rawA = explode(' ', $raw);
        $this->mac = $rawA[4];
        $combined = explode('.', $rawA[8]);
        $this->firstName = $combined[0];
        $this->lastName = $combined[1];

        if (in_array($combined[2], array('n', 'p'))) {
            $this->type = $combined[2];
        } else {
            $this->type = 'u';
            $this->grade = $combined[2];
        }

        $this->device = $combined[3];
        $this->date = \DateTime::createFromFormat('d-m-Y', $combined[4].'-'.$combined[5].'-'.$combined[6]);
        $this->key = $combined[7];

        if (isset($combined[8]))
            $this->email = $combined[8];
    }

    public function setMac($mac)
    {
        if( !filter_var($mac, FILTER_VALIDATE_REGEXP,
            array('options' => array('regexp' => '/^([a-f0-9]{2}[:|\-]?){6}$/i'))))
            throw new \InputErrorException('Adres MAC nie jest poprawny.', 400);

        $this->mac = strtolower($mac);
    }

    public function getMac()
    {
        return $this->mac;
    }

    public function setFirstName($v)
    {
        if( !filter_var(self::replacePolChars($v), FILTER_VALIDATE_REGEXP,
            array('options' => array('regexp' => '/^[a-z]{0,25}$/i'))) )
            throw new \InputErrorException('Imię nie jest poprawne.', 400);

        $this->firstName = ucfirst(strtolower($v));
    }

    public function getFirstName()
    {
        return $this->firstName;
    }

    public function setLastName($v)
    {
        if( !filter_var(self::replacePolChars($v), FILTER_VALIDATE_REGEXP,
            array('options' => array('regexp' => '/^[a-z]{1,25}$/i'))) )
            throw new \InputErrorException('Nazwisko nie jest poprawne.', 400);

        $this->lastName = ucfirst(strtolower($v));
    }

    public function getLastName()
    {
        return $this->lastName;
    }

    public function setType($v)
    {
        if( !filter_var($v, FILTER_VALIDATE_REGEXP,
            array('options' => array('regexp' => '/^(u|n|p)$/'))) )
            throw new \InputErrorException('Rodzaj klienta nie jest poprawny.', 400);

        $this->type = $v;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setGrade($v)
    {
        if( !filter_var($v, FILTER_VALIDATE_REGEXP,
            array('options' => array('regexp' => '/^[0-9]{1}([a-z]{0,2})?$/i'))) )
            throw new \InputErrorException('Klasa nie jest poprawna.', 400);

        $this->grade = strtolower($v);
    }

    public function getGrade()
    {
        return $this->grade;
    }

    public function setDevice($v)
    {
        if( !filter_var($v, FILTER_VALIDATE_REGEXP,
            array('options' => array('regexp' => '/^(pc|tel|tab|oth)$/'))) )
            throw new \InputErrorException('Typ urządzenia nie jest poprawny.', 400);

        $this->device = $v;
    }

    public function getDevice()
    {
        return $this->device;
    }

    public function setEmail($v)
    {
        if( !filter_var($v, FILTER_VALIDATE_EMAIL) )
            throw new \InputErrorException('Adres e-mail nie jest poprawny.', 400);

        $this->email = base64_encode(strtolower($v));
    }

    public function getEmail()
    {
        return base64_decode($this->email);
    }

    public function getDate()
    {
        return $this->date;
    }

    public function getKey()
    {
        return $this->key;
    }

    public static function replacePolChars($input)
    {
        // replace polish letters
        $replacements = array(
            'ą' =>  'a',
            'Ą' =>  'A',
            'ć' =>  'c',
            'Ć' =>  'C',
            'ę' =>  'e',
            'Ę' =>  'E',
            'ł' =>  'l',
            'Ł' =>  'L',
            'ń' =>  'n',
            'Ń' =>  'N',
            'ó' =>  'o',
            'Ó' =>  'O',
            'ś' =>  's',
            'Ś' =>  'S',
            'ż' =>  'z',
            'Ż' =>  'Z',
            'ź' =>  'z',
            'Ź' =>  'Z',
        );
        return str_replace(array_keys($replacements), array_values($replacements), $input);
    }
}
