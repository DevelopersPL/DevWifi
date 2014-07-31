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

class EntryFactory {

    // TODO: file locking

    protected $entries = array();

    protected $handle;

    public function __construct($db)
    {
        $this->handle = fopen($db, 'c+');
        if(!flock($this->handle, LOCK_EX | LOCK_NB))
            throw new \Exception('Cannot obtain lock on database!', 503);

        $this->load();
    }

    public function load()
    {
        while(!feof($this->handle)) {
            $raw = trim(fgets($this->handle));
            if($raw)
            {
                $entry = new Entry($raw);
                $this->entries[$entry->getMac()] = $entry;
            }
        }
    }

    public function get($mac)
    {
        $mac = strtolower($mac);
        if(array_key_exists($mac, $this->entries))
            return $this->entries[$mac];
    }

    public function getAll()
    {
        return $this->entries;
    }

    public function set(Entry $entry)
    {
        $this->entries[$entry->getMac()] = $entry;
    }

    public function delete(Entry $entry)
    {
        unset( $this->entries[$entry->getMac()] );
    }

    public function save()
    {
        foreach($this->entries as $entry)
            if(!$entry->isValid())
                throw new \InputErrorException('Wpis z adresem '.$entry->mac.' nie jest prawidłowy.');

        rewind($this->handle);
        ftruncate($this->handle, 0);
        foreach($this->entries as $entry)
                fwrite($this->handle, $entry->toRaw()."\n");

        fflush($this->handle);
    }

    public function __destruct ()
    {
        flock($this->handle, LOCK_UN);
        fclose($this->handle);
    }
} 